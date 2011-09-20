<?php
import("models");
import("djphp.templates.Shortcuts");


class GenerateSitemapController {
    public static function sitemap_xml($name) {
        $selected_map = NULL;
        $sitemaps = array();

        foreach(App::$settings->INSTALLED_APPS as $app){
            
			$sitemap = import($app.".sitemap",NULL,TRUE);
            
            if($sitemap) {

                if(strpos($name,$sitemap->name) === 0) {
                    $selected_map = $sitemap;
                }
                $sitemaps[] = $sitemap;
            }

		}
        
        if($selected_map) {
            if(strpos($name,"_") !== FALSE) {
                list($name, $page) = explode("_",$name);
                return $selected_map->page($page);
            }

            return $selected_map->index_page();
        }
        else if($name === '__main__') {
            
            if($sitemaps) {
                $context = array('sitemaps' => $sitemaps);
                return render_to_string("sitemap/index.xml",$context);
            }
        }

        throw new Exception("Invalid Sitemap");
    }
    
    public static function sitemap_xml_gz($name) {
        return gzencode(self::sitemap_xml($name));
    }
}

class SitemapController extends GenerateSitemapController {
    public static function index($request,$kwargs) {
        $kwargs->name = '__main__';
        return self::sitemap_xml($request,$kwargs);
    }
    
    public static function sitemap_xml($request,$kwargs) {
        try{
            $sitemap = Sitemap::$objects->get($kwargs->name, 'key');
            $response = new HttpResponse($sitemap->value,NULL,'text/xml');
            if($request->support_gzip()) {
                $response->set_header('Content-Encoding','gzip');
            }
            else {
                $response->content = gzdeflate($response->content);
            }
            
            return $response;
        }
        catch(DoesNotExist $e) {
            try{
                $value = parent::sitemap_xml($kwargs->name);
                $sitemap = new Sitemap();
                $sitemap->value = gzencode($value);
                $sitemap->key = $kwargs->name;
                $sitemap->save(true);

                $response = new HttpResponse($value,200,"text/xml");

                if($request->support_gzip()) {
                    $response->set_header('Content-Encoding','gzip');
                    $response->content = gzencode($response->content);
                }

                return $response;
            }
            catch(Exception $e) {
                throw new Http404;
            }
        }
    }

    public static function sitemap_xml_gz($request,$kwargs) {
        try{
            $sitemap = Sitemap::$objects->get($kwargs->name, 'key');
            return new HttpResponse($sitemap->value,200,"application/x-gzip");
        }
        catch(DoesNotExist $e) {
            try{
                $value = parent::sitemap_xml_gz($kwargs->name);
                $sitemap = new Sitemap();
                $sitemap->value = $value;
                $sitemap->key = $kwargs->name;
                $sitemap->save(true);

                return new HttpResponse($value,200,"application/x-gzip");
            }
            catch(Exception $e) {
                throw new Http404;
            }
        }
    }
}
