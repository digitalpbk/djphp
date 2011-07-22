<?php

class DB_Connection implements arrayaccess {
    static $instance;
    static $connections;
    
    public static function get_instance() {
        if(self::$instance == NULL) {
            self::$instance = new DB_Connection();
        }
        return self::$instance;
    }
    
    public function offsetGet($db) {
        if(!$db) $db = 'default';
        
        if(isset($connections[$db])) {
            return $connections[$db];
        }
        
        $config = App::$settings->DATABASES[$db];
        if(!$config) {
			throw new DBException("Invalid DB alias $db");
		}
        import($config['driver']);
        $class = array_pop(explode('.',$config['driver'])) . 'Backend';
        
        $connections[$db] = new $class($config);
        return $connections[$db];
    }
    
    function offsetSet($db,$value){
        
    }
    
    function offsetExists($db){
        return isset($connections[$db]);
    }
    
    function offsetUnset($db){}
	
	function begin_transaction(){
		foreach(self::$connections as $connection){
			$connection->begin_transaction();
		}
	}
	
	function end_transaction($commit=TRUE){
		foreach(self::$connections as $connection){
			$connection->end_transaction($commit);
		}
	}
}

class DBException extends Exception {
    public $error_code;
    public function __construct($msg,$error_code = 0) {
        $this->error_code = $error_code;
        parent::__construct($msg);
    }
}

return DB_Connection::get_instance();