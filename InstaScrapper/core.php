<?php

require __DIR__ . '/Mylib/CrawlHelper.php';
require __DIR__ . '/Mylib/DB.php';
require __DIR__ . '/Mylib/Object.php';



define('BASE_PATH', __DIR__);
define('BASE_COMMANDS_PATH', BASE_PATH . '/Commands');

use \Mylib\CrawlHelper as Crawler;
use \Mylib\DB;
use \Mylib\Object;



class core
{
    /**
     * Version
     *
     * @var string
     */
    protected $version = '0.42.0';

    protected $Crawler;


    protected $mysql_credentials_if_user_not_provided = [
       'host'     => 'localhost',
       'user'     => 'kafegame_PhpBot',
       'password' => 'qwertyuiop1234567890',
       'database' => 'kafegame_Crawler',
    ];

    protected $regex_pattern = array(
        // Get  '<script></script>' tag from source code, html
        'category'                          => '/<div class=\"side-category-list\"><a href=\"(.*)\">(.*)<\/a><\/div>/',
        //
        'get_food_name_category_page'      => '/<div class=\"square-thumb\"><a href=\"(.*)\"><img src=\"(.*)\" alt=\"(.*)\"><\/a><\/div>/',
        //
        'get_food_full_category_page_1'      => '/<div class=\"search-text\">((.|\n)*?)<\/p>[ \t\n]+<\/div>/',
        //
        'get_food_full_category_page_2'      => '/<div class=\"search-text\">((.|\n)*?)<\/p>[ \t\n]+<\/div>/',
        //
        'get_ingridient_category_page'       => '/<option class=\"item\" title=\"((.|\n)*?)\" value=\"(.*)<\/option>/',
        // This is RegxPattern to extract JavaScript variables from html source code
        'json'            => '/(?i)<script[[:space:]]+type="text\/JavaScript"(.*?)>([^\a]+?)<\/script>/si',
        // This is RegexPattern to get only json object for php
        'object'          => '/(window\.\_sharedData \=|\;)/',
        // short call json data __a=1
        'filter_response' => 'window._sharedData={"entry_data":{"ProfilePage":[{source}]}};',
        // If in case any error, return this json in ajax response
        'error_response'  => 'window._sharedData = {"error": "{error}"};',
        // This RegexPattern to detect if response is null/empty
        'empty_error'     => '"entry_data": {}',
    );


    protected $api_key = '';

    protected $input;

    protected $upload_path;

    protected $download_path;

    protected $mysql_enabled = false;

    protected $pdo;





    public function __construct()
    {
      $this->Crawler = new Crawler;
    }

