<?
import('djphp.cache.apccache');
import('Url');

class UrlResolver {
	public static $urls;
	
	public static function resolve($path){
		if(!self::$urls) {
			/* //Disabling APC caching for root url array
			 *
			 * $urls = ApcCache::get('ROOT_URLS');
			if(!$urls || App::$settings->DEBUG) {
				$urls = import(App::$settings->ROOT_URLS);
				ApcCache::set('ROOT_URLS', $urls);
			}*/
		    $urls = import(App::$settings->ROOT_URLS);
			self::$urls = $urls;
		}
		return self::_resolve(self::$urls,$path);
	}
	
	private static function _resolve($urls,$path,$prefix = NULL){
		foreach($urls as $regex => $defn){
			if($regex !== 0) {
				if(($pos = strpos($regex,'#')) !== FALSE){
					$regex = substr($regex,0,$pos);
				}
						
				if($defn instanceof Url) {
					if(preg_match("#$regex#",$path,$matches)) {
						$view = $defn->view;
						array_shift($matches);
						$args = array();
						$kwargs = $defn->kwargs;
						if(!$kwargs){
							$kwargs = kwargs();
						}
						$last_match = FALSE;
						
						foreach($matches as $key => $match){
							if(is_numeric($key)) {
								if($match !== $last_match)
									$args[] = $match;
							}
							else {
								$kwargs->set($key,$match);
								$last_match = $match;
							}
						}
						
						if(isset($urls[0])){
							$view = $urls[0].'.'.$view;
						}
						
						return array($view,$args,$kwargs);
					}
				}
				else{
					if(empty($regex) || strpos($path,$regex) === 0){
						
						$new_path = substr($path,strlen($regex));
						
						$result = self::_resolve($defn, $new_path, $prefix.$regex);
						if($result)
							return $result;
					}
				}
			}
		}
	}
	
	private static function  _construct_routes(&$routes,$urls,$prefix = NULL) {
		foreach($urls as $regex => $defn){
			if($regex !== 0) {
				if(($pos = strpos($regex,'#')) !== FALSE){
					$regex = substr($regex,0,$pos);
				}
				
				if($defn instanceof Url) {
					if(substr($regex,0,1) == '^') $regex = substr($regex,1);
					if(substr($regex,-1,1) == '$') $regex = substr($regex,0,-1);
					
					$routes[$defn->name] = $prefix . $regex;
				}
				else{
					self::_construct_routes($routes, $defn, $prefix . $regex);
				}
			}
		}
	}
	
	public static function reverse(){
		static $_cache = NULL;
		static $routes = NULL;
		
		if($_cache === NULL)
			$_cache = array();
		
		$args = func_get_args();

		if($args && isset($args[1]) && ($args[1] instanceof Argument)) {
			$key = $args[1]->hash();
		}
		else { // means kwargs
			$key = implode("/",$args);
		}
		
		if(isset($_cache[$key]))
			return $_cache[$key];
		
		$name = array_shift($args);
		
		if($routes === NULL) {
			$routes = ApcCache::get('ROOT_URLS_REVERSE');
			if(!$routes || App::$settings->DEBUG) {
				self::_construct_routes($routes,self::$urls);
				ApcCache::set('ROOT_URLS_REVERSE', $routes);
			}
		}
		
		if(isset($routes[$name])){
			$route = $routes[$name];
			$regex = $route;
			
			if($args) {
				if($args[0] instanceof Argument){ // means kwargs
					self::_prg_res_cb_named(NULL,$args[0]);
					$regex = preg_replace_callback("/\(\?P\<([^>]+)\>.*?\)/",array(__CLASS__,'_prg_res_cb_named'),$regex);
				}
				else {
					self::_prg_res_cb(NULL,$args);
					$regex = preg_replace_callback("/\(.*?\)/",array(__CLASS__,'_prg_res_cb'),$regex);
				}
				
			}
			
			$_cache[$key] = $regex;
			return $_cache[$key];
			//return ANSWERS_BASE.'/'.$regex;
		}
		else{
			throw new Exception("Reverse not found for " . $name ." with args ".var_export($args,TRUE));
		}
	}
	
	static function _prg_res_cb($matches=NULL,$args=NULL){
		static $i;
		static $_args = NULL;
		if($args) { $_args = $args; $i=0; }
		if($matches)
			return $_args[$i++];
		return NULL;
	}
	
	static function  _prg_res_cb_named($matches=NULL,$kwargs=NULL){
		static $_kwargs = NULL;
		if($kwargs) { $_kwargs = $kwargs;}
		if($matches) {
			return $_kwargs->get($matches[1]);
		}
		return NULL;
	}
}



