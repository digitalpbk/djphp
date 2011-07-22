<?php
class MysqliResult {
	private $result;
	private $map;
	public $renderer;
	
	function __construct($res,$map,$ren){ 
		$this->result = $res;
		$this->map = $map;
		$this->renderer = $ren;
	}
	function __destruct() {
		unset($this->result);
		unset($this->map);
		unset($this->renderer);
	}
	
	function fetch_object(){
		$obj = NULL;
		if($this->result instanceof mysqli_result) 
		{
			$obj = $this->result->fetch_object();
		}
		elseif($this->result instanceof mysqli_stmt){
			$variables = array();
			$data = new stdClass;
			foreach($this->map as $field)
				$variables[] = &$data->{$field}; // pass by reference
		    
			call_user_func_array(array($this->result, 'bind_result'), $variables);

			if($this->result->fetch()) {
				$obj = $data;
			}
			else
				return NULL;
		}
		
		if($this->renderer){
			$mapping = $this->renderer->get_alias_mapping();
			
			if($mapping) {
				$res = array();
				foreach($obj as $var => $value) {
					list($alias,$field) = explode('__',$var);
					$klass = $mapping[$alias];
					if(!isset($res[$klass]))
						$res[$klass] = new StdClass;
					$res[$klass]->{$field} = $value;
				}
				return $res;
			}
		}
		
		return $obj;
	}
		
	function free() {
		if($this->result instanceof mysqli_result)
			$this->result->close();
		else {
			$this->result->close();
		}
	}
}

class MysqliBackend extends mysqli{
    var $connected;
    var $config;

    function __construct($config, $connect = FALSE) {
        parent::init();
        if (!parent::options(MYSQLI_INIT_COMMAND, 'SET NAMES "utf8"')) {
            throw new DBException('mysqli init command failed');
        }

        if (!parent::options(MYSQLI_OPT_CONNECT_TIMEOUT, 5)) {
            throw new DBException('Setting MYSQLI_OPT_CONNECT_TIMEOUT failed');
        }
        
        $this->config = new Argument($config);


        if($connect) {
            $this->connect();
        }
    }
    
    function connect(){
        if($this->connected) {
            return;
        }
		
		$host = $this->config->get('host','localhost');
        $user = $this->config->get_required('username');
        $pass = $this->config->get_required('password');
        $db = $this->config->get_required('database');
        
		
        if (!parent::real_connect($host, $user, $pass, $db)) {
            throw new DBException('Connect Error (' . mysqli_connect_errno() . ') '
                    . mysqli_connect_error());
        }
        
		$this->set_charset("utf8");
		
        $this->connected = TRUE;
    }
	
	function begin_transaction(){
		$this->autocommit(FALSE);
	}
	
	function end_transaction($commit=TRUE){
		if($commit){
			$this->commit();
		}
		else {
			$this->rollback();
		}
		$this->autocommit(TRUE);
	}
	
	function query_unprepared($param,$will_free=TRUE) {
		$renderer = NULL;
		
		if($param instanceof QuerySet) {
			$renderer = new SQLRender($param);
			$sql = $renderer->render();
		}
		else {
			$sql = $param;
		}
		
		$this->connect();
		
		if(!$sql){ 
			throw new DBException('SQL is empty');
		}
		
		$res = parent::query($sql,$will_free?MYSQLI_USE_RESULT:MYSQLI_STORE_RESULT);

		if(!$res) {
			throw new DBException($this->error.' '.$sql);
		}
		
		return new MysqliResult($res,NULL,$renderer);
	}
	
	function query_prepared($param, $will_free = TRUE){
		$renderer = new PreparedSQLRender($param);
		$sql = $renderer->render();
		$args = $renderer->args;
        //var_dump($sql,$args);
		$result = $this->prepared_query($sql, $args, !$will_free);
		$result->renderer = $renderer;
		return $result;
	}
	
	function query($param, $will_free = TRUE, $prefer_prepared = TRUE){
		if($param instanceof QuerySet) {
			if($prefer_prepared){
				return $this->query_prepared($param, $will_free);
			}
		}
		return $this->query_unprepared($param, $will_free);
	}
	
