<?php
import("djphp.core.Url");

return array(
	"djphp.contrib.sitemap.views.SitemapController",
	"^(?P<name>.*).xml$" => new Url("sitemap_xml","sitemap"),
    "^(?P<name>.*).xml.gz$" => new Url("sitemap_xml_gz","sitemap_gz"),
);

