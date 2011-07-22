<?
import("Form");

class ModelForm extends Form {
    public $instance;
    
    function __construct($instance,$values = NULL,$prefix=NULL)
    {
        if(!$values){
            $values = array();
        }
        
        $this->instance = $instance;
        
        if($instance)
            foreach($instance as $var => $a){
                if(!isset($values[$var])){
                    $values[$var] = $a;
                }
            }
        
        parent::__construct($values,$prefix);
    }
    
    
    function is_valid(){
        $res = parent::is_valid();
        if($res){
            foreach($this->instance as $var => $value){
                if(isset($this->cleaned_data[$var])){
                    $this->instance->{$var} = $this->cleaned_data[$var];
                }
            }
        }
        return $res;
    }
}
