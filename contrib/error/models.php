<?php

define('ANSWER_LOG_CRITICAL',4);
define('ANSWER_LOG_ERROR',3);
define('ANSWER_LOG_WARNING',2);
define('ANSWER_LOG_INFO',1);
define('ANSWER_LOG_DEBUG',0);

class DBExceptionLog extends BaseModel {

    public $id;
	public $message;
	public $backtrace;
	public $line;
	public $file;
	public $client;
	public $severity;

	public static $objects;
	public static $fields;
	public static $table = 'error_log';

    //Constructor
	public function init(){
		if(!self::$objects) {
			self::$fields = array(
				new AutoField(kwargs('field','id')),
			);

			self::$objects = new BaseManager(__CLASS__);
		}
	}

	public function __toString() {
		return $this->message;
	}
}

DBExceptionLog::init();

// vim: tabstop=4 softtabstop=4 shiftwidth=4 noexpandtab ai
