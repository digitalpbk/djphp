<?php
import("djphp.db.QuerySet");

class DoesNotExist extends Exception{
    public $class;
    public function __construct($message, $class = NULL){
        parent::__construct($message);
        $this->class = $class;
    }
}

class BaseManager  {

	public $table;
	public $fields;
	public $foreign_keys;
	public $pk = 'id';
	public $klass; //Model Class actually
	
	private static $utf8Init = FALSE;

	function __construct($class){

		$this->klass = $class;

		$reflection = new ReflectionClass($class);
        $props = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
		
		$this->fields = array();
		$fields_array = getStaticProperty($reflection, "fields");
		$fields_map = array();
		foreach($fields_array as $field) {
			$fields_map[$field->field] = $field; 	
			$field->klass = $class;
			if($field instanceof ForeignKeyField){
				$this->foreign_keys[$field->field] = $field;
			}
		}
		
		foreach($props as $prop){
			if(!$prop->isStatic()) {
				$var = $prop->getName();
				if(substr($var,0,1) != "_"){ //skip underscore variables and defined fields
					$defn = isset($fields_map[$var])?$fields_map[$var]:new ModelField(kwargs('field',$var,'klass',$class));
					if(isset($defn->primary)) {
						$this->pk = $var;
					}

                    if(!$defn instanceof ForeignKeyField)
					    $this->fields[$var] = $defn;
				}
			}
		}
		
		$this->table = getStaticProperty($reflection, "table");
	}
    
	public function get($id,$field = NULL)
	{	
		$qs = $this->_qs();
		if(!$field) {
			$field = $this->pk;
		}
		
		$obj = $qs->filter(Q($field,'=',$id))->limit(1);
		$obj = $obj->load();

		if(!isset($obj[0])){
			throw new DoesNotExist($this->klass .' object with ' . $field . ' = ' . $id. ' does not exist', $this->klass);
		}
		
		
		return $obj[0];
	}
    
	public function save(&$obj,$force_insert=False,$delayed = FALSE)
    {
		// TODO : Call presave and postsave signals
		$signals = import("djphp.core.Signals");
		
        $pk = $this->pk;
    	
    	if($obj->{$pk} === NULL || $force_insert){
            $pk_field = $this->fields[$pk];
            
			if($force_insert && $pk_field->auto_increment){
				$obj->{$pk} = NULL;
			}
			$signals->fire('PRESAVE',kwargs('object',$obj,'created',TRUE),$this->klass);
			$qs = $this->_qs();
			
			$values = array();
			foreach($this->fields as $field => $defn){
				$value = $obj->{$field};

				if($defn->default && $value === NULL){
					$obj->{$field} = $value = $defn->get_default();
				}

				$value = $defn->before_save($value, $obj,TRUE);

				if($defn->null === FALSE && $value === NULL){
					throw new Exception($field . ' cannot be null');
				}
				
				$values[$field] = $value;
			}

            $id = $qs->insert($values,$delayed);
			

			if($pk_field->auto_increment){
				$obj->{$pk} = $id;
			}
			
			$obj->__initial = clone $obj;
			$signals->fire('POSTSAVE',kwargs('object',$obj,'created',TRUE),$this->klass);
    	}
    	else {
    		$signals->fire('PRESAVE',kwargs('object',$obj,'created',FALSE),$this->klass);
			
			$values = array();
			foreach($this->fields as $field => $defn){
				if($obj->{$field} !== $obj->__initial->{$field}){
					$values[$field] = $defn->before_save($obj->{$field}, $obj,FALSE);
					if($defn->null === FALSE && $values[$field] === NULL){
						throw new Exception($field . ' cannot be null');
					}
				}
			}


			if($values) {
				$qs = $this->_qs();
				$pk = $this->pk;

				$qs->filter(Q($pk,'=',$obj->{$pk}))->limit(1)->update($values);
				$obj->__initial = clone $obj;
				
				$signals->fire('POSTSAVE',kwargs('object',$obj,'created',FALSE),$this->klass);
			}
    	}
		
		
    	return $obj->{$pk};
    }
    
	function delete($obj,$field = NULL) {
		if(!$field)
			$field = $this->pk;
		$qs = $this->_qs();
		return $qs->filter(Q($field,'=',$obj->{$field}))->limit(1)->delete();
	}
	
    public function filter(){
        $qs = $this->_qs();
        $func_args = func_get_args();
        return call_user_func_array(array($qs,"filter"),$func_args);
    }
   
    private function _qs(){
        return new QuerySet($this);
    }
    
	public function all() {
    	return $this->_qs();
    }

    public function aggregate(){
        $qs = $this->_qs();
        $func_args = func_get_args();
        return call_user_func_array(array($qs,"aggregate"),$func_args);
    }
	
	public function count() {
		return $this->_qs()->count();
	}
    
    function from_result($result){
        $arr = array();

        while($row = $result->fetchRow(DB_FETCHMODE_OBJECT)){
            $arr[] = $this->cast($row);
        }
        return $arr;
    }
   
    
	/*function from_array($args) {
        if(count($args) == 0) return NULL;
        $arg = $args[0];
        $obj = new $this->klass();
        if(is_array($arg)){
            foreach($this->obj as $var => $value){
                if(isset($arg[$var])){
                    $obj->{$var} = $arg[$var];
                }
            }
        }
        
        return $obj;
    }*/
   
    
	
}
// vim: tabstop=4 softtabstop=4 shiftwidth=4 noexpandtab ai
