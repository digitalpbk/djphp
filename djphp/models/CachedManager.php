<?
require_once('BaseManager.php');

class CachedManager extends BaseManager {
    public function __construct($class) {
        import('djphp.cache.default');
        parent::__construct($class);
    }
	protected function getKey($id,$field = NULL) {
        if(!$field)
            $field = $this->pk;
		$cacheKey = $this->klass.$field.$id;
		return $cacheKey;
	}

	public function get($id,$field=NULL) {
		$cacheKey = $this->getKey($id,$field);
        $cache = import('djphp.cache.default');
		$value = $cache->get($cacheKey);

		if(empty($value)) {
			$value = parent::get($id,$field);
			$cache->set($cacheKey, $value);
		}
		return $value;
	}

	public function save($obj,$force_insert=FALSE) {
		$ret_id = parent::save($obj,$force_insert);
		$cacheKey = $this->getKey($ret_id);
        $cache = import('djphp.cache.default');
		$cache->set($cacheKey, $obj);
        return $ret_id;
	}

    public function clear_cache($id,$field) {
        $cacheKey = $this->getKey($id,$field);
        $cache = import('djphp.cache.default');
		return $cache->flush($cacheKey);
    }
}
