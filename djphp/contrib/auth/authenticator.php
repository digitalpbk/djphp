<?php
import('models');
import('base');

class DefaultAuthBackend {
	function get_user($request, $args){
		$form = $args->get('auth_data',NULL);
		if($form){
			$username = $form['username'];
			$password = $form['password'];
			
			if($username && $password) {
				try {
					$user = User::$objects->get($username, 'username');
				}
				catch(DoesNotExist $e){
					return NULL;
				}
				
				if(Auth::check_password($user->password,$password)) {
					return $user;
				}
			}
		}
	}

    function has_access($user, $perm, $perm_code) {
        static $cache = NULL;
        if(!$user)
            return FALSE;

        if($user->is_admin)
            return TRUE;

        if(!isset($cache[$user->id . $perm])) {
            if(UserPermissionMap::$objects->filter('user_id','=',$user->id)->filter('perm_id','=',$perm_code)->count())
                $cache[$user->id . $perm] = TRUE;
            else
                $cache[$user->id . $perm] = FALSE;
        }
        return $cache[$user->id . $perm];
    }
}

