<?php
import('djphp.templates.Shortcuts');

class ErrorController {
	
    function http403($request,$e) {
		if(App::$settings->DEBUG) {
			$content = $e->getMessage().' '.$e->getTraceAsString();
            return new HttpResponse($content,403);
		}

        try{
            $context["e"] = $e;
            return render_to_response('403.phtml',RequestContext($request,$context));
        }
        catch(Exception $e) {
            return new HttpResponse("403",403);
        }
    }
    
    function http404($request,$e) {
		if(App::$settings->DEBUG) {
			$content = $e->getMessage().' '.$e->getTraceAsString();
            return new HttpResponse($content,404);
		}

        try{
            $context["e"] = $e;
            return render_to_response('404.phtml',RequestContext($request,$context));
        }
        catch(Exception $e) {
            return new HttpResponse("404",404);
        }
    }
    
    function http500($request, $e) {
        if(App::$settings->DEBUG) {
			$content = $e->getMessage().' '. $e->getTraceAsString();
            return new HttpResponse($content,500);
        }
        else {
            try{
                $context["e"] = $e;
                import("models");
                $log = new DBExceptionLog();
                $log->backtrace = $e->getTraceAsString();
                $log->message = $e->getMessage();
                $log->file = $request->uri;

                DBExceptionLog::$objects->save($log,FALSE,TRUE);
                return render_to_response('500.phtml',RequestContext($request,$context));
            }
            catch(Exception $e) {
                return new HttpResponse("Internal Server Error",500);
            }
        }
    }
}