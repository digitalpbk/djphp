<?php
import("djphp.models.BaseModel");
import("djphp.models.CachedManager");
import("djphp.models.Fields");
import("djphp.contrib.auth.models");

class SystemVariables extends BaseModel {
    public $id;
    public $variables;

    public static $table = "variables";
    public static $objects;
    public static $fields;

    public static $instance;

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
        }
    }

    public static function get_instance(){
        if(!self::$instance) {
            try {
                self::init();
                $object = self::$objects->get(1);
                self::$instance = json_decode($object->variables);
            }
            catch(DoesNotExist $e){
                self::$instance = new stdClass();
            }
        }
        return self::$instance;
    }

}

return SystemVariables::get_instance();

