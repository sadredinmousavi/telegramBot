<?php

require __DIR__ . '/Mylib/InstagramHelper.php';
require __DIR__ . '/Mylib/DB.php';
require __DIR__ . '/Mylib/User.php';
require __DIR__ . '/Mylib/Media.php';


define('BASE_PATH', __DIR__);
define('BASE_COMMANDS_PATH', BASE_PATH . '/Commands');

use \Mylib\InstagramHelper as Instagram;
use \Mylib\DB;
use \Mylib\User;
use \Mylib\Media;
// use Exception;
// use PDO;
// use RecursiveDirectoryIterator;
// use RecursiveIteratorIterator;
// use RegexIterator;

class core
{
    /**
     * Version
     *
     * @var string
     */
    protected $version = '0.42.0';

    protected $Instagram;


    protected $mysql_credentials_if_user_not_provided = [
       'host'     => 'localhost',
       'user'     => 'kafegame_PhpBot',
       'password' => 'qwertyuiop1234567890',
       'database' => 'kafegame_instagram',
    ];

    /**
     * Telegram API key
     *
     * @var string
     */
    protected $api_key = '';

    /**
     * Telegram Bot username
     *
     * @var string
     */
    protected $bot_username = '';

    /**
     * Telegram Bot id
     *
     * @var string
     */
    protected $bot_id = '';

    /**
     * Raw request data (json) for webhook methods
     *
     * @var string
     */
    protected $input;

    /**
     * Custom commands paths
     *
     * @var array
     */
    protected $commands_paths = [];

    /**
     * Current Update object
     *
     * @var \Longman\TelegramBot\Entities\Update
     */
    protected $update;

    /**
     * Upload path
     *
     * @var string
     */
    protected $upload_path;

    /**
     * Download path
     *
     * @var string
     */
    protected $download_path;

    /**
     * MySQL integration
     *
     * @var boolean
     */
    protected $mysql_enabled = false;

    /**
     * PDO object
     *
     * @var \PDO
     */
    protected $pdo;

    /**
     * Commands config
     *
     * @var array
     */
    protected $commands_config = [];

    /**
     * Admins list
     *
     * @var array
     */
    protected $admins_list = [];

    /**
     * ServerResponse of the last Command execution
     *
     * @var \Longman\TelegramBot\Entities\ServerResponse
     */
    protected $last_command_response;

    /**
     * Botan.io integration
     *
     * @var boolean
     */
    protected $botan_enabled = false;







