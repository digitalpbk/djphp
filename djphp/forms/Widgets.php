<?php
import('djphp.templates.FFX');

abstract class Widget {
    public $attrs;
	public $formfield;
	public $prefix;
    
    function __construct($args = null){
		$attributes = array();
		if($args) {
			$attributes = $args->get('attrs',array());
        }
		
        $this->attrs = $attributes;
        $this->value = NULL;
        $this->_errors = NULL;
        $this->prefix = NULL;
    }
    
    function renderAttributes(){
        $arr = array();

        $name = $this->get_name();
		if(!isset($this->attrs['id'])) {
			$this->attrs['id'] = 'id_' . $name;
		}

        if($this->formfield->required) {
            $this->addClass("required");
        }
	
        foreach($this->attrs as $k => $v){
            $arr[] = FFX::safe($k).'="'.FFX::safe($v).'"';
        }
        
        return join(' ',$arr);
    }

    function get_name() {
		if(!isset($this->attrs['name'])) {
			$this->attrs['name'] = isset($this->prefix) ? $this->prefix . '_' . $this->formfield->field : $this->formfield->field ;
		}

        return $this->attrs['name'];
    }

    function get_label_target(){
        if(!isset($this->attrs['id'])) {
			$this->attrs['id'] = 'id_' . $this->get_name();
		}
        return $this->attrs['id'];
    }

    function addClass($class) {
        if(!isset($this->attrs['class'])) {
            $this->attrs['class'] = $class;
        }
        else {
            $this->attrs['class'] .= ' ' . $class;
        }
    }
    
    function renderValue($value) {
        return FFX::safe($value);
    }
    
    function render(){
        throw new Exception("Not implemented");
    }
       
    function __toString(){
        return $this->render();
    }
}

class TextWidget extends Widget {
    function render($value){
        if(isset($this->formfield->max_length) && !isset($this->attrs['length'])) {
            $this->attrs['maxlength'] = $this->formfield->max_length;
        }
    	return '<input type="text" '.$this->renderAttributes().' value="'.$this->renderValue($value).'"/>';
    }
}

class HiddenWidget extends Widget {
	function render($value){
    	return '<input type="hidden" '.$this->renderAttributes().' value="'.$this->renderValue($value).'"/>';
    }
}

class PasswordWidget extends Widget {
	function render($value){
    	return '<input type="password" '.$this->renderAttributes().' value=""/>';
    }
}

class TextareaWidget extends Widget {
	function render($value){
    	return '<textarea '.$this->renderAttributes().'>'.$this->renderValue($value).'</textarea>';
    }
}

class CheckWidget extends Widget {
	function render($value){
		$output  = '';
		$output .= '<input type="checkbox" '.$this->renderAttributes().($value?"checked":""). ' value="1"/>';
		return $output;
    }
}

class SelectWidget extends Widget {
	public $choices;
	
    function renderOptions($value){
        $str = '';

        foreach($this->choices as $k => $val){
            $selected = '';
            if($k == $value){
                $selected = 'selected ';
            }
            $str .= '<option '.$selected.'value="'.$k.'">'.$val.'</option>';
        }
        
        return $str;
    }

    function render($value){
    	return '<select '.$this->renderAttributes().'>'.$this->renderOptions($value).'</select>';
    }

}

class MultiSelectWidget extends Widget {
	public $choices;

    function renderOptions($value){
        $str = '';

        foreach($this->choices as $k => $val){
            $selected = '';
            if(is_array($value) && in_array($k,$value)){
                $selected = 'selected ';
            }
            $str .= '<option '.$selected.'value="'.$k.'">'.$val.'</option>';
        }

        return $str;
    }

    function render($value){
    	return '<select multiple="multiple" '.$this->renderAttributes().'>'.$this->renderOptions($value).'</select>';
    }

    function get_name() {
        return $this->attrs['name'] = parent::get_name().'[]';
    }
}