	function raw_query($sql, $args, $store = FALSE) {
		return $this->prepared_query($sql, $args, $store);
	}
	
	function prepared_query($sql, $args, $store = FALSE) {
		$this->connect();
		
		try{
			$stmt = $this->prepare($sql);
			if(!$stmt){
				throw new DBException($stmt->error.' '.$sql, $stmt->errno);
			}
			if($args) {
				
				
				$datatypes = '';
				$values = array();
				
				foreach($args as $arg) {
					$datatypes .= $arg[0];
					$values[] = $arg[1];
				}
				
				if (strnatcmp(phpversion(),'5.3') >= 0) {
					$refs = array();
					foreach($values as $key => $value)
						$refs[$key] = &$values[$key];
					
					array_unshift($refs, $datatypes);
					$return = call_user_func_array(array($stmt,'bind_param'), $refs);
				}
				else {
					array_unshift($values,$datatypes);	
					$return = call_user_func_array(array($stmt,'bind_param'), $values);
				}
				
				//if(!$return)
					//throw new DBException($stmt->error);
			}
			
			if(!$stmt->execute()){
				throw new DBException($stmt->error,$stmt->errno);
			}
			
		}
		catch(Exception $e){
			if($stmt)
				$stmt->close();
			
			throw $e;
		}
		
		if($store)$stmt->store_result();
		
		$map = array();
		$meta = $stmt->result_metadata();
		if($meta) { //only for SELECTish queries
			while($field = $meta->fetch_field()) {
				$map[] = $field->name;
			}
		}
		
		return new MysqliResult($stmt,$map,NULL);
	}
	
	function free(&$result){
		$result->free();
		unset($result);
	}
    
    function fetch(&$result){
		if($result) {
			return $result->fetch_object();
		}
		return NULL;
    }
    
    function render(&$qs){
        return (string)(new SQLRender($qs));
    }
    
}

class SQLRender {
    /* SQL Render */
    var $qs;
	var $tables;
    var $multitabled = FALSE;
    private $table_aliases;
    private $last_op;

    function __construct($qs) {
        $this->qs = $qs;
        $this->multitabled = FALSE;
		
		$this->tables = array();
		
		foreach($qs->fields as $field) {
            if($field instanceof ModelField)
			    $this->tables[$field->klass] = $field->klass;
            else if($field instanceof Aggregator)
                $this->tables[$field->field->klass] = $field->field->klass;
		}

		foreach($qs->filters as $filter) {
			$this->tables[$filter->field->klass] = $filter->field->klass;
			if($filter->value instanceof ModelField){
				$this->tables[$filter->value->klass] = $filter->value->klass;
			}
		}

        foreach($qs->order_by as $field_order) {
            list($field,$order) = $field_order;
			$this->tables[$field->klass] = $field->klass;
		}

        
		foreach($this->tables as $key => &$table){
			$table = getStaticProperty($table, 'table');
		}
		if(count($this->tables) > 1){
			$this->multitabled = TRUE;
			$i = 1;
			foreach($this->tables as $key => $t){
				$alias = 't'.$i;
				$this->table_aliases[$t] = $alias;
				++$i;
			}
		}
    }
	
    function render_tables(){
        if(!$this->multitabled)
            return array_pop(array_values($this->tables));
        else { // join
            $tables = array();
            foreach($this->tables as $key => $table){
				$alias = $this->table_aliases[$table];
                $tables[] = $table.' '.$alias; 
            }
            
            return join(',', $tables);
        }
    }
	
	function render_field($field,$aliased = FALSE){
        if($field instanceof ModelField) {
            if($this->multitabled) {
                $table = $this->table_aliases[$this->tables[$field->klass]];
                $col = "`$table`.`$field->field`";
                if($aliased)
                    return $col . ' AS ' . $table . '__' . $field->field;
                else
                    return $table . '.' . $field->field;
            }
            else
                return '`'.$field->field.'`';
        }
        else if($field instanceof Aggregator) {
            return strtoupper($field->type).'('.$this->render_field($field->field).') AS ' . $field->alias;
        }
	}
    
