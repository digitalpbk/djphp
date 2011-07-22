<?php

/**
 * @file The flash message displayed at the top of the page is what is referred to as "message" here
 *       This file handles this message
 *       Each session has his own set of messages
 */

define('FLASHMESSAGER_ERROR','error');
define('FLASHMESSAGER_WARNING','warning');
define('FLASHMESSAGER_INFO','info');
define('FLASHMESSAGER_DEBUG','debug');

define('FLASHMESSAGER_DEFAULT_TIMEOUT',1200);


class LazyMessages implements Iterator{
    private $request;
    private $message;
    private $cache;
    private $position;

    function __construct($request,$message){
        $this->request = $request;
        $this->message = $message;
        $this->cache = NULL;
        $this->position = 0;
    }

    function is_empty(){
        return $this->message->is_empty($this->request);
    }

    function rewind() {
        $this->position = 0;
    }

    function current() {
        $this->_load();
        return $this->cache[$this->position];
    }

    function key() {
        return $this->position;
    }

    function next() {
        ++$this->position;
    }

    function valid() {
        $this->_load();
        return isset($this->cache[$this->position]);
    }

    private function _load(){
        if($this->cache === NULL){
            $this->cache = $this->message->get_and_delete_messages($this->request);
        }
    }
}

class FlashMessagerMiddleware {
    function process_request($request){
        FlashMessager::get_instance();
    }

    function process_response($request, $response) {
        $messages = FlashMessager::get_instance();
        $messages->save($request);
    }
}

class FlashMessager {
    static $instance;
    private $_cache;
    
    public function get_instance(){
        if(!self::$instance) {
            self::$instance = new self();
            $signals = import('djphp.core.Signals');
            $signals->connect("EXTRA_CONTEXT",array(self::$instance,"get_lazy_messages"));
        }

        return self::$instance;
    }

    public function get_lazy_messages($sender,$kwargs){
        $request = $kwargs->request;
        return array("messages" => new LazyMessages($request,$this));
    }

	private function getMsgid($SESSION) {
		$key = $SESSION['flashmsgid'];
		if(empty($key)) {
			$key = uniqid() . $SESSION['session_id'];
			$SESSION['flashmsgid'] = $key;
		}
		return $key;
	}

	public function get_and_delete_messages($request) {
		$cacheKey = $this->getMsgid($request->SESSION);
		$cache = import('djphp.cache.default');
        $messages = $this->get_messages($cacheKey);

        if($messages) {
		    $cache->flush($cacheKey);
        }

        $this->_cache = NULL;
        return $messages;
	}

    public function is_empty($request){
        $cacheKey = $this->getMsgid($request->SESSION);
        $messages = $this->get_messages($cacheKey);
        return empty($messages);
    }

    public function get_messages($key){

        if(!$this->_cache){
            $cache = import('djphp.cache.default');
		    $this->_cache = $cache->get($key);
        }
        
        return $this->_cache;
    }

	public function message($request,$incomingMsg,$type=FLASHMESSAGER_ERROR) {
		$cacheKey = $this->getMsgid($request->SESSION);
		$message = array(
			'msg'  => $incomingMsg,
			'type' => $type,
		);

        $messages = $this->get_messages($cacheKey);
        
        if(!$messages){
            $messages = array();
        }
		$messages[] = $message;

        $this->_cache = $messages;
    }

    public function save($request){
        $cacheKey = $this->getMsgid($request->SESSION);
        $messages = $this->get_messages($cacheKey);
        
        if($messages) {
		    $cache = import('djphp.cache.default');
            $cache->set($cacheKey,$messages,FLASHMESSAGER_DEFAULT_TIMEOUT);

        }
	}

    function warn($request,$message){
        $this->message($request,$message,FLASHMESSAGER_WARNING);
    }

    function error($request,$message){
        $this->message($request,$message,FLASHMESSAGER_ERROR);
    }

    function info($request,$message){
        $this->message($request,$message,FLASHMESSAGER_INFO);
    }
}


return FlashMessager::get_instance();

// vim: tabstop=4 softtabstop=4 shiftwidth=4 noexpandtab ai

