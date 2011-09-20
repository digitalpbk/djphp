<?php

import("djphp.templates.Template");

function render_to_response($file, $context = NULL, $mimetype = NULL, $status = NULL, $dirs = NULL){
    $content = render_to_string($file,$context,$dirs);
    return new HttpResponse($content, $status, $mimetype);
}

function render_to_string($file,&$context,$dirs = NULL ) {
    $tmpl = new Template();
    return $tmpl->render_to_string($file, $context, $dirs);
}

function render_to_json($object) {
    $content = json_encode($object);
    return new HttpResponse($content, 200, "application/json");
}

function redirect($url){
	return new HttpResponse($url,302);
}

function permanent_redirect($url){
	return new HttpResponse($url,301);
}

function RequestContext($request, $context){
	$context['user'] = $request->user;
    $context['current_path'] = $request->path;
    $context['current_uri'] = $request->uri;
    $context['domain'] = $request->domain;

    //$messages = import('djphp.contrib.messages');
    //$context['messages'] = new LazyMessages($request,$messages);

    $signals = import('djphp.core.Signals');
    $return = $signals->fire('EXTRA_CONTEXT',kwargs('request',$request),'RequestContext');

    foreach($return as $ret_value) {
        foreach($ret_value as $key => $value){
            $context[$key] = $value;
        }
    }
    
	return $context;
}