    function render_fields(){
        if($this->qs->fields === NULL)
            return '';
        if(empty($this->qs->fields)){
            return "*";
        }
        else {
            $fields = array();
			foreach($this->qs->fields as $field) {
			   $fields[] = $this->render_field($field,TRUE);
			}
            return join(",",$fields);
        }
    }

	function escape_value(&$field,$value,$quote = FALSE){
		if($field instanceof NumericField) {
			return longval($value);
		}
		else {
			if($quote)
				return "'".mysql_escape_string($value)."'";
			else
				return mysql_escape_string($value);
		}
	}
	
	function render_compare(&$field,$cmp,$value){
		if($value instanceof ModelField) {
			return $this->render_field($field) . ' ' . $cmp . ' ' .$this->render_field($value);
		}

		if($cmp == 'IN') {
			if(!is_array($value))
				throw new DBException("IN value must be an array");
			
			if(empty($value)) {
				return 0;
			}
			
			foreach($value as &$v){
                $v = $field->get_db_prep_lookup($v,__CLASS__,$cmp);
				$v = $this->escape_value($field,$v,TRUE);
			}
			
			$value = '('.join(',', $value).')';
		}
		else {
			$value = $field->get_db_prep_lookup($value,__CLASS__,$cmp);

			if($cmp == 'CONTAINS') {
				$value = "%".$value."%";
                $value = $this->escape_value($field,$value,TRUE);
				$cmp = 'LIKE';
			}
			else if($cmp == 'STARTSWITH'){
				$value = $value."%";
                $value = $this->escape_value($field,$value,TRUE);
				$cmp = 'LIKE';

			}
			else if($cmp == 'ENDSWITH'){
				$value = "%".$value;
                $value = $this->escape_value($field,$value,TRUE);
				$cmp = 'LIKE';
			}
			else {
				$value = $this->escape_value($field,$value,TRUE);
			}
		}
		return $this->render_field($field) . ' ' . $cmp . ' ' .$value;
	}
    
    function render_Q($filter){
        $query = '';
        
        if(is_array($filter->field)){
            foreach($filter->field as $f)
                $query .= $this->render_Q($f);
        }
        else{
			$condition = $this->render_compare($filter->field, $filter->cmp, $filter->value);
            $query .= $this->last_op.' '.$condition.' ';
            $this->last_op = $filter->op;
        }
        return $query;
    }
        
    function render_filters(){
        $query = "";
        $this->last_op = "";
        foreach($this->qs->filters as $filter){
            $query .= $this->last_op;
            $this->last_op = '';
            $query .= ' '.$this->render_Q($filter);
            
            if($filter->child){
                $query .= $this->last_op;
                $this->last_op = '';
                $query .= ' ('.$this->render_Q($filter->child).') ';
            }
        }
        return $query;
    }
    
    function render_order(){
		$orders = array();
        foreach($this->qs->order_by as $order_by){
			$order = array_pop($order_by);
			$field = array_pop($order_by);
			
			$field = $this->render_field($field);
			if($order == 'DESC')
				$field .= ' DESC';
			$orders[] = $field;
		}
		return join(',',$orders);
    }

    function render_group(){
		$orders = array();
        foreach($this->qs->group_by as $field){
			$field = $this->render_field($field);
			$orders[] = $field;
		}
		return join(',',$orders);
    }
    
