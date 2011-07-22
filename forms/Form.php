<?php

class Form {
    public $_fields;
    public $_errors;
    public $values;
    public $cleaned_data;
    
    function __construct($values = NULL,$prefix=NULL){
		$this->_fields = $this->values = $this->_errors = $this->cleaned_data = array();
		$this->values = $values;
		$fields = getStaticProperty(get_class($this),"fields");
		foreach($fields as $field){
			$this->_fields[$field->field] = $field;
			$this->_fields[$field->field]->widget->prefix = $prefix;
		}

		$this->construct_fields($values);
    }
    
    function construct_fields($values){
        foreach($this->_fields as $field => $formfield){
            if(isset($values[$field])) {
                $val = $values[$field];
			    $formfield->value = $val;
            }
        }

    }

    function set_error($value) {
        $this->_errors['__all__'][] = $value;
    }
    
    function __get($name) {
        
        if(isset($this->_fields[$name])){
            return $this->_fields[$name]->render_widget();
        }
        
        if($name == "_all_errors"){
        	return isset($this->_errors["__all__"])?$this->_errors["__all__"]:NULL;
        }
        
        if(strpos($name,'__')!==FALSE){
        	$parts = explode('__',$name);
        	
        	if(isset($this->_fields[$parts[0]])){
        		if($parts[1] == 'label') {
                    $parts[1] = 'label_html';
                }
                return $this->_fields[$parts[0]]->{$parts[1]};
        	}
        
        }
        
        return NULL;
    }
    
    function is_valid(){
        foreach($this->_fields as $field => $formfield){
            $cleaned = $this->values[$field];
            
            try{
                $formfield->value = $cleaned = $formfield->clean($cleaned);
                $formfield->validate();
				
				if(method_exists($this,'clean_'.$field)){
					$ret = $this->{"clean_$field"}($cleaned);
                    if($ret !== NULL)
                        $cleaned = $ret;
				}
			}
			catch(ValidationError $e){
				$this->_errors[$field] = $e->getMessage();
				$this->_fields[$field]->setError($e->getMessage());
                continue;
			}

            $this->cleaned_data[$field] = $cleaned;
        }
        
        if(count($this->_errors)==0)
        {
            if(method_exists($this,'clean')){
                try{
                    $ret = $this->{"clean"}($this->cleaned_data);
                    if($ret !== NULL){
                        $this->cleaned_data = $ret;
                    }
                }
                catch(ValidationError $e){
                    $this->_errors['__all__'][] = $e->getMessage();
                }
            }
        }
        else {
            //$this->cleaned_data = array();
        }
        return ((count($this->_errors)==0)?true:false);
    }
    

	function as_table(){
		$html = '';
		foreach($this->_fields as $field){
			$widget = $field->render_widget();
			$label = $field->render_label();
			$errors = $field->render_errors();
			if($widget) {
				$html .= '<tr><td>'.$label.'</td><td>'.$widget.$errors.'</td></tr>'."\n";
			}
		}
		return $html;
	}
}

class ValidationError extends Exception{
    public $code;

    function __construct($message, $code = NULL, $previous = NULL) {
        $this->code = $code;
        parent::__construct($message);
    }
}
