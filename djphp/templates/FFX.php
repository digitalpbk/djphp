<?php
class FFX {
	static function url(){
		$args = func_get_args();
		return call_user_func_array(array('UrlResolver','reverse'),$args);
	}
	
	static function time_until($date1,$date2=NULL){
		if($date2 === NULL){
			$date2 = new DateTime();
		}
		
		$dateDiff = $date1->format('U') - $date2->format('U');
		
		$arr = array(
			"second"=>60,
			"minute"=>60,
			"hour"=>24,
			"day"=>30,
			"month"=>12,
		);
		
		$t = 1;
		foreach($arr as $unit => $divider){
			$t *= $divider;
			if($dateDiff < $t){
				$value = floor($dateDiff * $divider  / $t);
				return $value.' '.FFX::pluralize($value,$unit);
			}
		}
		$value = floor($dateDiff / (86400*365));
		return $value.' '.FFX::pluralize($value,'year');
	}
	
	static function pluralize($i,$text){
		return $text . ($i==1?'':'s');
	}
	
	
	static function time_since($date1,$date2=NULL){
		if($date2==NULL){
			$date2 = new DateTime();
		}
		return FFX::time_until($date2,$date1);
	}
	

	static function safe($str){
        import('djphp.utils.encodings');
		$str = Encodings::unicode_escape_sequences($str);
		return htmlentities($str, ENT_QUOTES, "UTF-8", FALSE);
	}

	static function safehtml($html,$using = 'FULL_HTML'){
		import('djphp.utils.encodings');
        $html = Encodings::unicode_escape_sequences($html);
        import("djphp.utils.HtmlLib");
		return HtmlLib::safehtml($html,$using);
	}

    static function slugify($title) {
        $title = preg_replace('/[^a-z0-9-]+/',' ',$title);
		$title = preg_replace('/\s+/',' ',$title);
		$title = preg_replace('/\s+/','-',$title);
		$title = preg_replace('/-+/','-',$title);
        return trim($title,'-');
    }

    static function truncate_words($phrase,$max_words,$ellipsis=TRUE) {
        $phrase_array = explode(' ',$phrase);
        if(count($phrase_array) > $max_words && $max_words > 0)
            $phrase = implode(' ',array_slice($phrase_array, 0, $max_words)) . ($ellipsis?'...':'');
        return $phrase;
    }

    static function ordinal($num){
        $test_c = abs($num) % 10;
        $ext = ((abs($num) %100 < 21 && abs($num) %100 > 4) ? 'th'
            : (($test_c < 4) ? ($test_c < 3) ? ($test_c < 2) ? ($test_c < 1)
            ? 'th' : 'st' : 'nd' : 'rd' : 'th'));
        return $num.$ext;
    }
}