    function render(){
        
		if($this->qs->operation == 'SELECT' || $this->qs->operation == 'SELECT DISTINCT' || $this->qs->operation == 'COUNT') {
            $tables = $this->render_tables(); //first to set aliases
			if($this->qs->operation == 'SELECT' || $this->qs->operation == 'SELECT DISTINCT') {
                $query = $this->qs->operation .' ';
				$query .= $this->render_fields();
            }
			else{
				$query = 'SELECT ';
                $field = array_pop($this->qs->fields);
				$query .= 'COUNT(';
				$query .= $this->render_field($field);
				$query .=') AS count';
			}
			$query .= ' FROM '. $tables;
            
            $filter = $this->render_filters();
            if($filter)
                $query .= ' WHERE '.$filter;
            
			if($this->qs->operation == 'SELECT' || $this->qs->operation == 'SELECT DISTINCT') {
				$order = $this->render_order();
				if($order)
					$query .= ' ORDER BY '.$order;

                $group = $this->render_group();
                if($group)
					$query .= ' GROUP BY '.$group;

				if($this->qs->limit)
					$query .= ' LIMIT '.join(',',$this->qs->limit);
			}
			
			return $query;
        }
		else if($this->qs->operation == 'DELETE'){
			if($this->multitabled) {
				throw new DBException('DELETE doesnt support joins');
			}
			
			$tables = $this->render_tables(); //first to set aliases
			$query = 'DELETE ';
			$query .= ' FROM '. $tables;
            
            $filter = $this->render_filters();
            if($filter)
                $query .= ' WHERE '.$filter;
            

			if($this->qs->limit)
				$query .= ' LIMIT '.$this->qs->limit[1];
		
			return $query;
		}
		else if($this->qs->operation == 'INSERT' || $this->qs->operation == 'INSERT DELAYED') {
			if($this->multitabled) {
				throw new DBException('INSERT doesnt support joins');
			}
			
			$tables = $this->render_tables(); //first to set aliases
			$values = array();
			foreach($this->qs->values as $field => &$value){
                if(!is_null($value)){
                    $field = $this->qs->fields[$field]; // get field object
                    $value = $field->get_db_prep_value($value,__CLASS__); // do a prep lookup conversion on the field
                    $values[] = $this->escape_value($field, $value,TRUE); // escape it 
				}
				else {
					unset($this->qs->fields[$field]);
				}
			}
			
			$query = $this->qs->operation;
			$query .= ' INTO '. $tables;
			$query .= '('.$this->render_fields().')';
			$query .= ' VALUES (';
			
			$query .= join(',',$values);
			$query .= ')';
			
			return $query;
		}
		else if($this->qs->operation = 'UPDATE'){
			if($this->multitabled) {
				throw new DBException('UPDATE doesnt support joins');
			}
			
			$query = 'UPDATE ';
			$tables = $this->render_tables();
			$query .= $tables;
			$query .= ' SET ';
			
			foreach($this->qs->values as $field => &$value){
				$field = $this->qs->fields[$field];
                $value = $field->get_db_prep_value($value,__CLASS__);
				$value = $this->escape_value($field, $value,TRUE);
				$value = $this->render_field($field) . '=' . $value;
			}
			
			$query .= join(',',array_values($this->qs->values));
			
			$filter = $this->render_filters();
			if($filter)
                $query .= ' WHERE '.$filter;
            
			if($this->qs->limit)
				$query .= ' LIMIT '.$this->qs->limit[1];
			
			return $query;
		}
		
    }
	
	function get_alias_mapping() {
		if($this->qs->operation == 'SELECT' || $this->qs->operation == 'SELECT DISTINCT') {
			if($this->multitabled) {
				$tables_klass = array_flip($this->tables);
				$alias_tables = array_flip($this->table_aliases);
				$alias_klass = array();
				
				foreach($alias_tables as $alias => $table) {
					$alias_klass[$alias] = $tables_klass[$table];
				}
				return $alias_klass;
			}
		}
		
		return NULL;
	}
	
	function cast($obj){
		if($this->multitabled) {
			$tables_klass = array_flip($this->tables);
			$alias_tables = array_flip($this->table_aliases);
			$result = array();
			foreach($obj as $var => $value) {
				list($table,$field) = explode('__',$var);
				$klass = $tables_klass[$alias_tables[$table]];
				if(!isset($result[$klass]))
					$result[$klass] = new StdClass;
				$result[$klass]->{$field} = $value;
			}
			
			return $result;
		}
		return $obj;
	}
    
    function __toString(){
        return $this->render();
    }
   
}

class PreparedSQLRender extends SQLRender {
	public $args = array();
	
	function escape_value(&$field,$value,$quote = FALSE){
        if($field instanceof NumericField) {
			$this->args[] = array('i',$value);
		}
		else {
			$this->args[] = array('s',$value);
		}
		return '?';
	}
}
