<?php

class DefaultSettings extends BaseSettings {
	public $DEBUG = TRUE;
	public $MIDDLEWARES = array(
	);
	
	public $ERROR_VIEW = "djphp.contrib.error.views.ErrorController";
	public $TEMPLATE_FOLDER = "templates";
	
	public $CONTEXT_PROCESSORS = array(
		'djphp.static.context_processor.StaticContextProcessor',
	);
	
	public $AUTH_BACKENDS = array();
	
	public $SITE_ID = '1'; //override this for multi site config.
    public $SITE_NAME = 'example.com';
    
	public $DATABASE_ROUTER = NULL;
	
	public $SESSION_COOKIE = 'dj_sessid';
	public $SESSION_EXPIRES = 3600;
	public $SESSION_DOMAIN = NULL;

    public $COOKIE_PATH = '/';
	
	public $SECRET = 'Give a default value in settings.php main file';
	
	public $LOGIN_REDIRECT = '/';
	public $LOGOUT_REDIRECT = '/';

    public $CACHES = array();

    public $EMAIL_DEFAULT_FROM = array('Admin','root@localhost');
    public $PROXY_IP_LIST = FALSE;
}