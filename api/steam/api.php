<?php

require_once("game.php");
require_once("vdf.php");
require_once("key.php");

function formatGame($game) {
    $output = '<div class="cell-outter">';

    $output .= '<div class="cell">';
    $output .= '<div class="tile">';
    $output .= "<img src=\"{$game['game']['thumbnail']}\" class=\"tile\" alt=\"{$game['game']['name']}\" />";
    $output .= '</div>';
    $output .= '<section class="title">';
    $output .= $game['game']['name'];
    if ($game['game']['age_rating'] > 0) {
        $output .= " ({$game['game']['age_rating']})";
    }
    $output .= '</section>';
    $output .= '<section class="store">';
    $output .= "<a href=\"{$game['game']['url']}\" class=\"store\" target=\"_blank\">View in Store</a>";
    $output .= '</section>';
    $output .= '<section class="released">';
    $output .= "Released:<br><span class=\"year\">{$game['game']['release_date']}</span>";
    $output .= '</section>';
    $output .= '<section class="rating">';
    if (count($game['game']['rating']['recent']) > 0) {
        $r = implode(" - ", $game['game']['rating']['recent']);
        $output .= "Recent Rating:<br><span class=\"recent\">{$r}</span><br>";
    }
    if (count($game['game']['rating']['overall']) > 0) {
        $r = implode(" - ", $game['game']['rating']['overall']);
        $output .= "Overall Rating:<br><span class=\"recent\">{$r}</span><br>";
    }
    $output .= '</section>';
    $output .= "";
    $output .= '<section class="tags">
  Genres:
  <div>
  <ul><li>' . implode("</li><li>", $game['game']['genres']) . '</li></ul>
  <div class="clear"></div>
  </div>';
    $output .= 'Tags:
  <div>
  <ul><li>' . implode("</li><li>", $game['game']['tags']) . '</li></ul>
  <div class="clear"></div>
  </div>
  </section>';
    $output .= '</div>
  </div>';


    /*
      $output .= ;
      $output .= ;
      $output .= ;


      <section class="features">
      <div class="category partial-controller-support">&nbsp;</div>
      <div class="category captions-available">&nbsp;</div>
      <div class="category steam-workshop">&nbsp;</div>
      <div class="category vac">&nbsp;</div>
      <div class="category achievements">&nbsp;</div>

      <div class="category steam-cloud">&nbsp;</div>
      <div class="category level-editor">&nbsp;</div>
      <div class="category steam-leaderboards">&nbsp;</div>
      <div class="category mods-hl1">&nbsp;</div>
      <div class="category mods-hl2">&nbsp;</div>

      <div class="category vr-support">&nbsp;</div>
      <div class="category single-player">&nbsp;</div>
      <div class="category multiplayer">&nbsp;</div>
      <div class="category cross-platform-multiplayer">&nbsp;</div>
      <div class="category online-coop">&nbsp;</div>

      <div class="category local-coop">&nbsp;</div>
      <div class="category commentary-available">&nbsp;</div>
      <div class="category source-sdk">&nbsp;</div>
      <div class="category trading-cards">&nbsp;</div>
      <div class="category stats">&nbsp;</div>
      </section>
     */



    return $output;
}

class APIGets {

    private $get;
    public $_output = false;
    public $_detailed = false;
    public $uid = 0;
    public $page = -1;
    public $game;
    public $vdf = false;
    
    private $outputs = [];
    private $errors = [];

    public function __construct() {
        $get = filter_input_array(INPUT_GET);
        $this->output = isset($get['output']) ? $get['output'] == 1 : false;
        $this->detailed = isset($get['detailed']) ? $get['detailed'] == 1 : false;



        if (isset($get['uid'])) {
            $this->uid = (int) $get['uid'];
        } else if (isset($get['vanityurl'])) {
            $vanity = preg_replace("/[^A-Za-z0-9\_\.\~\-]/", "", $get['vanityurl']);
            $this->uid = (int) $this->getSteamId($vanity, $this->output); // 0 on error
        } else if (isset($get['game'])) {
            $this->game = $this->getGame($get['game'], $this->output);
        }
        if (isset($get['page'])) {

            if ($this->uid > 0) {
                $this->page = $get['page'];
                
                $this->getUserGame($this->page, true, true);
            } else {
                $this->errors[] = "Unable to retrieve page when UID not set.";
            }
        }
        if (isset($get['totalpages']) && $this->uid > 0) {
            $this->get_total_games(true);
        }

        if (isset($get['vdf']) && $this->uid > 0) {
            $this->getGameList(true);
        }
        if (isset($get['gamelist']) && $this->uid > 0) {
            $this->getGameList($this->detailed, $this->output);
        } else if (isset($get['delgamelist']) && $this->uid > 0) {
            $this->delGameList($get['delgamelist'], $this->output);
        }
        
        if(count($this->errors)) {
            $this->create_json(0, array_unique($this->errors));
        } else if(count($this->outputs)) {
            $this->create_json(1, $this->outputs);
        }
    }

