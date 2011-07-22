<?

class Encodings {
    static function unicode_escape_sequences($str){
		$working = json_encode($str);
		$working = preg_replace('/\\\u([0-9a-z]{4})/', '&#x$1;', $working);
		return json_decode($working);
	}
}