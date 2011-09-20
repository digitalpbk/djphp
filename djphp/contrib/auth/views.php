<?php
import('forms');
import("djphp.templates.Shortcuts");

class AuthController {
	static function login(HttpRequest $request,Argument $args) {

		$next = self::_get_next($request, $args);
		if(!$next){
			$next = App::$settings->LOGIN_REDIRECT;
		}
		
		if($request->user->is_authenticated()){
            if($request->is_ajax()){
                return render_to_json(array('success' => TRUE,'next' => $next));
            }
			return redirect($next);
		}
		
		if($request->method == "POST") {
			$form = new LoginForm($request,$request->POST);
			if($form->is_valid()) {
				//logged in
                if($request->is_ajax()){
                   return render_to_json(array('success' => TRUE,'next' => $next));
                }
				return redirect($next);
			}
            else {
                if($request->is_ajax()){
                    return render_to_json(array('success' => FALSE));
                }
            }
		}
		else {
			$form = new LoginForm($request);
		}

        $template = $args->get('template',"auth/login.phtml");
        $context = $args->get('context',array());
        $context['form'] = $form;
		return render_to_response($template,RequestContext($request,$context));
	}
	
    static function logout(HttpRequest $request, Argument $args){
		if($request->user){
			Auth::logout($request);
		}
		
		$next = self::_get_next($request,$args);
		if(!$next){
			$next = App::$settings->LOGOUT_REDIRECT;
		}
		
		$response = redirect($next);
        $response->set_header("Cache-Control","no-cache");
        return $response;
	}

    static function register(HttpRequest $request,Argument $args) {
        if($request->method == 'POST'){
            $form = new RegisterForm($request->POST);
            if($form->is_valid()) {
                $user = new User();
                $user->username = $form->cleaned_data["username"];
                $user->name = $user->username;
                $user->email = $form->cleaned_data['email'];
                $user->set_password($form->cleaned_data['password']);
                $user->is_active = TRUE;
                $user->save();

                $signals = import('djphp.core.Signals');
                $signals->fire('USER_REGISTER',kwargs('user', $user, 'request', $request),'Auth');

                return redirect(UrlResolver::reverse('auth_login'));
            }
        }
        else {
            $form = new RegisterForm();
        }

		$context = array('form' => $form);
        return render_to_response("auth/register.phtml",RequestContext($request,$context));
    }
	
	static function _get_next(HttpRequest $request,Argument $args){
		$next=NULL;
        $redirect_field = $args->get("redirect_field","next");
		if(isset($request->GET[$redirect_field])){
			$next = $request->GET[$redirect_field];
		}
		return $next;
	}

    /** Forgot Password @author: anirudh */
	public static function reset_password(HttpRequest $request,Argument $args) {
		$reset = $args->reset_hash;
		$user_id = $args->user_id;
		try {
			$user = User::$objects->get($user_id);
		}
		catch(DoesNotExist $e) {
			$context = array('error' => 'Invalid Link');
            return render_to_response("auth/password_reset.phtml",RequestContext($request,$context));
		}
		
		if($request->method == 'POST') {
			$reset_form = new PasswordResetForm($request->POST);
			
			if($reset_form->is_valid()) {
				$password = $reset_form->cleaned_data['password'];
				$user->set_password($password);
				//$user->last_logged_in = new DateTime();
				$user->save();
							
				
				return redirect(UrlResolver::reverse('auth_login'));
			}
		}
		else {
			if($reset != self::generate_reset_hash($user)) {
				$context = array('error' => 'Invalid Link');
                return render_to_response("auth/password_reset.phtml",RequestContext($request,$context));
			}
			
			$reset_form = new PasswordResetForm();
		}
		
		$context = array('form' => $reset_form);
		return render_to_response("auth/password_reset.phtml",RequestContext($request,$context));
	}

	
	protected static function generate_reset_hash($user) {
		return md5($user->id.$user->password);
	}
	
	public static function forgot_password(HttpRequest $request,Argument $args) {
		if($request->method == 'POST') {
			$reset_form = new RequestPasswordResetForm($request->POST);
			
			if($reset_form->is_valid()) {
				$email = $reset_form->cleaned_data['email'];
				try {
					$user = User::$objects->get($email,'email');
				}
				catch(Exception $e) {
					return redirect(UrlResolver::reverse('auth_forgot_password_mail_sent'));
				}
                
				$user_id = $user->id;
				$reset = self::generate_reset_hash($user);
				$reset_link =  'http://' . $request->domain . UrlResolver::reverse('auth_reset_password',$user_id,$reset);
				
				$context = array(
					'site_name'  => App::$settings->SITE_NAME,
					'reset_link' => $reset_link,
                    'user' => $user,
				);

                $to = $user->email;
				$body = render_to_string('auth/email/reset_email.txt',$context);
                $subject = render_to_string('auth/email/reset_email_subject.txt',$context);

				self::send_reset_mail($to,$subject,$body);

				return redirect(UrlResolver::reverse('auth_forgot_password_mail_sent'));
			}
		}
		else {
			$reset_form = new RequestPasswordResetForm();
		}
		
		$context = array('form' => $reset_form);
		return render_to_response("auth/forgot_password.phtml",RequestContext($request,$context));
	}
	
	protected static function send_reset_mail($to,$subject,$body,$from = NULL) {
		import('djphp.mail.base');

        if(!$from) $from = App::$settings->DEFAULT_EMAIL_FROM;

        list($from_name,$from_email) = $from;
        
		$mailer = new PHPMailer();
		$mailer->setFrom($from_email,$from_name);
		$mailer->AddAddress($to);
		$mailer->Subject = $subject;
		$mailer->Body = $body;
		
		if(!$mailer->Send()) {
			throw new Exception('Error in sending mail : ' . $mailer->ErrorInfo);
		}
	}
	
	public static function forgot_password_mail_sent(HttpRequest $request,Argument $args) {
		return render_to_response("auth/forgot_password_mail_sent.phtml",RequestContext($request,array()));
	}
    /* end forgot password*/
}
