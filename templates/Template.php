<?php
class TemplateNotFound extends Exception {}
class Template {
	public $instance;
	private $blockContents;
	private $blockStack;
	public $extendsFile;

	function __construct(){
		$this->blockContents = array();
		$this->blockStack = array();
		$this->extendsFile = NULL;
		$this->instance = NULL;
	}
	
	public function extend($master) {
		$this->extendsFile = $master;
	}

	public function block($blockName){
		array_push($this->blockStack,$blockName);
		if(isset($this->blockContents[$blockName])) {
			return FALSE;
		}
		ob_start();
		return TRUE;
	}

    public function block_super() {
        // TODO
    }
	
	public function endblock($blockName=NULL, $is_master = FALSE){
		$blockName = array_pop($this->blockStack);
		
		if($this->extendsFile && $is_master == FALSE){
			if(!isset($this->blockContents[$blockName])) {
				$all_blocks = ob_get_contents();	 
				$this->blockContents[$blockName] = $all_blocks;
				ob_end_clean();
			}
		}
		else { //If master
			if(isset($this->blockContents[$blockName])) {
				echo $this->blockContents[$blockName];
                unset($this->blockContents[$blockName]);
		 	}
		 	else {
		 		ob_end_flush();	 
		 	}
		}
	}
	
	function set_template_dirs() {
		$dirs = array();
		$old_dirs = explode(PATH_SEPARATOR,get_include_path());
		
		$dirs[] = App::$settings->PROJECT_ROOT . DIRECTORY_SEPARATOR . App::$settings->TEMPLATE_FOLDER;
		
		foreach($old_dirs as $dir) {
			foreach(App::$settings->INSTALLED_APPS as $app) {
				$directory = $dir . DIRECTORY_SEPARATOR . import_translate($app)  . DIRECTORY_SEPARATOR . App::$settings->TEMPLATE_FOLDER;
				if(file_exists($directory)){
					$dirs[] = $directory;
				}
			}
			$dirs[] = $dir;
		}
		
		$dirs[] = App::$djphp_path;
		
		return join(PATH_SEPARATOR,$dirs);
	}
	
	static function append_process_context(&$data) {
		$extra = array();
		if(!is_array($data))
			$data = array();
		
		foreach(App::$settings->CONTEXT_PROCESSORS as $modules){
			
			if(is_array($modules)) {
				$class = $modules[0];
				$func = $modules[1];
			}
			else {
				$class = $modules;
				$func = "get_context";
			}
			
			list($module, $klass) = module_dot_class($class);
			import($module);
			
			$append = call_user_func(array($klass,$func), $data);
			
			if(is_array($append)) {
				$extra = array_merge($extra,$append);
			}
		}
		
		$data = array_merge($data, $extra);
	}
	
	static function render_to_string($file,&$data,$dirs = NULL) {

	    self::append_process_context($data);
	    
	    if(!$dirs)
		    $dirs = self::set_template_dirs();
	    
	    $old = set_include_path($dirs);
		import("djphp.templates.FFX");
		try{
			$content = self::_render_to_string($file,$data);
		}
		catch(Exception $e){
			set_include_path($old);
			throw $e;
		}
		
	    return $content;
	}
	
	static function _render_to_string($file,&$data){
	    static $template = NULL;
	    if($template === NULL) //
		$template = new Template(); 
	    
	    $template->extendsFile = NULL;
	    ob_start();
	    extract($data);
	    
	    try {
		$ret = include($file);
	    }
	    catch (Exception $e){
		ob_end_clean();
		throw $e;
	    }
	    
	    if($ret === FALSE) {
		    throw new TemplateNotFound("Template $file not found");
	    }
	    
	    if($template->extendsFile){
            ob_end_clean();
            return self::_render_to_string($template->extendsFile,$data);
	    }
	    else {
		$op = ob_get_contents();
		ob_end_clean();
		return $op;
	    }
	}
}
