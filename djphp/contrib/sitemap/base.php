<?php
import('djphp.core.Pagination');

abstract class BaseSitemap {
    public $per_page = 5000;
    
    public function index_page(){
        $qs = $this->queryset();
        $page = new Pagination($qs,$this->per_page);
        $pages = $page->num_pages;

        $context = array('name' => $this->name,
                         'pages' => $pages,
                         'sitemap' => $this);

        return render_to_string("sitemap/page_index.xml",$context);
    }

    public function page($page_num) {
        $qs = $this->queryset();
        $page = new Pagination($qs,$this->per_page);
        $page->page($page_num);
        $context = array('objects' => $page->object_list,
                         'sitemap' => $this,
                         'name' => $this->name);
        return render_to_string("sitemap/objects.xml",$context);
    }

    abstract public function queryset();
    abstract public function lastmod($object);
}