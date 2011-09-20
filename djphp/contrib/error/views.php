<?php
import('djphp.templates.Shortcuts');

class ErrorController {
	
    function http403($request,$e) {
		if(App::$settings->DEBUG) {
			$content = $e->getMessage().' '.$e->getTraceAsString();
            return new HttpResponse($content,403);
		}

        try{
            $context['e'] = $e;
            return render_to_response('403.phtml',RequestContext($request,$context),NULL,403);
        }
        catch(Exception $e) {
            return new HttpResponse('403',403);
        }
    }
    
    function http404($request,$e) {
		if(App::$settings->DEBUG) {
			$content = $e->getMessage().' '.$e->getTraceAsString();
            return new HttpResponse($content,404);
		}

        try{
            $context["e"] = $e;
            return render_to_response('404.phtml',RequestContext($request,$context),NULL,404);
        }
        catch(Exception $e) {
            return new HttpResponse('404',404);
        }
    }
    
    function http500($request, $e) {
        if(App::$settings->DEBUG) {
			$content = $e->getMessage().' '. $e->getTraceAsString();
            return new HttpResponse($content,500);
        }
        else {
            try{
                $context['e'] = $e;
                import('models');
                $log = new DBExceptionLog();
                $log->backtrace = $e->getTraceAsString();
                $log->message = $e->getMessage();
                $log->file = $request->uri;
                
                DBExceptionLog::$objects->save($log,FALSE,TRUE); //TRUE for delayed insert

                import('djphp.mail.base');
                $subject = '['.App::$settings->SITE_NAME.'] Error '.$log->message;
                $date = new DateTime();
                $date = $date->format("c");

                $user = NULL;
                try{
                    if($request->user->is_authenticated()) {
                        $user = sprintf("%d %s %s",$request->user->id,$request->user->username,$request->user->name);
                    }
                }
                catch(Exception $e) {}
                
                $message = <<<MESSAGE
Error: $log->message
Date: $date
Requested URI: $log->file
User: $user

Backtrace:
$log->backtrace
MESSAGE;

                Mail::admins($subject,$message);
                return render_to_response('500.phtml',RequestContext($request,$context),NULL,500);
            }
            catch(Exception $e) {
                return new HttpResponse('Internal Server Error',500);
            }
        }
    }
}