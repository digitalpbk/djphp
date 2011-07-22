<?php

class HtmlLib {

    private static $DISALLOWED_TAGS = array(
        'script','style','link','embed','object'
    );
    private static $DISALLOWED_ATTRIBUTES = array(
    );
    private static $ALIAS_TAGS = array(
        'strong' => 'b',
        'em' => 'i',
    );
    
    private static $ALLOWED_RULES = array(
        'FULL_HTML' => array(
            'tags' => array('b','i','br','img','a','strong','em','sup','sub','blockquote','p','table','tr','td','th','tbody','ul','ol','li','u','small','strike'),
        ),
        'LTD_HTML' => array(
            'tags' => array('b','i','br','strong','em','sup','sub','p','ul','ol','li','u','small'),
            'attrs' => array('class','style'),
        ),
        'NO_HTML' => FALSE,
    );
    
    private static function nameit($child){
        $name = $child->nodeName;
        $attr_class = $child->attributes->getNamedItem("class");
        $class = $id = NULL;
        if($attr_class)
            $class = $attr_class->nodeValue;

        $attr_id = $child->attributes->getNamedItem("id");

        if($attr_id)
            $id = $attr_id->nodeValue;
        return $name . ($class?".$class":'') . ($id? "#$id":'');
    }
    
    private static function recursiveParser($node,$f,$doc,$rules,$xpath = NULL,$cb = NULL){
        //echo $xpath; echo '<br/>';
        
        foreach($node->childNodes as $child){
            if(in_array($child->nodeName, self::$DISALLOWED_TAGS)){
                continue;
            }

            if($cb){
                djphp_call_user_func($cb,$child,$xpath);
            }
            
            if($rules && in_array($child->nodeName, $rules['tags'])){
                $new = $doc->createElement($child->nodeName);
                foreach ( $child->attributes as $attribute )
                {
                    if ( substr($attribute->name,0,2) == 'on' ||
                         in_array ( $attribute->name, self::$DISALLOWED_ATTRIBUTES ) ||
                         ($rules && isset($rules['attrs']) && in_array ( $attribute->name, $rules['attrs'] ))
                    )
                    {
                        continue;
                    }

                    $new->setAttribute($attribute->name, $attribute->value);
                }
                $f->appendChild($new);
                
                if($child->hasChildNodes()){
                    $name = self::nameit($child);
                    self::recursiveParser($child,$new,$doc,$rules,$xpath . '/'. $name, $cb );
                }
            }
            
            else if($child->nodeName == '#text'){
                $textNode = $doc->createTextNode($child->textContent. ' '); # Auto spaced
                $f->appendChild($textNode);
            }
            
            else {
                if($child->hasChildNodes()){
                    $name = self::nameit($child);
                    self::recursiveParser($child,$f,$doc,$rules,$xpath . '/'. $name, $cb );
                }
            }
            
            
        }
    }
    
    public static function safehtml($html, $using = 'FULL_HTML',$cb=NULL) {
        $html = self::unicode_escape_sequences($html);
        $html = '<html><body>' . $html . '</body></html>';
        $d = new DomDocument();
        $d->loadHTML($html);
        $f = new DomDocument();
        $body = $d->getElementsByTagName("body");

        if(is_array($using)) {
            $rules = $using;
        }
        else {
            $rules = self::$ALLOWED_RULES[$using];
        }

        foreach($body as $b){
            self::recursiveParser($b,$f,$f,$rules,NULL,$cb);break;
        }
        return $f->saveHTML();
    }


    static function unicode_escape_sequences($str){
        $working = json_encode($str);
        $working = preg_replace('/\\\u([0-9a-z]{4})/', '&#x$1;', $working);
        return json_decode($working);
    }

    static function safe($str){
        $str = self::unicode_escape_sequences($str);
        return htmlentities($str, ENT_QUOTES, "UTF-8", FALSE);
    }

    static function strip_html($html){
        return self::safehtml($html,'NO_HTML');
    }

}
