<?php
class Url {
	public $view;
	public $name;
	public $kwargs;
	
	function __construct($view, $name, $kwargs = NULL){
		$this->view = $view;
		$this->name = $name;
		$this->kwargs = $kwargs;
	}
}

function patterns($array){
	if(isset($array[0])){
		$prefix = $array[0];
		unset($array[0]);
		$new_array = array();
		foreach($array as $key => $value){
			$value->view = $prefix . '.' . $value->view;
			$new_array[$key.'#'.$prefix] = $value;
		}
		return $new_array;
	}
	else {
		return $array;
	}
}