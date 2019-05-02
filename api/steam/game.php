<?php

require_once("scrape.php");

class Game {

    private $appid;
    private $_is_valid = true;
    private $app_name = "";
    private $scrape;
    private $steamScrape;
    private $header_image = "";
    private $age = -1;
    private $release_date = "";
    private $categories = [];
    private $genres = [];
    private $tags = [];
    private $rating = [];
    private $in_store = true;
    private $store_available = true;
    private $app_type = "";

    function parseHeaders($headers) {
        $head = array();
        foreach ($headers as $k => $v) {
            $t = explode(':', $v, 2);
            if (isset($t[1]))
                $head[trim($t[0])] = trim($t[1]);
            else {
                $head[] = $v;
                if (preg_match("#HTTP/[0-9\.]+\s+([0-9]+)#", $v, $out))
                    $head['response_code'] = intval($out[1]);
            }
        }
        return $head;
    }

    public function __construct($id) {
        $this->appid = $id;
        $code = 0;
        $response;

        do {
            $response = file_get_contents("http://store.steampowered.com/api/appdetails?appids={$id}");
            $code = $this->parseHeaders($http_response_header)['response_code'];
            //sleep(1); // avoid getting blocked by steam
            if ($code == 429) {
                sleep(10);
            }
        } while ($code == 429);

        $response = json_decode($response, true);


        if ($response[$id]['success'] == true) {


            $response = $response[$id]['data'];
            $this->app_name = $response['name'];
            $this->header_image = $response['header_image'];
//			echo $this->name . "<br>";
            $this->age = (int) $response['required_age'];
            $this->release_date = $response['release_date']['date'];
            $this->app_type = $response['type'];
            if (array_key_exists("categories", $response)) {
                $this->categories = $response['categories'];
            }
            if (array_key_exists("genres", $response)) {
                $this->genres = $response['genres'];
            }
            $this->steamScrape = new SteamStoreScrape($id, $this->age > 0);

            if ($this->steamScrape->getStorepageAvailable()) {
                $this->tags = $this->steamScrape->getTags();
            } else {
                $this->scrape = new SteamDBScrape($id, true);
                $this->store_available = false;
                $this->tags = $this->scrape->getTags();
                $this->tags[] = 'Not on Steam Store';
            }

            //$this->steamScrape->getOverallRating();
        } else {
            $scrape = new SteamDBScrape($id, true);
            if ($scrape->is_valid()) {
                $this->scrape = $scrape;


                $this->in_store = false;
                $this->store_available = false;

                $this->app_name = $this->scrape->getAppName();
                $this->header_image = $this->scrape->getHeaderImage();
                $this->app_type = $this->scrape->getAppType();
                $this->genres = $this->scrape->getGenres();
                $this->tags = $this->scrape->getTags();
                $this->tags[] = 'Not on Steam Store';
            } else {
                $this->_is_valid = false;
            }
        }
    }
    
    public function is_valid() {
        return $this->_is_valid;
    }


    function getName() {
        if (is_array($this->app_name)) {
            return $this->app_name[0];
        } else {
            return $this->app_name;
        }
    }

    function getTags() {
        return $this->tags;
    }

    function getFormattedTags() {
        return count($this->getTags()) ? "<ul><li>" . implode("</li><li>", $this->getTags()) . "</li></ul>" : "";
    }

    function getThumbnail() {
        return stripslashes($this->header_image);
    }

    function getHasAdultContent() {
        return $this->required_age > 0;
    }

    function getReleaseDate() {
        return $this->release_date;
    }

    function getCategories() {
        if (count($this->categories)) {
            if (is_array($this->categories[0])) {
                $tmp = [];
                foreach ($this->categories as $i) {
                    $tmp[] = $i['description'];
                }
                $this->categories = $tmp;
            }
            return $this->categories;
        } else {
            return [];
        }
    }

    function getGenres() {
        if (count($this->genres)) {
            if (is_array($this->genres[0])) {
                $tmp = [];
                foreach ($this->genres as $i) {
                    $tmp[] = $i['description'];
                }
                $this->genres = $tmp;
            }
            return $this->genres;
        } else {
            return [];
        }
    }

    function getFormattedGenres() {

        return count($this->getGenres()) ? "<ul><li>" . implode("</li><li>", $this->getGenres()) . "</li></ul>" : "";
    }

    function getAppType() {
        return $this->app_type;
    }

    function getStorepageAvailable() {
        return $this->store_available;
    }

    function getInStore() {
        return $this->in_store;
    }

    function getOverallRating() {
        if (isset($this->steamScrape)) {
            return $this->steamScrape->getOverallRating();
        } else {
            return "";
        }
    }

// 	function getOverallPct() {
// 		if(isset($this->steamScrape)) {
// 			return $this->steamScrape->getOverallPct();
// 		} else {
// 			return "";
// 		}
// 	}

    function getRecentRating() {
        if (isset($this->steamScrape)) {
            return $this->steamScrape->getRecentRating();
        } else {
            return "";
        }
    }

// 	function getRecentPct() {
// 		if(isset($this->steamScrape)) {
// 			return $this->steamScrape->getRecentPct();
// 		} else {
// 			return "";
// 		}
// 	}

    function getRatingsUpdated() {
        return time();
    }

    function getAppURL() {
        if ($this->store_available) {
            return "http://store.steampowered.com/app/{$this->appid}/";
        } else {
            return "https://steamdb.info/app/{$this->appid}/";
        }
    }

    function getAge() {
        return $this->age;
    }

    function getVDF() {
        return [$this->appid => ["tags" => array_merge($this->getCategories(), $this->getTags(), ["..."])]];
    }

    function getMergedSteamTags() {
        $tags = array_merge($this->getCategories(), $this->getTags(), ["..."]);
    }

    function str_replace_json($search, $replace, $subject) {
        return json_decode(str_replace($search, $replace, json_encode($subject)));
    }

}

?>