<?php
import("djphp.models.BaseModel");
import("djphp.models.CachedManager");
import("djphp.models.Fields");
import("djphp.contrib.auth.models");

class Variables {
    private $_proxy;

    function __construct($proxy) {
        $this->_proxy = $proxy;
    }

    function __get($name) {
        return $this->_proxy->{$name};
    }

    function __set($name, $value) {
        $this->_proxy->{$name} = $value;
    }

    function __unset($name) {
        unset($this->_proxy->{$name});
    }

    function __isset($name) {
        return isset($this->_proxy->{$name});
    }

    function save() {
        if(SystemVariables::$object) {
            $sys_var = SystemVariables::$object;
        }
        else {
            $sys_var = new SystemVariables();
            $sys_var->id = 1;
        }

        $variables = json_encode($this->_proxy);
        if($sys_var->variables = $variables) {
            $sys_var->variables = $variables;
            $sys_var->save();
        }
        
        SystemVariables::$object = $sys_var;
    }
}

class SystemVariables extends BaseModel {
    public $id;
    public $variables;

    public static $table = "variables";
    public static $objects;
    public static $fields;

    //singleton objects
    public static $instance;
    public static $object;

    public static function init() {
        if(!self::$objects) {
            self::$fields = array(
                new AutoField(kwargs('field','id')),
            );

            try{
                self::$objects = new CachedManager(__CLASS__);
            }
            catch(AppException $e) {
                self::$objects = new BaseManager(__CLASS__);
            }

            self::$object = NULL;
        }
    }

    public static function get_instance(){
        if(!self::$instance) {
            try {
                self::init();
                self::$object = self::$objects->get(1);
                self::$instance = new Variables(json_decode(self::$object->variables));
            }
            catch(DoesNotExist $e){
                self::$instance = new Variables(new stdClass());
            }
        }
        return self::$instance;
    }

}

return SystemVariables::get_instance();

