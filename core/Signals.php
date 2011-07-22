<?php
class Signals {
    private $_listeners = array();
    private static $_instance = NULL;
        
    public static function get_instance(){
        if(self::$_instance == NULL){
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    public function connect($type,$func_name,$sender=NULL,$unique_name = NULL){
		if(!isset($this->_listeners[$type])){
            $this->_listeners[$type] = array();
		}

        if($unique_name)
            $this->_listeners[$type][$unique_name] = array($func_name,$sender);
        else
            $this->_listeners[$type][] = array($func_name,$sender);

    }
    
    public function fire($type, Argument $kwargs, $sender){
        $return = array();
        if(isset($this->_listeners[$type])){
            foreach($this->_listeners[$type] as $listener){
                list($func,$sender_this) = $listener;
				if($sender_this === NULL || $sender === $sender_this) {
					if(is_array($func)){
						if(is_object($func[0])) {
							$key = get_class($func[0]) . '.' . $func[1];
							$return[$key] = $func[0]->{$func[1]}($sender, $kwargs);
						}
						else {
							$key = join('.',$func);
							$return[$key] = call_user_func($func,$sender,$kwargs);
						}
					}
					else {
						$return[$func] = $func($sender,$kwargs);
					}
					
				}
            }
        }
        
        return $return;
    }

    public function debug_print() {
        var_dump($this->_listeners);
    }
}

return Signals::get_instance();
