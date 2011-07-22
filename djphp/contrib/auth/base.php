<?php
define('INVALID_PASSWORD','!');

class AuthException extends Exception {}
class PermissionException extends Exception {}

class Auth {

    static $permissions;
    
	static function authenticate($request,$args) {
		$user = new AnonymousUser();
        $backends = App::$settings->AUTH_BACKENDS;
		foreach($backends as $backend){
			list($module, $class) = module_dot_class($backend);
			import($module);
			$user = call_user_func(array($class,'get_user'), $request, $args);
			if($user !== NULL) {
                break;
            }
		}
		
		if($user){
			if(!$user->is_active) {
				throw new AuthException('Inactive User');
			}

            Auth::login($request,$user);
		}
		
		return $user;
	}

    static function login($request,$user){
        $user->last_logged_in = new DateTime();
        $user->save();
        
        $request->user = new AuthenticatedUser($user);
        $request->SESSION['uid'] = $user->id;
        $signals = import("djphp.core.Signals");
        $signals->fire('USER_LOGIN',kwargs('user',$user),__CLASS__);
    }

    static function logout($request) {
        $user = $request->user;
        
        $signals = import("djphp.core.Signals");
        $signals->fire('USER_LOGOUT',kwargs('user',$user),__CLASS__);

        $request->user = new AnonymousUser();
        unset($request->SESSION['uid']);
    }

    static function look_up_permission($perm) {
        if(!isset(Auth::$permissions)) {
            $perms = array();
            foreach(App::$settings->INSTALLED_APPS as $app){
                $add = import($app.".perms",NULL,TRUE);
                if(is_array($add)) {
                    foreach($add as $key => $value) {
                        $perms[$key] = $value;
                    }
                }
            }

            Auth::$permissions = array_flip($perms);
        }

        if(isset(Auth::$permissions[$perm])){
            return Auth::$permissions[$perm];
        }

        throw new Exception('Invalid Permission ' . $perm);
    }

    static function has_access($user, $perm) {
        $perm_code = Auth::look_up_permission($perm);

        $backends = App::$settings->AUTH_BACKENDS;
        $caught = NULL;

		foreach($backends as $backend){
			list($module, $class) = module_dot_class($backend);
			import($module);

            try {
			    if(method_exists($class,'has_access') && call_user_func(array($class,'has_access'), $user, $perm, $perm_code))
                    return true;
            }
            catch(PermissionException $e){
                $caught = $e;
            }
		}

        if($caught instanceof PermissionException)
            throw $caught;
        return FALSE;
    }

    static function check_password($stored, $given){
		if(strpos($stored,'$') !== FALSE) { // with salt
			list($salt, $md5) = explode('$',$stored);
			if(md5($salt.$given) == $md5){
				return TRUE;
			}
		}
		if(md5($given) == $stored){
			return TRUE;
		}
		return FALSE;
	}
}