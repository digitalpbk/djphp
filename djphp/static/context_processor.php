<?php

class StaticContextProcessor {
    function get_context(){
        return array(
            "MEDIA_URL" => App::$settings->MEDIA_URL,
            "DEBUG" => App::$settings->DEBUG,
            "SITE_NAME" => App::$settings->SITE_NAME,
        );
    }
}