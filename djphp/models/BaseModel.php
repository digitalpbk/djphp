<?php
class BaseModel {
	private $__caches = NULL;
	public $__initial = NULL; // used for differential updates
	
	public function save($force_insert = False) {
		$manager = getStaticProperty(get_class($this),"objects");
		return $manager->save($this,$force_insert);
	}
	
	public function delete() {
		$manager = getStaticProperty(get_class($this),"objects");
		return $manager->delete($this);
	}
	
	public function __get($name) {
		if(!isset($this->__caches[$name])){
			$manager = getStaticProperty(get_class($this),"objects");
			$fields = $manager->foreign_keys;
			if($fields && isset($fields[$name])){
				$defn = $fields[$name];
                
                if($defn instanceof ForeignKeyField){
					$this->__caches[$name] = $defn->to_php($this->{$defn->from_field}, $this);
				}

			}
			else {
				// can be a reverse thing
				if(class_exists($name)){
					$manager = getStaticProperty(get_class($this),"objects");
					$other_manager = getStaticProperty($name,"objects");
					$fields = $other_manager->foreign_keys;
					foreach($fields as $fk){
						if($fk instanceof OneToOneField){
							if($fk->to_klass == get_class($this)){
								//var_dump($other_manager,$this->{$manager->pk},$fk->from_field);
								$this->__caches[$name] =  $other_manager->get($this->{$manager->pk},$fk->from_field);
								break;
							}
						}
					}
				}

                if(strpos($name,"_set") !== FALSE) {
                    $name = substr($name,0,-4);
                    if(class_exists($name)){
					    $other_manager = getStaticProperty($name,"objects");
                        $fields = $other_manager->foreign_keys;
                        
                        foreach($fields as $fk){
                            if($fk instanceof ForeignKeyField){
                                if($fk->to_klass == get_class($this)){
                                    $this->__caches[$name] =  $other_manager->filter($fk->field,'=',$this);
                                    break;
                                }
                            }
                        }
                    }
                }
			}
		}
		return $this->__caches[$name];
	}
    
	public function __set($name, $value) {
		$this->__caches[$name] = $value;
		
		$manager = getStaticProperty(get_class($this),"objects");
		
		$fields = $manager->foreign_keys;
		if($fields && isset($fields[$name])){
			$defn = $fields[$name];
            if($defn instanceof GenericForeignKeyField) {
                $this->{$defn->klass_field} = get_class($value);
                $other_manager = getStaticProperty(get_class($value),"objects");
				$pk = $other_manager->pk;
                $this->{$defn->from_field} = $value->{$pk};
            }
			else if($defn instanceof ForeignKeyField){
                if($value) {
				    if(get_class($value) != $defn->to_klass){
					    throw new Exception('Cannot assign object of ' . get_class($value) . ' to ' . $name);
				    }
				
				    $pk = $defn->to_field();
				    $this->{$defn->from_field} = $value->{$pk};
                }
                else {
                    $this->{$defn->from_field} = NULL;
                }
			}
		}
	}
    
	public function __construct($std = NULL){
		if(is_object($std)){
			$manager = getStaticProperty(get_class($this),"objects");
			foreach($std as $var => $value){

				if(isset($manager->fields[$var])) {
					$defn = $manager->fields[$var];
					$this->{$var} = $defn->to_php($value, $this);
				}
			}
			
			$this->__initial = clone $this;
		}
	}
}

