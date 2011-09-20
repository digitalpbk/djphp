<?php
import('djphp.utils.Mime');

class StaticServeController {
    function serve($request, $kwargs){
        $path = $kwargs->path;
        
        $media = App::$settings->MEDIA_ROOT;
        $path = realpath($media . '/' . $path);

        if(is_dir($path))
            throw new Http403;

        if(file_exists($path) && substr($path,0,strlen($media)) == $media) {
            $mime = MimeTypes::guess_for_filename($path);
            $contents = file_get_contents($path);
            return new HttpResponse($contents,200,$mime);
        }

        throw new Http404($path);
    }
}