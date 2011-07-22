<?php
import("djphp.forms.Form");
import("djphp.forms.Fields");
import("djphp.forms.Widgets");
import("base");

class LoginForm extends Form {
	private $request;
	
	public static $fields;
	static function init() {
		self::$fields = array(
			new CharFormField(kwargs('field','username')),
			new CharFormField(kwargs('field','password','widget',new PasswordWidget()))
		);
	}
	
	public function clean($data){
		try{
			$user = Auth::authenticate($this->request,kwargs('auth_data',$data));
		}
		catch(AuthException $e){
			throw new ValidationError($e->getMessage());
		}
		
		if(!$user){
			throw new ValidationError("Invalid credentials");
		}
		return $data;
	}
	
	public function __construct($request,$values = NULL) {
		parent::__construct($values);
		$this->request = $request;
	}
}

LoginForm::init();

class RegisterForm extends Form {
    public static $fields;

    static function init() {
        self::$fields = array(
            new CharFormField(kwargs('field','username','max_length',32)),
            new EmailFormField(kwargs('field','email')),
            new CharFormField(kwargs('field','password','min_length',5,'widget',new PasswordWidget())),
            new CharFormField(kwargs('field','password_confirm','min_length',5,'widget',new PasswordWidget())),
        );
    }
    
    function clean_email($email) {
        try{
            $user = User::$objects->get($email,'email');
            throw new ValidationError('Email is already registered, Please login.');
        }
        catch(DoesNotExist $e){
            // pass
        }
    }

    function clean($data){

        if($data['password'] != $data['password_confirm']) {
            throw new ValidationError("The passwords do not match");
        }

        try{
            User::$objects->get($data['username'],'username');
            throw new ValidationError("Username already taken");
        }
        catch(DoesNotExist $e) {
            //Yup unique
        }
        
        return $data;
    }
}

RegisterForm::init();

class PasswordResetForm extends Form {
    public static $fields;

    static function init() {
        self::$fields = array(
            new CharFormField(kwargs('field','password','label','New Password','min_length',5,'widget',new PasswordWidget())),
            new CharFormField(kwargs('field','password_confirm','label','Confirm New Password','min_length',5,'widget',new PasswordWidget())),
        );
    }

    function clean($data){

        if($data['password'] != $data['password_confirm']) {
            throw new ValidationError("The passwords do not match");
        }
        
        return $data;
    }
}

PasswordResetForm::init();
/**
 * used for finding the email
 */
class RequestPasswordResetForm extends Form {

    public static $fields;

    static function init() {
        self::$fields = array(
            new EmailFormField(kwargs('field','email','min_length',5,'widget',new TextWidget())),
        );
    }
}

RequestPasswordResetForm::init();