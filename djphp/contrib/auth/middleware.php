<?php
import('models');

class UserMiddleware {
	function process_request($request) {
		assert('isset($request->SESSION) /*Session backend*/');
		
		if(isset($request->SESSION['uid'])){
			$user_id = $request->SESSION['uid'];
			
			try{
				$request->user = User::$objects->get($user_id);
                if(!$request->user->is_active) {
                    $request->user = new AnonymousUser();
                }
                else {
                    $request->user = new AuthenticatedUser($request->user);
                }
			}
			catch(DoesNotExist $e) {
				$request->user = new AnonymousUser();
			}
		}
		else {
			$request->user = new AnonymousUser();
		}
	}

}