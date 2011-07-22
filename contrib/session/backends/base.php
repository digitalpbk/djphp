<?php

class BaseSession implements arrayaccess {
	protected $container = array();
    public $session_id = NULL;
	
	public function __construct($initial,$session_id) {
        $this->container = $initial;
		if($session_id === NULL){
			$this->session_id = md5(uniqid());
		}
		else {
			$this->session_id = $session_id;
		}
    }
	
    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            throw new Exception("Key Required");
        } else {
            $this->container[$offset] = $value;
        }
    }
    
	public function offsetExists($offset) {
        return isset($this->container[$offset]);
    }
    
	public function offsetUnset($offset) {
        unset($this->container[$offset]);
    }
	
    public function offsetGet($offset) {
        return isset($this->container[$offset]) ? $this->container[$offset] : null;
    }
	
	public function pack(){
		return serialize($container);
	}
}