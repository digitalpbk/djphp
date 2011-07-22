<?php

class SessionMiddleware {
	
	function process_request($request) {
		if(!App::$settings->SESSION_BACKEND)
			throw new Exception("SESSION_BACKEND is not defined in settings");
		
		list($module, $klass) = module_dot_class(App::$settings->SESSION_BACKEND);
		import($module);
		
		$request->SESSION = new $klass($request->COOKIE[App::$settings->SESSION_COOKIE]);
	}
	
	function process_response($request,$response) {
		$cookie = App::$settings->SESSION_COOKIE;
		if(!isset($request->COOKIE[$cookie]) || ($request->SESSION->pack() != $request->COOKIE[$cookie])){
			$request->COOKIE->set($cookie,$request->SESSION->pack(),App::$settings->SESSION_EXPIRES);
		}
	}
}