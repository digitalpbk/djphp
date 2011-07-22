<?php
require("Cookies.php");

class HttpRequest{
    public $path;
    public $uri;
    public $QUERY_STRING;
    public $method;
    public $GET;
	public $POST;
	public $COOKIE;
	public $domain;
    public $HEADERS;
    public $REMOTE_IP;
    
    private $__lazy;
    private $__cache;
    
	function is_ajax() {
		if( !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ) {
			return TRUE;
		}
		return FALSE;
	}
	
	function __construct(){

		$this->uri = $_SERVER['REQUEST_URI'];
		$this->GET = &$_GET;
		$this->POST = &$_POST;
		$this->COOKIE = new CookieManager($_COOKIE);

        if (get_magic_quotes_gpc()) {
            array_walk_recursive($this->POST, array($this,'_sanitizeVariables'));
            array_walk_recursive($this->GET, array($this,'_sanitizeVariables'));
            array_walk_recursive($this->COOKIE, array($this,'_sanitizeVariables'));
        }

        
		$this->domain = $_SERVER['HTTP_HOST'];
		$this->method = $_SERVER['REQUEST_METHOD'];
		$this->QUERY_STRING = $_SERVER['QUERY_STRING'];
		$this->path = rtrim(!empty($this->QUERY_STRING)?str_replace("?".$this->QUERY_STRING,"",$this->uri):$this->uri,"?");

        $this->HEADERS = apache_request_headers();
        $this->REMOTE_IP = $_SERVER['REMOTE_ADDR'];
        $this->__lazy = array();
	}

    // sanitization
    private function _sanitizeVariables(&$item, $key)
    {
        if (!is_array($item))
            $item = stripcslashes($item);
    }

    function lazy_set($field,$callback,$params) {
        $this->__lazy[$field] = array($callback,$params);
    }

    function __get($name){
        if(isset($this->__cache[$name])){
            return $this->__cache[$name];
        }
        if(isset($this->__lazy[$name])){
            list($callback,$params) = $this->__lazy;
            $o = call_user_func_array($callback,$params);
            $this->__cache[$name] = $o;
            return $o;
        }

        if($name == 'base_domain') {
            $domain_port = explode(':',$this->domain);
            $domains = explode('.',$domain_port[0]);
            $tld = array_pop($domains);
            $root = array_pop($domains);
            return $root. '.' . $tld;
        }
        
        return NULL;
    }
}

class HttpResponse{
	public $mimetype;
    public $eTag;
	public $status;
	public $content;
    public $last_modified;
	
	function __construct($content,$status = NULL,$mimetype = NULL){
		$this->status = $status? $status: 200;
		$this->content = $content;
		$this->mimetype = $mimetype?$mimetype:"text/html";
	}
}


class HttpException extends Exception{
    public $status;
    public $param;
    
    function __construct($status, $param = NULL, $message = NULL) {
		parent::__construct($message);
		$this->status = $status;
		$this->param = $param;
    }
}

class Http404 extends HttpException{
    function __construct($message=NULL) {
        if(!$message) $message = "Page Not Found";
		parent::__construct(404,NULL,$message);
    }
}
