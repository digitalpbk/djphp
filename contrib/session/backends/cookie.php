<?php
require('base.php');

class CookieSessionBackend extends BaseSession{
	function __construct($content){
		$hash = substr($content,-16);
		$cookie = substr($content,0,-16);
		
		if($hash == $this->hash($cookie)){
			$contents = unserialize($cookie);
			$session_id = $contents['session_id'];
			unset($contents['session_id']);
			parent::__construct($contents,$session_id);
		}
		else {
			parent::__construct(array(),NULL);
		}
	}
	
	function pack(){
		$container = $this->container;
		$container['session_id'] = $this->session_id;
		$contents = serialize($container);
		$contents .= $this->hash($contents);
		return $contents;
	}
	
	function hash($contents){
		return substr(base64_encode(md5(App::$settings->SECRET . $contents,TRUE)),0,16);
	}
}