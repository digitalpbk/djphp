<?php
class FilterEntity {
    var $field;
	var $cmp;
	var $klass;
    var $op;
    var $grp;
    var $value;
    
    function __construct($field,$cmp,&$value,$op,$child=NULL){
        $this->field = $field;
		$this->cmp = $cmp;
		$this->value = &$value;
        $this->op = $op;
        $this->child = $child;
    }
}


function Q($field,$cmp,$value,$op="AND",$child=NULL){
    if($op != "AND" && $op != "OR"){
        throw new Exception("Unknown operation $op for QuerySet");
    }
    
    return new FilterEntity($field,$cmp,$value,$op,$child);
}
class QuerySetException extends Exception {}
class QuerySetDuplicateException extends QuerySetException {}

class QuerySet implements ArrayAccess, Iterator, Countable  {
    public $limit;
    public $order_by;
    public $filters;
    public $fields;
	public $manager;
	public $foreign_keys;
    public $operation = 'SELECT';
    public $values = array();
	public $group_by;
    
    private $_qCache = array();
    private $_qPtr = 0;
    private $_qMinCount = NULL;
    private $_only = FALSE;
    private $_castable = TRUE;
    
    private $last_op;
    
    function __construct(&$manager){
		$this->manager = &$manager;
        $this->filters = 
        $this->order_by =
        $this->group_by =
        $this->limit = array();
		$this->values = array();
        $this->fields = $manager->fields;
		$this->using = 0;
    }
    
    function using($db) {
		$this->using = $db;
		return $this;
    }
    
    function only(){
        $fields = func_get_args();
        $fields_old = $this->fields;
		$this->fields = array();

		foreach($fields as $field){
            $new_field = $fields_old[$field];
            if(!$new_field)
                $new_field = $this->_resolve_field($field);
            
		    $this->fields[] = $new_field;
		}
        $this->_only = TRUE;
        return $this;
    }
    
    function filter(){
        $args = func_get_args();
        
		while ($args and count($args) > 0) {
			$arg = array_shift($args);
			
			if(!$arg instanceof FilterEntity){
				if(count($args) >= 2) {
					$arg = Q($arg,array_shift($args),array_shift($args),'AND');
				}
				else {
					throw new ArgumentError("Filter got a wierd number of arguments, Why dont you use Q(...)?");
				}
			}

            $field = $this->_resolve_field($arg->field);
            if($field instanceof ForeignKeyField) {
                if($field instanceof GenericForeignKeyField) {
                    $klass = get_class($arg->value);
                    $klass_field = $this->manager->fields[$field->klass_field];
                    $this->filters[] = Q($klass_field,'=',$klass);
                }
                else
			        $klass = $field->to_klass;

			    $other_manager = getStaticProperty($klass,'objects');
                $pk = $other_manager->pk;
                $field = $this->manager->fields[$field->from_field];
                $this->filters[] = Q($field,'=',$arg->value->{$pk});
            }
            else if($field){
                $arg->field = $field;
                $this->filters[] = $arg;
            }
		}

        return $this;
    }
	
	function _resolve_field($field,$manager = NULL){
        if(!$manager)
            $manager = $this->manager;

		if(isset($manager->fields[$field])){
            return $manager->fields[$field];
        }
        else if(isset($manager->foreign_keys[$field])){
            return $manager->foreign_keys[$field];
        }
        else {
			//Indirect fields...
            if(strpos($field,'__') === FALSE) {
                throw new QuerySetException("Unknown field $field");
            }
            $parts = explode('__',$field);
            $field = array_shift($parts);
            $other = array_shift($parts);
			$rest = join('__', $parts);

			if(!isset($manager->foreign_keys[$field])){
				throw new QuerySetException("Unknown foreign key map $field");
			}
			
			$fk = $manager->foreign_keys[$field];
			
			$klass = $fk->to_klass;
			$other_manager = getStaticProperty($klass,'objects');

            $to_field = $other_manager->fields[$fk->to_field()];
            $field = $manager->fields[$fk->from_field];

            if(!isset($this->foreign_keys[$klass])) {
                $this->foreign_keys[$klass] = $fk->field;
                $this->filters[] = Q($field,'=',$to_field);
            }
            
			if(!isset($other_manager->fields[$other])){
                if($rest){
                    return $this->_resolve_field($other.'__'.$rest,$other_manager);
                }
                else
				    throw new QuerySetException("Unknown foreign key map $other");
			}
			else {
                return $other_manager->fields[$other];
            }
		}
    }
    
