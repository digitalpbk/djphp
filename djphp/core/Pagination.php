<?php

class InvalidPage extends Exception {}
class EmptyPage extends InvalidPage {}

class Pagination {

	public $current_page;
	public $rowsPerPage;
	private $_totalRows;
	public $prev_page;
	public $next_page;
	private $qs;
	public $empty = FALSE;
    public $var = 'page';
	
	//constructor
	function __construct($qs,$rowsPerPage=10){
		$this->rowsPerPage = $rowsPerPage;
		$this->_totalRows = NULL;
		$this->qs = $qs->copy();
	}
	
	function __get($name){
		if($name == "object_list"){
			return $this->qs;
		}
		if($name == "total_rows"){
			return $this->getTotalRows();
		}
		if($name == "num_pages" || $name == "last_page") {
			$totalRows = $this->getTotalRows();
			return ceil($totalRows/$this->rowsPerPage);
		}
        if($name == "pages"){
            return $this->surroundingPages();
        }
		if($name == "first_page"){
			return 1;
		}
        
        return NULL;
	}
	
	public function first_page(){
		return $this->page(1);
	}
	
	public function last_page(){
		return $this->page($this->last_page);
	}
	
	public function page($current_page) {
		$this->current_page = $current_page;
		
		if($this->num_pages == 0){
			$this->empty = TRUE;
		}
		else {
			if($current_page < 1){ 
				throw new InvalidPage();
			}
			elseif($current_page > $this->num_pages) {
				throw new EmptyPage();
			}

            $this->qs->limit(($current_page - 1) * $this->rowsPerPage,$this->rowsPerPage);

			$this->prev_page  = $this->current_page-1;
			$this->next_page  = $this->current_page+1;
		}
		return $this;
	}
	
    public function surroundingPages(){
        $s_start = $this->current_page - 5;
        $s_end = $this->current_page + 5;
        $num = $this->num_pages;
        
        if($s_start < 1){
            $s_end = $s_end + (1 - $s_start);
            $s_start = 1;
        }
        
        if($s_end > $num){
            $s_end = $num;
            if($s_end - $s_start < 10 && $s_start > 1) {
                $s_start = $s_end - 10;
                if($s_start < 1){
                    $s_start = 1;
                }
            }
        }
        
        $arr = array();
        for($i=$s_start;$i<=$s_end;$i++){
            $arr[] = $i;
        }
        return $arr;
    }
    
	public function has_prev(){
		if($this->current_page>1){
			return TRUE;
		}
		return FALSE;
	}
	
	public function has_next(){
		if($this->num_pages > $this->current_page)
		{
			return TRUE;
		}
		return FALSE;
	}
	
	private function getTotalRows(){
		if($this->_totalRows === NULL){
			$this->_totalRows = count($this->qs);
		}
		return $this->_totalRows;
	}
	
	public function url($current_uri, $page) {
        if(strpos($current_uri,$this->var . '=') !== FALSE) {
            $current_uri = preg_replace('|[\&\?]'. $this->var . '=\d+|','',$current_uri, 1);
        }
        
        if(strpos($current_uri,'?') === FALSE) {
            return '?' . $this->var . '=' . $page;
        }
        else {
            return $current_uri . '&' . $this->var . '=' . $page;
        }
    }
	
}

class ProxyPagination implements Countable, Iterator {
    private $array;
    private $_ptr;
    private $count;
    //constructor
	function __construct($array, $count = NULL){
        $this->array = $array;
        $this->_ptr = 0;
        $this->count = $count;
	}

    public function count() {
        return isset($this->count)?$this->count:count($this->array);
    }

    public function copy(){
        return $this;
    }

    public function limit($start,$end) {
        // pass;
    }

    public function key() {
        return $this->_ptr;
    }

    public function rewind() {
        $this->_ptr = 0;
    }

    public function next() {
        $this->_ptr++;
    }

    public function current(){
        return $this->array[$this->_ptr];
    }

    public function valid() {
        return isset($this->array[$this->_ptr]);
    }
}