    // /api/steam/get/user/id/{vanityurl}
    function getSteamId($vanityurl, $output = true) {
        global $key;
        $query = "key={$key}&vanityurl={$vanityurl}"; //steamid = 76561198118958943

        $response = json_decode(file_get_contents('http://api.steampowered.com/ISteamUser/ResolveVanityURL/v0001/?' . $query), true)['response'];

        if ($response["success"] === 1) {
            //if ($output) {
                $this->outputs["steamid"] = $response['steamid'];
                //$this->create_json(1, $response['steamid']);
            //}
            return $response['steamid'];
        } else {
            $this->errors[] = "Unable to retrieve steamID for vanity url: {$vanityurl}.";
            if ($output) {
                //$this->create_json(0, "Unable to retrieve steamID for vanity url: {$vanityurl}.");
            }
            return 0;
        }
    }

    private function getGamelistPath() {
        return "gamelists/{$this->uid}.json";
    }

    // /api/steam/get/user/{steamid}/gamelist/
    // /api/steam/get/user/{steamid}/gamelist/detailed/
    function getGameList($detailed = false, $output = false) {
        $filename = $this->getGamelistPath();
        if (!file_exists($filename)) {
            global $key;
            $query = "key={$key}&steamid={$this->uid}&format=json";
            $response = json_decode(file_get_contents('http://api.steampowered.com/IPlayerService/GetOwnedGames/v0001/?' . $query), true);
            
            if (isset($response['response']['game_count'])) {
                file_put_contents($filename, json_encode($response));
            } else {
                $this->errors[] = "Unable to retrieve game list for steamID: {$this->uid}. Are you sure your profile is public?";
                if ($output) {
                    $this->create_json(0, "Unable to retrieve game list for steamID: {$this->uid}. Are you sure your profile is public?");
                }
                return "";
            }
        }


        if ($detailed === true) {
            $this->outputs["vdf"] = $this->getVDF();
            //$this->create_json(1, $this->getVDF());
        } else if ($output) {
            $this->outputs["gamelist"] = file_get_contents($filename);
            //$this->create_json(1, file_get_contents($filename));
        }
        
        return file_get_contents($filename);
    }

    function get_total_games($output = false) {
        $data = json_decode($this->getGameList(false, false), true);
        $success = isset($data['response']['game_count']);
        if ($success) {
            if ($output == true) {
                $this->outputs["totalgames"] = $data['response']['game_count'];
            }
            return $data['response']['game_count'];
        } else {
            $this->errors[] = "Unable to retrieve game count.";
            return 0;
        }
    }

    function getVDF() {
        $filename = $this->getGamelistPath();
        $apps = $this->getDetailedGamelist($filename);
        return "<pre>" . vdf_encode($apps, true) . "</pre>";
    }

    private function getDetailedGamelist($filename) {
        $array = [];
        if (!file_exists($filename)) {
            $errors[] = "Attempted to get invalid gamelist.";
        } else {
            $list = json_decode(file_get_contents($filename), true);

            foreach ($list['response']['games'] as $elem) {
                $id = $elem['appid'];
                $game = $this->getGame($id, false);
                if (count($game) > 0) {
                    $tags = isset($game["game"]["tags"]) ? $game["game"]["tags"] : []; //explode(",", $game["game"]["tags"]);
                    $cats = isset($game["game"]["categories"]) ? $game["game"]["categories"] : []; //explode(",", $game["game"]["categories"]);
                    $content_new = ["..."];
                    $content_tags = array_merge($content_new, $this->mergeDetails($tags, "Tag: "));
                    $content = array_merge($content_tags, $this->mergeDetails($cats, "Feature: "));
                    $rating = isset($game['game']['rating']['overall'][0]) ? $game['game']['rating']['overall'][0] : "";
                    if (strlen($rating)) {
                        $content[] = 'Rating: ' . $rating;
                    }
                    $array["" . $id] = ["tags" => $content];
                }
            }
        }
        return ["Apps" => $array];
    }