    public function __construct()
    {
      $this->Instagram = new Instagram;
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


    public function getUserfromInsta($username, $full_scan = false)
    {
        $response_array = $this->Instagram->run('get_user_info', ['keyword' => $username]);

        if(null === $all_medias = $response_array['user']['media']){
            return false;
        }

        $user = new User($response_array);

        //echo json_encode($response_array, true);
        if (DB::isDbConnected()) {
            DB::insertUser($user);
            $all_medias = $response_array['user']['media'];
            $end_cursor = $all_medias['page_info']['end_cursor'];
            $has_next = $all_medias['page_info']['has_next_page'];
            $count = $all_medias['count'];
            //
            foreach ($all_medias['nodes'] as $media_raw) {
                $media = new Media($media_raw);
                DB::insertMedia($media);
            }
            //
            $this->getUserLastMediafromInsta($username);
            //
            if ($full_scan){
                do {
                    $response_array = $this->Instagram->run('get_user_info', ['keyword' => $username, 'max_id' => $end_cursor]);
                    //echo $end_cursor .'s               s';
                    $all_medias = $response_array['user']['media'];
                    //
                    $end_cursor = $all_medias['page_info']['end_cursor'];
                    $has_next = $all_medias['page_info']['has_next_page'];
                    $all_medias = $response_array['user']['media'];
                    foreach ($all_medias['nodes'] as $media_raw) {
                        $media = new Media($media_raw);
                        //extracting download link
                        //$Raw_data = $this->getMediaDetailsfromInsta($media->getProperty('code'));
                        //$media['scanned'] = 1;
                        //hala mishavad goft scan anjam shode
                        //
                        DB::insertMedia($media);
                    }
                    //
                } while ($has_next && $full_scan);
                //
                $this->getUserLastMediafromInsta($username);
                //
            }
        }

        return true;
    }







    public function searchUserfromInsta($username)
    {
        $response_array = $this->Instagram->run('top_search', ['keyword' => $username]);
        //
        foreach ($response_array['users'] as $data) {
            $user = new User($data);
            if (DB::isDbConnected()) {
                DB::insertUser($user);
            }
        }

        return true;
    }









    public function getUserLastMediafromInsta($username)
    {

        //
        //
        //NEW  METHOD
        //
        //
        if (DB::isDbConnected()) {
            $medias = DB::selectMedia(['videos' => true, 'images' => true, 'carousels' => true], ['special_user_name' => $username, 'scanned' => 0]);
            foreach ($medias as $media) {
                $response_array = $this->Instagram->run('get_media_details', ['keyword' => $media['code']]);
                $media_raw = $response_array['graphql']['shortcode_media'];
                if (DB::isDbConnected()) {
                    $media = new Media($media_raw);
                    DB::insertMedia($media);
                }
            }
        }

        return true;
        //
        //
        //METHOD DEPRECATED
        //
        //


        $response_array = $this->Instagram->run('get_user_last_media', ['keyword' => $username]);
        //
        echo "<pre> s2 ";
        print_r($response_array);
        echo "</pre>";
        if (DB::isDbConnected()) {
            foreach ($response_array['items'] as $media_raw) {
                $media = new Media($media_raw);
                DB::insertMedia($media);
            }
        }

        return true;
    }






    public function getRelatedMediafromInsta($channel_id, $how_many_needed = 30)
    {
        $related_found = 0;
        $s = DB::selectChannels();
        $medias = DB::selectMedia(['videos' => true, 'images' => true, 'carousels' => false], ['user_belongs_to_channel' => $channel_id, 'sort_by' => 'likes'], $date_from = strtotime("-2 day"));
        foreach ($medias as $media) {
            if ($related_found >= $how_many_needed){
                break;
            }
            $response_array = $this->Instagram->run('get_media_details', ['keyword' => $media['code']]);
            $media_raw_related = $response_array['graphql']['shortcode_media']['edge_web_media_to_related_media']['edges'];
            $codes = [];
            if (empty($media_raw_related)){
              echo "C" .PHP_EOL;
                continue;
            }
            echo "<pre>";
            print_r($media_raw_related);
            echo "</pre>";
            foreach ($media_raw_related as $edge) {
                $codes []= $edge['node']['shortcode'];
            }
            $Dcodes = DB::checkExistingMediaCode($codes);
            foreach ($Dcodes as $raw) {
                if (($key = array_search($raw, $codes)) !== false) {
                    unset($codes[$key]);
                }
            }

            foreach ($codes as $mediaCode) {
                $response_array = $this->Instagram->run('get_media_details', ['keyword' => $mediaCode]);
                $media_raw = $response_array['graphql']['shortcode_media'];
                if ($media_raw['owner']['is_verified']){
                    $user = new User($media_raw['owner']);
                    DB::insertUser($user);
                    //
                    $media = new Media($media_raw);
                    DB::insertMedia($media);
                    //
                    $related_found = $related_found + 1;
                    echo "<pre>";
                    print_r($user);
                    print_r($media);
                    echo "</pre>";
                }
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
     * Add a single custom commands path
     *
     * @param string $path   Custom commands path to add
     * @param bool   $before If the path should be prepended or appended to the list
     *
     * @return \Longman\TelegramBot\Telegram
     */
    public function addCommandsPath($path, $before = true)
    {
        if (!is_dir($path)) {
            TelegramLog::error('Commands path "%s" does not exist.', $path);
        } elseif (!in_array($path, $this->commands_paths, true)) {
            if ($before) {
                array_unshift($this->commands_paths, $path);
            } else {
                $this->commands_paths[] = $path;
            }
        }

        return $this;
    }

    /**
     * Add multiple custom commands paths
     *
     * @param array $paths  Custom commands paths to add
     * @param bool  $before If the paths should be prepended or appended to the list
     *
     * @return \Longman\TelegramBot\Telegram
     */
    public function addCommandsPaths(array $paths, $before = true)
    {
        foreach ($paths as $path) {
            $this->addCommandsPath($path, $before);
        }

        return $this;
    }

    /**
     * Return the list of commands paths
     *
     * @return array
     */
    public function getCommandsPaths()
    {
        return $this->commands_paths;
    }

    /**
     * Set custom upload path
     *
     * @param string $path Custom upload path
     *
     * @return \Longman\TelegramBot\Telegram
     */
    public function setUploadPath($path)
    {
        $this->upload_path = $path;

        return $this;
    }

    /**
     * Get custom upload path
     *
     * @return string
     */
    public function getUploadPath()
    {
        return $this->upload_path;
    }

    /**
     * Set custom download path
     *
     * @param string $path Custom download path
     *
     * @return \Longman\TelegramBot\Telegram
     */
    public function setDownloadPath($path)
    {
        $this->download_path = $path;

        return $this;
    }

    /**
     * Get custom download path
     *
     * @return string
     */
    public function getDownloadPath()
    {
        return $this->download_path;
    }

    /**
     * Set command config
     *
     * Provide further variables to a particular commands.
     * For example you can add the channel name at the command /sendtochannel
     * Or you can add the api key for external service.
     *
     * @param string $command
     * @param array  $config
     *
     * @return \Longman\TelegramBot\Telegram
     */
    public function setCommandConfig($command, array $config)
    {
        $this->commands_config[$command] = $config;

        return $this;
    }

    /**
     * Get command config
     *
     * @param string $command
     *
     * @return array
     */
    public function getCommandConfig($command)
    {
        return isset($this->commands_config[$command]) ? $this->commands_config[$command] : [];
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
