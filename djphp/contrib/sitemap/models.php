<?php

import('djphp.models.BaseModel');
import('djphp.models.BaseManager');
import('djphp.models.Fields');


class Sitemap extends BaseModel {
    public $key;
    public $value;
    public $updated_on;

    public static $objects;
	public static $fields;
	public static $table = 'sitemap';

 //Constructor
	public function init(){
		if(!self::$objects) {
			self::$fields = array(
				new StringField(kwargs('field','key','primary',TRUE,'auto_increment',FALSE)),
                new BlobField(kwargs('field','value')),
                new DateTimeField(kwargs('field','updated_on','auto_now',TRUE)),
			);

			self::$objects = new BaseManager(__CLASS__);
		}
	}

}

Sitemap::init();