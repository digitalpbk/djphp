<?php
import("djphp.models.BaseModel");
import("djphp.models.CachedManager");
import("djphp.models.Fields");

class FlatPage extends BaseModel {
    public $key;
    public $value;
    public $mimetype;

    public static $objects;
    public static $cached_objects;
    public static $fields;
    public static $table = 'flatpages';

    //Constructor
	public function init(){
		if(!self::$objects) {
			self::$fields = array(
				new AutoField(kwargs('field','key','auto_increment',FALSE)),
			);

			self::$objects = new BaseManager(__CLASS__);
            self::$cached_objects = new CachedManager(__CLASS__);
		}
	}
	    
}

FlatPage::init();