<?php 
import('djphp.models.BaseModel');
import('djphp.models.BaseManager');
import('djphp.models.Fields');

class User extends BaseModel {
	public $id = NULL;
	//Declare all the members of the class
	public $email;
	public $username;
	public $password;
	public $name;
	public $created_on;
	public $last_logged_in;
	public $is_active;
    
    public $is_admin;
    public $is_staff;
    
	public static $objects;
	public static $fields;
	public static $table = 'auth_users';
	
	//Constructor
	public function init(){
		if(!self::$objects) {
			self::$fields = array(
				new AutoField(kwargs('field','id')),
				new DateTimeField(kwargs('field','created_on','default','NOW')),
				new DateTimeField(kwargs('field','last_logged_in','default','NOW')),
                new BooleanField(kwargs('field','is_active','default',TRUE)),
                new BooleanField(kwargs('field','is_admin','default',FALSE)),
                new BooleanField(kwargs('field','is_staff','default',FALSE)),
			);
			
			self::$objects = new BaseManager(__CLASS__);
		}
	}
    
    /**
     * @param int $length
     * @return string
     */
    private function get_salt($length=4){
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890-=!@#%^&*()+:;"<>,~`';
        $salt = '';
        $strlen = strlen($chars);
        for($i=0; $i < $length; $i++) {
            $salt .= $chars[rand(0,$strlen-1)];
        }
        return $salt;
    }

    /**
     * Set the user password 
     * @param  $password
     * @return void
     */
    public function set_password($password) {
        $salt = $this->get_salt();
        $password = $salt . '$' . md5($salt.$password);
        $this->password = $password;
    }
	
    public function check_access($name){
        import('djphp.contrib.auth.base');
        return Auth::has_access($this, $name);
    }

    public function grant_access($name) {
        return Auth::grant_access($this,$name);
    }

    public function revoke_access($name) {
        return Auth::revoke_access($this,$name);
    }

    public function all_access(){
        return $this->userpermissionmap_set;
    }
}

class AnonymousUser {

	public function is_authenticated() {
		return FALSE;
	}

    public function check_access($name){
        import('djphp.contrib.auth.base');
        return Auth::has_access(NULL, $name);
    }

    public function has_access($name){
        try{
            return $this->check_access($name);
        }
        catch(PermissionException $e){
            return False;
        }
    }

    public function __get($name) {
        return FALSE;
    }
}

class AuthenticatedUser extends AnonymousUser{
    private $user;
    
    public function is_authenticated() {
        return TRUE;
    }

    public function __construct($user){
        $this->user = $user;
    }

    public function __get($name) {
        return $this->user->{$name};
    }

    public function check_access($name){
        return $this->user->check_access($name);
    }

    public function grant_access($name) {
        return $this->user->grant_access($name);
    }

    public function revoke_access($name) {
        return $this->user->revoke_access($name);
    }

    public function all_access(){
        return $this->user->all_access();
    }

    /**
     * Set the user password
     * @param  $password
     * @return void
     */
    public function set_password($password) {
        $this->user->set_password($password);
    }

    public function save(){
        $this->user->save();
    }

    public function get_user() {
        return $this->user;
    }
}

User::init();

class UserPermissionMap extends BaseModel {
    public $id;
    public $user_id;
    public $perm_id;

    public static $objects;
	public static $fields;
	public static $table = 'auth_user_permission';

	//Constructor
	public function init(){
		if(!self::$objects) {
			self::$fields = array(
				new AutoField(kwargs('field','id')),
                new NumericField(kwargs('field','user_id')),
                new ForeignKeyField(kwargs('field','user','to_klass','User','from_field','user_id')),
				new NumericField(kwargs('field','perm_id')),
			);
			self::$objects = new BaseManager(__CLASS__);
		}
	}
}

UserPermissionMap::init();