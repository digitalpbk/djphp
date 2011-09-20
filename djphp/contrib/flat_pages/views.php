<?php
import('models');
import('djphp.utils.Mime');

class FlatController {
    public function serve($request,$kwargs) {
        return self::_get(FlatPage::$objects,$kwargs);
    }

    protected function _get($manager,$kwargs) {
        try{
            $flat_page = $manager->get($kwargs->key,'key');
        }
        catch(DoesNotExist $e){
            throw new Http404;
        }

        return new HttpResponse($flat_page->value,NULL,$flat_page->mimetype);
    }
}

class CachedFlatController extends FlatController {
    public function serve($request,$kwargs) {
        return self::_get(FlatPage::$cached_objects,$kwargs);
    }
}

class DirectToTemplate {
    public function serve($request,$kwargs) {
        import('djphp.templates.Shortcuts');
        try{
            return render_to_response($kwargs->template,
                                      RequestContext($request,array('kwargs' => $kwargs)),
                                      $kwargs->get('mime',MimeTypes::guess_for_filename($kwargs->template))
            );
        }
        catch(TemplateNotFound $e){
            throw new Http404();
        }
    }
}