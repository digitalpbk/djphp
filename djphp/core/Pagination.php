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
	
	
	
}
