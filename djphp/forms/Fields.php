<?php
import("Widgets");

class FormField {
	public $label;
	public $help_text;
	public $field;
	public $value;
	public $widget;
	public $required;
    public $default;
	
	public function __construct($args){
		$this->field = $args->get_required('field');
		$this->label = $args->get('label', $this->default_label());
		$this->help_text = $args->get('help_text',NULL);
		$this->widget->formfield = $this;
		$this->required = $args->get('required',TRUE);
        $this->default = $args->get('default');
        $this->value = $this->default;
	}
	
	function default_label(){
		return ucwords(str_replace('_',' ',$this->field));
	}
	
	function validate(){
        if( ($this->required) &&  empty($this->value) && (strlen($this->value)==0)  ) {
            throw new ValidationError('This field is required');
        }
	}

    function clean($value) {
        return $value;
    }

	/*error handling*/
	private $_errors;
	
    function __get($name){
    	if($name == "errors"){
            return $this->render_errors();
    	}
    	if($name == "label_html"){
            return $this->render_label();
    	}
    	return NULL;
    }
    
    function setError($errors){
    	$this->_errors = $errors;
    }
    
	function render_errors(){
		if($this->_errors){
			return $this->_errors;
		}
	}
		
	function render_widget(){
		return $this->widget->render($this->value);
	}
	
	function render_label(){
        if($this->label) {
            $classes = array();
            if($this->required){
                $classes[] = 'required';
            }
            if($this->_errors){
                $classes[] = 'error';
            }
            if($classes)
                $class = 'class="'.join(' ',$classes).'" ';
            else
                $class = '';

            return '<label '.$class.'for="'.$this->widget->get_label_target().'">'. $this->label . '</label>';
        }
	}

}

class CharFormField extends FormField {
    public $max_length = NULL;
    public $min_length = NULL;
    
	public function __construct($args) {
		$this->widget = $args->get('widget',new TextWidget());
		parent::__construct($args);

        $this->max_length = $args->get('max_length');
        $this->min_length = $args->get('min_length');
	}

    public function validate(){
        parent::validate();

        if($this->max_length > 0 && strlen($this->value) > $this->max_length) {
            throw new ValidationError('Maximum ' . $this->max_length .' characters','MAX_LENGTH');
        }

        if($this->min_length > 0 && strlen($this->value) < $this->min_length) {
            throw new ValidationError('Minimum ' . $this->min_length . ' characters is required','MIN_LENGTH');
        }
    }
}

class ChoiceFormField extends FormField {
	public $choices;
	
	public function __construct(Argument $args) {
		$this->widget = $args->get('widget',new SelectWidget());
		$this->choices = $this->widget->choices = $args->get_required('choices');
		parent::__construct($args);
	}

    public function validate(){
        parent::validate();

        if(!in_array($this->value,array_keys($this->choices))){
            throw new ValidationError("Value not in choices",'INVALID_CHOICE');
        }
    }
}


class EmailFormField extends CharFormField {
    function validate(){
		parent::validate();
        
        if(!filter_var($this->value, FILTER_VALIDATE_EMAIL)){
            throw new ValidationError("Please enter a valid email address");
        }
    }
}

class BoolFormField extends FormField {
    function clean($value){
        return (isset($value) && $value)?TRUE:FALSE;
    }

    public function __construct(Argument $args) {
        $this->widget = $args->get('widget',new CheckWidget());
        parent::__construct($args);
    }
}

