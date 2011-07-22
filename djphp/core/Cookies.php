<?php

class CookieManager implements ArrayAccess {
    private $store;

    function __construct(&$cookies){
        $this->store = $cookies;
    }

    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            throw new Exception("Key Required");
        } else {
            $this->store[$offset] = $value;
            $this->set($offset,$value);
        }
    }

    public function set($key, $value, $keep_for = 3600){
        setcookie($key,$value,time() + $keep_for,App::$settings->COOKIE_PATH);
    }

	public function offsetExists($offset) {
        return isset($this->store[$offset]);
    }

	public function offsetUnset($offset) {
        unset($this->store[$offset]);
        setcookie($offset,' ',time() - 864000,App::$settings->COOKIE_PATH);
    }

    public function offsetGet($offset) {
        return isset($this->store[$offset]) ? $this->store[$offset] : null;
    }
}