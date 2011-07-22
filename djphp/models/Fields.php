<?php
define('DB_DATE_FORMAT','Y-m-d H:i:s');

class ModelField {
    public $field;
    public $primary = NULL;
	public $auto_increment = NULL;
    public $klass = NULL;
	public $null = TRUE;
	public $default = NULL;
	
    public function __construct($args) {
		if(!$args instanceof Argument){
			throw new Exception("Args must be kwargs");
		}
        $this->field = $args->get_required('field');
        $this->primary = $args->get('primary',NULL);
		$this->klass = $args->get('klass',NULL);
		$this->auto_increment = $args->get('auto_increment',$this->primary?TRUE:FALSE);
		$this->null = $args->get('null',TRUE);
		$this->default = $args->get('default',NULL);
    }
    
    function to_php($db_value,&$instance) { return $db_value; }
    function before_save($value,&$instance,$create = FALSE) { return $value; }
	function get_default() {
		return  $this->default;
	}

    function get_db_prep_lookup($value,$driver){
        return $value;
    }

    function get_db_prep_value($value,$driver){
        return $value;
    }
}

class DateTimeField extends ModelField{
	public $auto_now;
	
    function to_php($db_value,&$instance) {
        return new DateTime($db_value);
    }
    
    function before_save($date,&$instance,$create = FALSE) {
		if($this->auto_now) {
			return new DateTime();
		}
        return $date;
    }

    function get_db_prep_value($date,$driver){
		if($date instanceof DateTime)
			return $date->format(DB_DATE_FORMAT);
		return NULL;
    }

    function get_db_prep_lookup($date,$driver) {
        return self::get_db_prep_value($date, $driver);
    }

	function get_default(){
		if($this->default == 'NOW')
			return new DateTime();
		return NULL;
	}
	
	function __construct($args) {
        $this->auto_now = $args->get('auto_now',FALSE);
        parent::__construct($args);
    }
}

class ForeignKeyField extends ModelField{
    public $from_field;
	public $to_klass;
    public $to_field;
    
    function __construct($args) {
        $this->to_field = $args->get('to_field',NULL);
        $this->to_klass = $args->get_required('to_klass');
		$this->from_field = $args->get_required('from_field');

        parent::__construct($args);
    }

    function to_field() {
        if(!$this->to_field) {
            $objects = getStaticProperty($this->to_klass,'objects');
			return $objects->pk;
		}
        return $this->to_field;
    }
    
    function to_php($db_value, &$instance) {
        $objects = getStaticProperty($this->to_klass,'objects');
        $this->to_field = $this->to_field();
			
		if($db_value)
			return $objects->get($db_value,$this->to_field);
		return NULL;
    }
    
    function get_db_prep_lookup($object, $driver) {
        return NULL;
    }

    function get_db_prep_value($date,$driver) {
        return NULL;
    }
}

class OneToOneField extends ForeignKeyField {}

class GenericForeignKeyField extends ForeignKeyField {
    public $klass_field;
    
    function __construct($args) {
        $this->klass_field = $args->get_required('klass_field');
		$args->set('to_klass','generic');
		
        parent::__construct($args);
    }

    function to_php($db_value, &$instance) {
        $this->to_field = NULL;
        $this->to_klass = $instance->{$this->klass_field};
        return parent::to_php($db_value,$instance);
    }
    
    function get_db_prep_lookup($date,$driver) { 
        return NULL;
    }

    function get_db_prep_value($date,$driver) {
        return NULL;
    }
}


function longval($num){
	if(is_numeric($num)) return $num;
	return 0;
}

class NumericField extends ModelField {
    function __construct($args) {
        parent::__construct($args);
    }

    function get_db_prep_lookup($value,$driver) {
        return longval($value);
    }

    function get_db_prep_value($value,$driver) {
        return longval($value);
    }
}


class FloatField extends ModelField {
    function get_db_prep_lookup($value,$driver) {
        return floatval($value);
    }

    function get_db_prep_value($value,$driver) {
        return floatval($value);
    }
}

class StringField extends ModelField {
	
    function __construct($args) {
        parent::__construct($args);
    }
}

class BlobField extends ModelField {
    function __construct($args) {
        parent::__construct($args);
    }
}

class EnumField extends StringField {
    public $choices;
	
	function __construct($args) {
		$this->choices = $args->get_required('choices');
		
        parent::__construct($args);
    }

    function before_save($value, &$instance,$create = FALSE) {
        if(!in_array($value,array_keys($this->choices))){
            throw new Exception("Invalid ENUM choice");
        }
        return parent::before_save($value,$instance,$create);
    }
}

class AutoField extends NumericField {
	function __construct($args) {
        $args->set('primary',TRUE);
		$args->set('auto_increment',$args->get('auto_increment',TRUE));
		parent::__construct($args);
    }
}

class BooleanField extends ModelField {
    function to_php($db_value,&$instance) {
        return $db_value?TRUE:FALSE;
    }

    function get_db_prep_lookup($value,$driver) {
        return $value?1:0;
    }

    function get_db_prep_value($value,$driver) {
        return $value?1:0;
    }
    
}