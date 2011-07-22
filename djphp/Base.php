<?php
define('EXT','.php');

if (!defined('PHP_VERSION_ID')) {
    $version = explode('.', PHP_VERSION);

    define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
}

require_once("core.functions.php");

class AppException extends Exception {}

class App {
	public static $settings;
	public static $djphp_path;
	
	function __construct($settings_file){
		$this->setup_environ($settings_file);
		$this->init_settings($settings_file);
		
		if(App::$settings->DEBUG){
			error_reporting(E_ALL);
		}
		else {
			//error_reporting(0);
		}

	}
	
	function init_settings($settings_file){
		$default_settings = import("djphp.conf.settings","DefaultSettings");
		$settings = import($settings_file,"Settings");
		settings_merge($settings, $default_settings);
		App::$settings = $settings;
		
	}
	
	function setup_environ($settings_file){
		$path = array();
		$path[] = dirname($settings_file);
		self::$djphp_path = dirname(dirname(__FILE__));
		$path[] = self::$djphp_path;
		$paths = join(PATH_SEPARATOR, array_unique($path));
		set_include_path($paths);		
	}

	public function handle() {
		ob_start();
		$response = &$this->_handle();
		if(App::$settings->DEBUG) {
			$debug = ob_get_contents();
		}
		ob_end_clean();
		
		if($response->status == 301 || $response->status == 302){
			header("Location: ".$response->content, TRUE, $response->status);
		}
		else {
			if($response->status != 200) {
				header("HTTP/1.1 " . $response->status);
			}
			
			header("Content-Type: ". $response->mimetype);
			
			if(!empty($response->content)){
				echo $response->content;
			}
			
			if(App::$settings->DEBUG && $debug) {
				echo '----------------<hr/>-------------------<br/>'."\n";
				echo $debug;
			}
		}
	}
	
	private function _handle(){
		try{
			return $this->get_response();
		}
		catch(HttpException $e) {
            
			try {
				$view_path = App::$settings->ERROR_VIEW . '.http' . $e->status;
				$view_args = array($e);
				return $this->call_view($request,$view_path,$view_args,NULL);
			}
			catch(Exception $e) {
				if(App::$settings->DEBUG) {
					$content = $e->getTraceAsString();
					$content .= "\n\n\n";
					$content .= $e->getMessage();
					return new HttpResponse($content);
				}
				else {
					return new HttpResponse('');
				}
			}
		}
		catch(Exception $e) {
			if(App::$settings->DEBUG) {
				$view_path = 'djphp.contrib.error.views.ErrorController.http500';
			}
			else {
 				$view_path = App::$settings->ERROR_VIEW . '.http500';
			}
			
			$view_args = array($e);
			return $this->call_view($request,$view_path,$view_args,NULL);
		}
		
	}
	
	function setup_signals($sender, $kwargs) {
		foreach(App::$settings->INSTALLED_APPS as $app){
			import($app.".signals",NULL,TRUE);
		}
	}
	
	function get_response(){
		import("djphp.core.Http");
		$request = new HttpRequest();
		
		$signals = import("djphp.core.Signals");
		$signals->connect('REQUEST_START',array(__CLASS__,'setup_signals'),__CLASS__);
		$signals->fire('REQUEST_START',kwargs('request',$request),__CLASS__);
		
		foreach(App::$settings->MIDDLEWARES as $modules){
			list($module,$klass) = module_dot_class($modules);
			import($module);
			if(is_callable(array($klass,'process_request'))) {
				$response = call_user_func(array($klass,"process_request"), $request);
				if($response instanceof HttpResponse) {
					return $response;
				}
			}
		}
		
		import("djphp.core.UrlResolver");
		$result = UrlResolver::resolve($request->path);
		
		if(!$result) {
			//Check for with /
			if(substr($request->path,-1,1) == '/') {
				$request->path = substr($request->path,0,-1);
				$result = UrlResolver::resolve($request->path);
			}
			else {
				$request->path = $request->path . '/';
				$result = UrlResolver::resolve($request->path);
			}
			
			if(!$result)
				throw new HttpException(404, "path missing from urls");
			else
				return new HttpResponse($request->path,301);
		}
		
		list($view_path,$args,$kwargs) = $result;
		
		$response = $this->call_view($request,$view_path,$args, $kwargs);
		
		foreach(array_reverse(App::$settings->MIDDLEWARES) as $modules){
			list($module,$klass) = module_dot_class($modules);
			import($module);
			
			if(is_callable(array($klass,'process_response'))) {
				$ret = call_user_func(array($klass,"process_response"), $request, $response);
				if($ret instanceof HttpResponse) {
					return $response;
				}
			}
		}
		return $response;
	
	}
	
	function extract_view_details($viewpath) {
		$parts = explode('.',$viewpath);
		$view_func = array_pop($parts);
		$view_class = array_pop($parts);
		$view_file = join('.',$parts);
		
		return array($view_file, $view_class, $view_func);
	}
	
	
	function call_view(&$request,$path, $args, $kwargs) {
		list($view_file, $view_class, $view_func) = $this->extract_view_details($path);
		
		array_unshift($args, $request);
		array_push($args, $kwargs);
		
		import($view_file);
		
		try {
			$response = call_user_func_array(array($view_class,$view_func),$args);
		}
        catch(DecoratorException $e){
            $response = $e->response;
        }
		catch(Exception $e) {
			if(method_exists($view_class,"__handle_exception")){
				$response = call_user_func(array($view_class,"__handle_exception"), $request, $e);
				if(!($response instanceof HttpResponse)) {
					throw $e;
				}
			}
			else {
				throw $e;
			}
		}
		
		if($response === NULL) {
			throw new Http404('View could not be found');
		}
		
		if(!($response instanceof HttpResponse)) {
			throw new AppException("View did not return a response object");
		}

		return $response;
	}
}
