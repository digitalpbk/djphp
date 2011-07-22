<?php

import("cachebase");

class ApcCache extends BaseCache {
    static function get($key) {
        $key = parent::prefixed_key($key);
        if(function_exists('apc_fetch')) {
            return apc_fetch($key);
        }
        return NULL;
    }
    
    static function set($key, $value, $timeout = NULL) {
        $key = parent::prefixed_key($key);
        $timeout = $timeout !== NULL ? $timeout : self::$timeout; 
        if(function_exists('apc_store')) {
            return apc_store($key, $value, $timeout);
        }
    }
    
    static function flush($key) {
        $key = parent::prefixed_key($key);
        if(function_exists('apc_delete')) {
            return apc_delete($key);
        }
    }

    static function is_available() {
        return function_exists('apc_store');
    }
}
