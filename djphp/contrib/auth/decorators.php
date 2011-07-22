<?php
import('djphp.core.Decorator');
import('djphp.templates.Shortcuts');

function require_login(HttpRequest $request, $redirect_to = NULL){
    if($request->user->is_authenticated()) {
        return;
    }

    if($request->is_ajax()){
        throw new DecoratorException(render_to_json(array('error' => 'login required')));
    }
    else {
        if(!$redirect_to) {
            $redirect_to = $request->uri;
        }

        $redirect_to = UrlResolver::reverse('auth_login') . '?next=' . urlencode($redirect_to);
        throw new DecoratorException(redirect($redirect_to));
    }
}

function require_permission(HttpRequest $request, $perm) {
    try {
        if($request->user->check_access($perm))
            return;
        
        throw new PermissionException("You don't have permission to do this action.");
    }
    catch(PermissionException $e) {
        if($request->is_ajax()){
            throw new DecoratorException(render_to_json(array('error' => $e->getMessage())));
        }
        else {
            throw new HttpException(403,NULL,$e->getMessage());
        }
    }
}