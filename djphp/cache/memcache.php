<?php
import("cachebase");

class MemCacheBackend extends BaseCache {
    static $handle = NULL;

    static function get($key,$raw=FALSE) {
        $key = parent::prefixed_key($key);
        if($handle = self::get_handle()) {
            $value = $handle->get ( $key );
            if(!$raw)
                return parent::decode($value);
            else
                return $value;
        }
        return NULL;
    }

    static function add($key,$value,$timeout, $raw = FALSE){
        $key = parent::prefixed_key($key);
        $timeout = $timeout !== NULL ? $timeout : self::$timeout;
        if($handle = self::get_handle()) {
            return $handle->add ( $key, parent::encode($value,$raw), false, $timeout );
        }
        return FALSE;
    }

    static function set($key, $value, $timeout = NULL, $raw = FALSE) {
        $key = parent::prefixed_key($key);
        $timeout = $timeout !== NULL ? $timeout : self::$timeout;
        if($handle = self::get_handle()) {
            return $handle->set ( $key, parent::encode($value,$raw), false, $timeout );
        }
        return FALSE;
    }

    static function store($value, $timeout=NULL, $raw = FALSE) {
        $key = parent::generate_key();
        if(self::set($key, $value, $timeout,$raw))
            return $key;
        return NULL;
    }

    static function flush($key) {
        $key = parent::prefixed_key($key);
        if($handle = self::get_handle()) {
            return $handle->delete ( $key );
        }
        return NULL;
    }

    static function get_handle(){
        if(!self::$handle){
            if(function_exists('memcache_connect')){
                $memcache_object = new Memcache ();

                // multi-host setup will share key space
                foreach(App::$settings->CACHES as $defn){
                    list($module,$class) = module_dot_class($defn['driver']);

                    if($class == __CLASS__) {
                        if(is_array($defn['location'])){
                            foreach($defn['location'] as $location){
                                $strs = explode(':',$location);
                                $host = $strs[0];
                                $port = $strs[1];
                                $memcache_object->addServer ( $host, $port );
                            }
                        }
                        else {
                            $strs = explode(':',$defn['location']);
                            $host = $strs[0];
                            $port = $strs[1];
                            $memcache_object->addServer ( $host, $port );
                        }
                    }
                }

                self::$handle = $memcache_object;
            }
        }

        return self::$handle;
    }
}
