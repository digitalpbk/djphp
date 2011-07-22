<?php

class SlO {
    public $value;
    function __construct($value) {
        $this->value = $value;
    }
}

class BaseCache {
    protected static $timeout = 3600;
    static function get($key) {}
    static function store($value, $timeout = NULL,$raw = FALSE) {}
    static function set($key, $value, $timeout = NULL) {}
    static function flush($key) {}

    static function prefixed_key($key){
        return 'DJPHP_' . App::$settings->SITE_ID . $key;
    }

    static function encode($value, $raw) {
        if($raw || !is_object($value))
           return $value;
        return serialize(new SlO($value));
    }

    static function decode($value) {
        if(is_array($value) || is_numeric($value)){
            return $value;
        }
        
        $object = unserialize($value);
        if($object instanceof SlO){
            return $object->value;
        }
        return $object;
    }

    static function generate_key() {
        return uniqid() . time();
    }
}