    function order_by(){
		$fields = func_get_args();
		
		foreach($fields as $field){
			$order = 'ASC';
			if(substr($field,0,1) === "-"){
				$order = 'DESC';
				$field = substr($field,1);
			}
			
			$field = $this->_resolve_field($field);
			$this->order_by[] = array($field,$order);
		}
		
        return $this;
    }

    function group_by(){
		$fields = func_get_args();

		foreach($fields as $field){
			$field = $this->_resolve_field($field);
			$this->group_by[] = $field;
		}

        return $this;
    }
    
    function limit($start,$count=NULL) {
        if($count){
            $count = intval($count);
        }
        $start = intval($start);
        
        if($start || $count) {
            if($count){
                $this->limit = array($start,$count);
                $this->_qMinCount = $start+$count;
            }
            else {
                $this->limit = array(0,$start);
                $this->_qMinCount = $start;
            }
        }
        else {
            throw new Exception("Limits are not valid");
        }
        return $this;
    }
	
	function select_related(){
		$fks = func_get_args();
		
		foreach($fks as $field){
			if(!isset($this->manager->foreign_keys[$field])){
				throw new QuerySetException("Unknown foreign key map $field");
			}
			
			$fk = $this->manager->foreign_keys[$field];
			$klass = $fk->to_klass;
			$other_manager = getStaticProperty($klass,'objects');
			
			foreach($other_manager->fields as $fk_field)
				$this->fields[] = $fk_field;
				
			if(!isset($this->foreign_keys[$klass])) {
				$this->foreign_keys[$klass] = $fk->field;
				$field = $this->manager->fields[$fk->from_field];
                $pk = $other_manager->fields[$fk->to_field()];
				$this->filters[] = Q($field,'=',$pk);
			}
		}
		return $this;
	}
    
    function delete(){
        $this->operation = 'DELETE';
        //Warning delete
		$connection = $this->get_connection();
        $result = $connection->query($this,FALSE);
		return $connection->affected_rows;
    }
	
	function set_values($values){
        $this->values = $values;
		return $this;
	}
    
    function insert($values = NULL, $delayed = False) {
        if($values) {
            $this->set_values($values);
        }
        if($delayed)
		    $this->operation = 'INSERT DELAYED';
        else
            $this->operation = 'INSERT';
		$connection = $this->get_connection();
        try{
            $result = $connection->query($this,FALSE);
        }
        catch(DBException $e) {
            if($e->error_code == 1062){
                if(PHP_VERSION_ID > 50300)
                    throw new QuerySetDuplicateException(NULL,NULL,$e); // 5.3
                throw new QuerySetDuplicateException("Duplicate Exception",NULL);
            }
            else {
                throw $e;
            }
        }
		return $connection->insert_id;
    }
    
    function update($values = NULL) {
        if($values) {
            $this->set_values($values);
        }

		$this->operation = 'UPDATE';
		$connection = $this->get_connection();
        $result = $connection->query($this,FALSE);
    }

    function distinct(){
        $this->operation = 'SELECT DISTINCT';
    }

    function annotate() {
        $obj = func_get_args();
        if(count($obj) == 1){
            $obj = $obj[0];
        }
        
        $args = NULL;
        if($obj instanceof Argument) {
            $args = $obj->get_all();
            foreach($args as $key=>$o){
                $o->alias = $key;
            }
        }
        else if(is_array($obj)) {
            $args = array();
            foreach($obj as $o) {
                $o->alias = $o->type.'__'.$o->field;
                $args[] = $o;
            }
        }
        else {
            $args = array($obj);
            $obj->alias = $obj->type.'__'.$obj->field;
        }

        foreach($args as $agg){
            $agg->field = $this->_resolve_field($agg->field);
        }


        $this->fields = $args;
        $this->_castable = FALSE;
        return $this;
    }

