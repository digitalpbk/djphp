<?php

class DecoratorException extends Exception {
    public $response;

    function __construct($response){
        $this->response = $response;
        parent::__construct();
    }
}

