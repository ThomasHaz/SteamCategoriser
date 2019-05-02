<?php

class SteamDBScrape {
	public $payload;
	public $id;
	private $app_name = [];
	private $header_image = [];
	private $tags = [];
	private $app_type = [];
	private $genres = [];
        private $_is_valid = false;
	
	function getUrlContent($url){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (iPhone; U; CPU iPhone OS 4_3_3 like Mac OS X; en-us) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8J2 Safari/6533.18.5");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
		$data = curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		return ($httpcode>=200 && $httpcode<300) ? $data : false;
	}
	
	public function __construct($id, $failed = false) {
		$this->payload = $this::getUrlContent("https://steamdb.info/app/{$id}/");
		if($this->payload) {
			if($failed) {
				preg_match("/<td itemprop=\"name\">(.*?)<\/td>/", $this->payload, $this->app_name);
 			}
                        if($this->app_name != []) {
                            $this->_is_valid = true;
                        
                            preg_match("/<td .*?>logo<\/td>[.\n]*<td>.*?\"(.*?)\"/", $this->payload, $this->header_image);
                            preg_match("/<td.*?applicationCategory.*?>(.*?)</", $this->payload, $this->app_type);
                            preg_match("/>Genres<\/td>.?<td>(.*?)</s", $this->payload, $this->genres);
                            preg_match_all("/<a href=\"\/tags\/\?tagid\=\d*\">(.*?[^>])<\/a>/", $this->payload, $this->tags);
                        }
                        
			/*
			preg_match("/<td .*?>has_adult_content<\/td>[.\n]*?<td>(.*?)<\/td>/". $this->payload, $this->mature_content);
			*/
		}
	}
	
        public function is_valid() {
            return $this->_is_valid;
        }
	public function getAppName() {
		if(count($this->app_name) > 1) {
			return $this->app_name[1];
		} else {
			return "";
		}
	}
	
	public function getHeaderImage() {
		if(count($this->header_image) > 1) {
			return $this->header_image[1];
		} else {
			return "";
		}
	}
	
	public function getAppType() {
		if(count($this->app_type) > 1) {
			return $this->app_type[1];
		} else {
			return "";
		}
	}
	
	public function getTags() {
		if(count($this->tags) > 1) {
			return $this->tags[1];
		} else {
			return [];
		}
	}
	
	public function getGenres() {
		if(count($this->genres) > 1 ) {
			return explode(",", $this->genres[1]);
		} else {
			return [];
		}
	}
}




class SteamStoreScrape {
	private $id;
	private $payload;
	private $ratings = [];
	private $recent_ratings = [];
	private $tags = [];
	private $store_available;
	
	function getUrlContent($url, $age_restricted = false){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		if($age_restricted) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, ["Cookie: birthtime=315532801", "Cookie: lastagecheckage=1-January-1980"]);
		}
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		$data = curl_exec($ch);
		$endpage = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
		$match;
		preg_match("/http[s]?:\/\/?[^\/\s]+\/?(.*)/", $endpage, $match);
		$accessible = strlen($match[1]) > 0;
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		return (($httpcode>=200 && $httpcode<300) && $accessible) ? $data : false;
	}
	
	public function __construct($id, $age_restricted) {
		$this->id = $id;
		$this->payload = $this::getUrlContent("http://store.steampowered.com/app/{$id}/", $age_restricted);
		if($this->payload) {
			preg_match("/user_reviews_summary_row.*?Recent Reviews:.*?game_review_summary.*?>(.*?)<.*?/s", $this->payload, $this->recent_ratings);
			preg_match("/user_reviews_summary_row.*?All Reviews:.*?game_review_summary.*?>(.*?)<.*?/s", $this->payload, $this->ratings);
			preg_match_all('/class="app_tag".*?>.*?(\S.*?\S*)[^\S]*</s', $this->payload, $this->tags);
			$this->store_available = true;
		} else {
			$this->store_available = false;
		}
	}
	
	public function getOverallRating() {
            //var_dump($this->ratings);
		if(count($this->ratings) > 1) {
			return $this->ratings[1];
		} else {
			return "";
		}
	}
	
	public function getRecentRating() {
		if(count($this->recent_ratings) > 1) {
			return $this->recent_ratings[1];
		} else {
			return "";
		}
	}
	
	public function getTags() {
		if(count($this->tags) > 1) {
			return $this->tags[1];
		} else {
			return [];
		}
	}
	
	public function getStorepageAvailable() {
		return $this->store_available;
	}
	
}

?>