    function aggregate() {
        $args = func_get_args();
        call_user_func_array(array($this,'annotate'),$args);
        return $this->evaluate();
    }

    public function evaluate(){
        $connection = $this->get_connection();
        $result = $connection->query($this,TRUE);
        $row = $connection->fetch($result);
		$connection->free($result);
        return $row;
    }

    function __toString(){
        return $this->render();
    }
    
    function copy(){
        return clone $this;
    }
    
    
    function setMinCount($count){
        if($this->_qMinCount === NULL){
            $this->_qMinCount = $count;
        }
        
        if($count < $this->_qMinCount){
            $this->_qMinCount = $count;
        }
    }
    /*Implementation of interfaces*/
    public function offsetSet($offset,$value) {
        throw new Exception("Cannot set to QuerySet");
    }

    public function offsetExists($offset) {
        $obj = $this->offsetGet($offset);
        return $obj !== NULL;
    }

    public function offsetUnset($offset) {
        throw new Exception("Cannot unset in QuerySet");
    }

    public function offsetGet($offset) {
        
        if($this->_qMinCount !== NULL && $offset >= $this->_qMinCount)
            return NULL;
        
        if(isset($this->_qCache[$offset])){
            return $this->_qCache[$offset];
        }
        
        $this->load($offset);
        if(isset($this->_qCache[$offset])){
            return $this->_qCache[$offset];
        }
        
        $this->setMinCount($offset);
        return NULL;
    }

    public function rewind() {
        $this->_qPtr = 0;
    }

    public function current() {
        if(isset($this->_qCache[$this->_qPtr])){
            return $this->_qCache[$this->_qPtr];
        }
    }

    public function key() {
        return $this->_qPtr;
    }

    public function next() {
        $this->_qPtr++;
        return $this->offsetGet($this->_qPtr);
    }

    public function valid() {
        return ($this->offsetGet($this->_qPtr) !== NULL);
    }   

    function count($field=NULL){
        $qs = clone $this;
        if(!$field)$field = $this->manager->pk;
        $qs->fields = array($this->manager->fields[$field]);
        $qs->limit = '';
        $qs->order_by = array();
        $qs->operation = 'COUNT';
        
		$row = $qs->evaluate();
        return $row->count;
    }
    
    /*over*/
    
    function load($start=0,$limit=100){
        if(empty($this->limit)){
            $this->limit($start,$limit);
        }
        else {
            list($oldstart,$oldlimit) = $this->limit;
            #echo "=============> ". $oldstart." ".$oldlimit." ".$start." ".$limit;
            if($oldstart+$oldlimit <= $start){
                return NULL;
            }
        }
        
        $i=$start;
        
        $connection = $this->get_connection();
		$result = $connection->query($this,TRUE); // Use and throw
		
        while($row = $connection->fetch($result)){
            $this->_qCache[$i] = $this->_castable?$this->cast($row):$row;
            $i++;
        }
		
		$connection->free($result);
		
        if($i < ($this->limit[0] + $this->limit[1]))
            $this->setMinCount($i);
        
        return $this->_qCache;
    }
    
    function get_connection(){
        $connections = import("djphp.db.Connection");
		if($this->using === 0) {
			/* Router */
			if(App::$settings->DATABASE_ROUTER){
				list($module, $klass) = module_dot_class(App::$settings->DATABASE_ROUTER);
				$router = import($module);
				$this->using = call_user_func(array($klass,'route'), $this);
			}
		}
		
		return $connections[$this->using];
    }
        
    function render() {
		$connection = $this->get_connection();
		return $connection->render($this);
    }
    
	function cast($obj){

		if(is_array($obj)) {
			$return = new $this->manager->klass($obj[$this->manager->klass]);
			
			foreach($obj as $klass => $o) {
				if(isset($this->foreign_keys[$klass])) {
					$fkf = $this->foreign_keys[$klass];
					$return->{$fkf} = new $klass($o);
				}
			}
			return $return;
		}
		else {
			return new $this->manager->klass($obj);
		}
	}
    
}