    private function mergeDetails($contentToAdd, $prefix) {
        if (count($contentToAdd)) {
            foreach ($contentToAdd as &$value) {
                $value = $prefix . $value;
            }
            unset($value);
            return $contentToAdd;
        }
        return [];
    }

    // /api/steam/del/user/{steamid}/gamelist
    // 0: failure
    // 1: success
    // 2: no such file
    function delGameList($output = false) {
        $filename = $this->getGamelistPath();
        $ret = 2;
        if (file_exists($filename)) {
            $ret = (int) unlink($filename);
        }

        if ($output) {
            $this->create_json(1, $ret);
        }
        return $ret;
    }

    // /api/steam/get/game/{appid}
    function getGame($appid, $output = true, $formatted = false) {
        
        if($appid === NULL) {
            $this->errors[] = "Invalid app id presented.";
            if ($output) {
                $this->create_json(0, "Invalid app id presented.");
            }
            return "";
        }
        $path = $this->get_game_path($appid);

        // update if game hasn't been updated in a year
        $update = false;

        if (file_exists($path)) {
            $cont = json_decode(file_get_contents($path), true);
            if (time() > ( $cont['game']['rating']['updated'] + (365 * 24 * 60 * 60) )) {
                $update = true;
            }
        }

        $success = true;
        $ret = [];
        if (!file_exists($path) || $update) {
            $success = $this->add_update_game($appid);
        }
        if ($success) {
            $ret = json_decode(file_get_contents($path), true);
            if ($formatted) {
                return formatGame($ret);
            } else if ($output) {
                $this->outputs["game"] = $ret;
                //$this->create_json(1, $ret);
            }
        } else {
            $this->errors[] = "Couldn't retrieve game details for: {$appid}.";
            if ($formatted) {
                return "";
            } else if ($output) {
                $this->create_json(0, "Couldn't retrieve game details for: {$appid}.");
            }
            return "";
        }
        return $ret;
    }

    private function get_game_path($appid) {
        $app_dir = "apps/";
        $fn = "{$appid}.json";
        return $app_dir . $fn;
    }

    private function add_update_game($appid) {
        
        $game = new Game($appid);
        if ($game->is_valid()) {
            $game_arr = ["game" => ["id" => $appid,
                    "name" => $game->getName(),
                    "type" => $game->getAppType(),
                    "thumbnail" => $game->getThumbnail(),
                    "genres" => $game->getGenres(),
                    "categories" => $game->getCategories(),
                    "tags" => $game->getTags(),
                    "store_available" => $game->getStorepageAvailable(),
                    "in_store" => $game->getInStore(),
                    "rating" => ["overall" => [$game->getOverallRating()], //, $game->getOverallPct()],
                        "recent" => [$game->getRecentRating()], //, $game->getRecentPct()],
                        "updated" => $game->getRatingsUpdated()],
                    "age_rating" => (int) $game->getAge(),
                    "release_date" => $game->getReleaseDate(),
                    "url" => $game->getAppURL()
                ]
            ];
            file_put_contents($this->get_game_path($appid), json_encode($game_arr));
            return true;
        } else {
            return false;
        }
    }

    function getUserGame($index, $output = false, $formatted = false) {
        $gamelist = json_decode($this->getGameList($this->uid, false, false), true);
        //var_dump($gamelist);

        $totalgames = $this->get_total_games();
        if($index >= $totalgames || $index < 0) {
            $this->errors[] = "Game index: {$index} not in range. {$totalgames} games available.";
            return "";
        }
        $appid = $gamelist['response']['games'][$index]['appid'];

        $g = $this->getGame($appid, false, $formatted);
        if ($output == true) {
            if ($g != "") {
                $this->outputs["usergame"] = $g;
                //$this->create_json(1, $g);
            }
        }
        return $g;
    }

    function dump() {
        var_dump($this);
    }

    private function create_json($success, $data) {
        $json = ["success" => $success, "data" => $data];
        echo json_encode($json);
    }

}

//
$api = new APIGets();
//echo "<br>";
////$api->dump();
//echo "<br>";
//$api->delGameList(true);
//echo "<br>";
//$filename = "gamelists/{$api->uid}.json";
////var_dump($filename);
////$gamelist = $api->getGameList($api->uid, false, true);
////var_dump($gamelist);
//$api->getUserGame(3, true, true);
//echo "<br>";
//$api->getGameList(true);
?>