    /**
     * Initialize Database connection
     *
     * @param array  $credential
     * @param string $table_prefix
     * @param string $encoding
     *
     * @return \Longman\TelegramBot\Telegram
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function enableMySql(array $credential = null, $table_prefix = null, $encoding = 'utf8mb4')
    {
        $credential = nul === $credential ? $credential : $this->mysql_credentials_if_user_not_provided;
        //$this->pdo = DB::initialize($credential, $this, $table_prefix, $encoding);
        $this->pdo = DB::initialize($credential, $table_prefix, $encoding);
        $this->mysql_enabled = true;

        return $this;
    }


    public function ChiBepazamFullCrawl()
    {
        //$response_array = $this->Crawler->run('get_category_list', ['keyword' => 'rice'], true);
        $response_array = $this->Crawler->getWeb('http://chibepazam.ir/');

        preg_match_all($this->regex_pattern['get_ingridient_category_page'], $response_array, $output_array);
        foreach ($output_array[1] as $ing_name) {
            $ingridient = new Object(['name' => str_replace('&nbsp;', ' ', $ing_name)]);
            DB::insertIngridient($ingridient);
        }
        $missing_ingridients = [
            'پیازداغ', 'روغن', 'پیاز', 'فلفل', 'گندم',
            'بلغور گندم', 'لبو'
        ];
        foreach ($missing_ingridients as $key => $ing) {
            $ingridient = new Object(['name' => $ing]);
            DB::insertIngridient($ingridient);
        }

        preg_match_all($this->regex_pattern['category'], $response_array, $output_array);
        $category = array_combine($output_array[2], $output_array[1]);

        echo "<pre>",print_r($category), "\n";
        foreach ($category as $category_name => $category_page) {
            echo "<pre>", 'Category name : ' .$category_name, "\n";
            $category_obj = new Object(['name' => $category_name, 'priority' => 1]);
            $category_id = DB::insertCategory($category_obj);
            $response_array_2 = $this->Crawler->getWeb($category_page);
            //
            preg_match_all("/<span class='pages'>صفحه (.*?) از (.*?)<\/span>/", $response_array_2, $max_page_raw);
            $max_page = $max_page_raw[2][0];
            $cur_page = $max_page_raw[1][0];
            echo "<pre>", 'Max page : ' .$max_page, "\n";
            // $doc = new DOMDocument();
            // $doc->loadHTML($response_array);
            // $foods = $dom->getElementsByTagName('div class="search-text"');
            // foreach ($foods as $food) {
            //     echo $food->nodeValue, PHP_EOL;
            // }
            //
            //full scan
            //preg_match_all($this->regex_pattern['get_food_full_category_page_1'], $response_array_2, $output_array_1);
            //preg_match_all($this->regex_pattern['get_food_full_category_page_2'], $output_array_1, $output_array_2);
            //echo "<pre>", print_r($output_array), "</pre>";
            for ($i = 1; $i <= $max_page; $i++){
                echo "<pre>", 'page number : ' .$i, "\n";
                $response_array_cat_page = $this->Crawler->getWeb($category_page .'page/' .$i .'/');
                preg_match_all($this->regex_pattern['get_food_name_category_page'], $response_array_cat_page, $output_array);
                $food_in_cat = array_combine($output_array[1], $output_array[3]);
                foreach ($food_in_cat as $food_page => $food_name) {
                    $food =[];
                    $food['name'] = $food_name;
                    $response_array_food_page = $this->Crawler->getWeb($food_page);
                    //
                    preg_match_all("/<img itemprop=\"photo\" class=\"recipe-image\" src=\"(.*?)\" title=\"/", $response_array_food_page, $out);
                    $food['thumb_img_src'] = $out[1][0];
                    preg_match_all("/itemprop=\"prepTime\">(.*)<\/time><\/span><\/a>/", $response_array_food_page, $out);
                    $food['prepTime'] = $out[1][0];
                    preg_match_all("/itemprop=\"cookTime\">(.*)<\/time><\/span><\/a>/", $response_array_food_page, $out);
                    $food['cookTime'] = $out[1][0];
                    preg_match_all("/itemprop=\"servingSize\">(.*)<\/span><\/span><\/a>/", $response_array_food_page, $out);
                    $food['servingSize'] = $out[1][0];
                    preg_match_all("/<ol itemprop=\"instructions\" class=\"recipe-ingredient\">((.|\n)*?)<\/ol>/", $response_array_food_page, $out);
                    preg_match_all("/<li>(.*?)<\/li>/", $out[1][0], $out_2);
                    $food['instruction'] = '';
                    foreach ($out_2[1] as $key => $value) {
                        $k = $key + 1;
                        $food['instruction'] .= strval($k) .'. ' .$value . '\n';
                    }
                    //
                    // echo "<pre>", print_r($food), "</pre>";
                    $food_obj = new Object($food);
                    $food_id = DB::insertFood($food_obj);
                    DB::insertFoodsCategories($food_id, $category_id);
                    //
                    $ing_extract_method = false;
                    if ($ing_extract_method){
                        preg_match_all("/<li><span itemprop=\"ingredient\"(.*?)itemprop=\"name\">((.*?)([\t\d]+|[\t\d]+تا[\t\d]+|نصف)(.*?))<\/span><\/span><\/li>/X", $response_array_food_page, $out);
                        $ingridient =[];
                        for ($k = 0; $k <= count($out); $k++) {
                          $ingridient ['name_description'] = $out[3][$k];
                          // Just for test
                          $ingridient ['name'] = $ingrid[3][$k];
                          $ingridient ['description'] = $out[3][$k];
                          //
                          $ingridient ['amount'] = $out[4][$k];
                          $ingridient ['unit'] = $out[5][$k];
                          $ingridient_id = DB::insertIngridient(new Object($ingridient));
                          $ingridient ['food_id'] = $food_id;
                          $ingridient ['ingridient_id'] = $ingridient_id;
                          //echo "<pre>", print_r($ingridient), "</pre>";
                          DB::insertFoodIngridient(new Object($ingridient));
                        }
                        preg_match_all("/<li><span itemprop=\"ingredient\"(.*?)itemprop=\"name\">((.*?)(\s\s)(.*?))<\/span><\/span><\/li>/X", $response_array_food_page, $out);
                    } else {
                        preg_match_all("/<li><span itemprop=\"ingredient\"(.*?)itemprop=\"name\">(.*?)<\/span><\/span><\/li>/X", $response_array_food_page, $out);
                        $ingridient =[];
                        for ($k = 0; $k <= count($out); $k++) {
                          $ingridient ['name_description'] = $out[2][$k];
                          $ingridient ['description'] = $out[2][$k];
                          //
                          $match = self::getSimilarIngridient($ingridient ['name_description']);
                          $ingridient ['ingridient_id'] = $match['id']; //$igridient_id = DB::insertIngridient(new Object($ingridient));
                          $ingridient ['name'] = $match['name'];
                          //
                          //
                          preg_match("/(.*?)([\/\d]+)(.*)/", $out[2][$k], $output_a);
                          //$ingridient ['name'] = $output_a[1];
                          $ingridient ['amount'] = $output_a[2];
                          $ingridient ['unit'] = $output_a[3];

                          $ingridient ['food_id'] = $food_id;
                          //echo "<pre>", print_r($ingridient), "</pre>";
                          //echo "<pre>", $ingridient ['name_description'], "\n";
                          DB::insertFoodIngridient(new Object($ingridient));
                        }
                    }

                }
            }
        }


        //echo $response_array;//load main site
        return true;


    }



    public function getSimilarIngridient($name_description)
    {
        // echo "<pre>",$name_description, "\n";
        $ingridient = [];
        $ingridient['id'] = 0;
        $ingridient['name'] = $name_description;
        //
        $ing_master_list_raw = DB::selectIngridients();
        $ing_master_list = array_combine(array_column($ing_master_list_raw, 'id'), array_column($ing_master_list_raw, 'name'));
        $word_splitted = preg_split("/ +/u", $name_description);
        $del_vals = ['ق', 'غ', 'حدودا', 'نفر', 'یک', 'دو', 'سه', 'چهار', 'پنج', 'شش', 'هفت', 'هشت', 'نه', 'پیمانه', 'قاشق', 'غذا', 'خوری'];
        foreach ($del_vals as $key => $del_val) {
            if (($k = array_search($del_val, $word_splitted)) !== false) {
                unset($word_splitted[$k]);
            }
        }
        foreach ($word_splitted as $key => $value) {
            preg_match("/(.*?)([\d ()\\:\/=]+)/", $word_splitted[$key], $output);
            if (!empty($output)){
                $word_splitted[$key] = $output[1];
            }
            preg_replace("/[\d ()\\:\/=]+/", "", $word_splitted[$key]);
        }
        //
        for ($ijk=0; $ijk <Count($word_splitted) ; $ijk++) {
            $index_start = $ijk;
            $index_end = $ijk + 1;
            $query_word = $word_splitted[$index_start];
            $found_array = preg_grep("/.*" .$query_word .".*/u", $ing_master_list);
            if (COUNT($found_array) > 0){
                //echo "<pre> 11111111111111111111111111111111", $query_word, "\n", print_r($found_array) , "\n", print_r($word_splitted), "\n ssssdddddddddddddddddddddddddddddddddd";
                while (COUNT($found_array) > 1){
                    // echo "<pre>", $query_word, "\n", print_r($found_array) , "\n", print_r($word_splitted), "\n ssssdddddddddddddddddddddddddddddddddd";
                    $query_word = $query_word .' ' .$word_splitted[$index_end];
                    $found_array = preg_grep("/.*" .$query_word .".*/u", $ing_master_list);
                    $index_end++;
                }
                $query_word = $word_splitted[$index_start];
                if (COUNT($found_array) === 1){
                    $keys = array_keys($found_array);
                    $ingridient['id'] = array_search($found_array[$keys[0]], $ing_master_list);
                    $ingridient['name'] = $found_array[$keys[0]];
                    break;
                } elseif (COUNT($found_array) === 0) {
                    for ($i1=1; $i1 <$index_end-1 ; $i1++) {
                        $query_word = $query_word .' ' .$word_splitted[$i1];
                    }
                    $found_array = preg_grep("/.*" .trim($query_word) .".*/u", $ing_master_list);
                    // $keys = array_keys($found_array);
                    // $ingridient['id'] = array_search($found_array[$keys[0]], $ing_master_list);
                    // $ingridient['name'] = $found_array[$keys[0]];
                    $ingridient['id'] = array_search($query_word, $found_array);
                    $ingridient['name'] = $found_array[$ingridient['id']];
                    break;
                }

            }
        }
        if ($ingridient['id'] === null || $ingridient['id'] === 0){
            echo "<pre>",$name_description, "\n";
        }

        // else {
        //     foreach ($ing_master_list as $key => $value) {
        //         $ing_name_word_count = COUNT(preg_split("/ /u", $value));
        //         for ($ii = 1; $ii <$ing_name_word_count; $ii++){
        //             $query_word = $query_word .$word_splitted[$ii];
        //         }
        //         $similar_chars_count = similar_text($value, $query_word, $percent);
        //         if ($percent > 80){
        //             $ingridient['id'] = $key;
        //             $ingridient['name'] = $value;
        //             break;
        //         }
        //     }
        // }
        // echo "<pre>", print_r($ingridient), "\n";
        return $ingridient;

    }




    public function getCategoryFoodList($category_name, $full_scan = true)
    {
        $response_array = $this->Crawler->run('get_category_list', ['keyword' => $category_name]);
        echo "<pre>", print_r($category_name), "</pre>";
        echo "<pre>", print_r($response_array), "</pre>";
        return true;


    }







    public function searchUserfromInsta($username)
    {
        $response_array = $this->Crawler->run('top_search', ['keyword' => $username]);
        //
        foreach ($response_array['users'] as $data) {
            $user = new User($data);
            if (DB::isDbConnected()) {
                DB::insertUser($user);
            }
        }

        return true;
    }














    /**
     * Check if user required the db connection
     *
     * @return bool
     */
    public function isDbEnabled()
    {
        if ($this->mysql_enabled) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * Get API key
     *
     * @return string
     */
    public function getApiKey()
    {
        return $this->api_key;
    }



    /**
     * Get Version
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }


}
