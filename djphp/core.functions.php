<?php

function import_translate($file) {
    return str_replace(".",DIRECTORY_SEPARATOR,$file);
}

function import($file,$klass=NULL,$optional=FALSE){
    $file = import_translate($file);
    $trace=debug_backtrace();
    $caller_file = $trace[0]['file'];
    
    if ($caller_file !== __FILE__) {
	    //Look in same directory also
	    $old = set_include_path(dirname($caller_file) . PATH_SEPARATOR . get_include_path());
    }

    if($optional)
        $ret = @include_once($file . EXT);
    else
        $ret = require_once($file . EXT);
    
    if ($caller_file !== __FILE__) {
	    set_include_path($old);
    }

    static $instances = FALSE;
    if(!$instances) $instances = array();
    
    if($ret === true) {
	    if(isset($instances[$file]))
            $ret = $instances[$file];
    }
    else if (is_object($ret) || is_array($ret)) {
	    $instances[$file] = &$ret;	
    }
    
    if($klass){
	    return new $klass;
    }
    else{
	    return $ret;
    }
}

function setStaticProperty($klass, $field, $value) {
	$class = new ReflectionClass($klass);
	try{
	    $class->setStaticPropertyValue($field, $value);
	    return 1;
	}
	catch(Exception $e){
	    return NULL;
	}
}

function getStaticProperty($class, $field) {
    if(!$class instanceof ReflectionClass)
	    $class = new ReflectionClass($class);

	try{
		return $class->getStaticPropertyValue($field);
	}
	catch(Exception $e){
		return NULL;
	}
}

class ArgumentError extends ErrorException {
        
}

class Argument {
    var $args;
    
    function __construct($args) {
		$this->args = $args;
    }
	
	function __get($field) {
		return $this->get($field);
	}
	
	function __set($field, $value){
		$this->set($field,$value);
	}
    
    function get($field, $default = NULL) {
		if(isset($this->args[$field])) {
			return $this->args[$field];
		}
		return $default;
    }
	
	function set($field,$value){
		$this->args[$field] = $value;
	}
    
    function get_required($field) {
		if(isset($this->args[$field])) {
			return $this->args[$field];
		}
		
		throw new ArgumentError('"'.$field . '" is required');
    }
	
	public function hash(){
		return join('/',$this->args);
	}

    public function get_all() {
        return $this->args;
    }
}
/**
 * @return Argument
 */
function kwargs() {
    $list = func_get_args();
	if(count($list) == 1 && is_array($list)){
		$list = $list[0];
	}

    if(isset($list[0]) && $list[0] instanceof Argument) {
		return $list[0];
    }
	
    $assoc = array();

    while ($list and count($list) > 1) {
        $assoc[array_shift($list)] = array_shift($list);
    }

    if ($list) { $assoc[] = $list[0]; }

    return new Argument($assoc); 
}

class BaseSettings {
    function get($key, $default = NULL){
		if(isset($this->{$key})) {
			return $this->{$key};
		}
		return $default;
    }
}

function settings_merge(&$settings, &$default_settings) {
    foreach($default_settings as $var => $value){
	if(!isset($settings->{$var})){
	    $settings->{$var} = $value;
	}
    }
}

function module_dot_class($class){
	$parts = explode('.', $class);
	$klass = array_pop($parts);
	$module = implode('.', $parts);
	
	return array($module, $klass);
}


function is_numeric_array($array) {
   foreach ($array as $a => $b) {
      if (!is_int($a)) {
         return false;
      }
   }
   return true;
}

function djphp_call_user_func() {
    $args = func_get_args();
    $cb = array_shift($args);
    if(is_array($cb)){
        list($class,$method) = $cb;
        if(is_object($class)){
            switch(count($args)){
                case 0:return $class->$method();
                case 1:return $class->$method($args[0]);
                case 2:return $class->$method($args[0], $args[1]);
                case 3:return $class->$method($args[0], $args[1], $args[2]);
                case 4:return $class->$method($args[0], $args[1], $args[2], $args[3]);
                case 5:return $class->$method($args[0], $args[1], $args[2], $args[3], $args[4]);
                case 6:return $class->$method($args[0], $args[1], $args[2], $args[3], $args[4], $args[5]);
            }
        }
    }

    return call_user_func_array($cb,$args);
}