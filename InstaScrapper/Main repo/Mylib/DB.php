<?php

namespace Mylib;

use PDO;
use PDOException;
use Exception;

class DB
{
    /**
     * MySQL credentials
     *
     * @var array
     */
    static protected $mysql_credentials = [];

    /**
     * PDO object
     *
     * @var PDO
     */
    static protected $pdo;

    /**
     * Table prefix
     *
     * @var string
     */
    static protected $table_prefix;

    /**
     * Telegram class object
     *
     * @var \Longman\TelegramBot\Telegram
     */
    static protected $telegram;

    /**
     * Initialize
     *
     * @param array                         $credentials  Database connection details
     * @param \Longman\TelegramBot\Telegram $telegram     Telegram object to connect with this object
     * @param string                        $table_prefix Table prefix
     * @param string                        $encoding     Database character encoding
     *
     * @return PDO PDO database object
     * @throws \Longman\TelegramBot\Exception\
     */
    public static function initialize(
        array $credentials,
        //Telegram $telegram,
        $table_prefix = null,
        $encoding = 'utf8mb4'
    ) {
        if (empty($credentials)) {
            throw new Exception('MySQL credentials not provided!');
        }

        $dsn     = 'mysql:host=' . $credentials['host'] . ';dbname=' . $credentials['database'];
        $options = [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . $encoding];
        try {
            $pdo = new PDO($dsn, $credentials['user'], $credentials['password'], $options);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }

        self::$pdo               = $pdo;
        //self::$telegram          = $telegram;
        self::$mysql_credentials = $credentials;
        self::$table_prefix      = $table_prefix;

        self::defineTables();

        return self::$pdo;
    }

    /**
     * External Initialize
     *
     * Let you use the class with an external already existing Pdo Mysql connection.
     *
     * @param PDO                           $external_pdo_connection PDO database object
     * @param \Longman\TelegramBot\Telegram $telegram                Telegram object to connect with this object
     * @param string                        $table_prefix            Table prefix
     *
     * @return PDO PDO database object
     * @throws \Longman\TelegramBot\Exception\Exception
     */
    // public static function externalInitialize(
    //     $external_pdo_connection,
    //     Telegram $telegram,
    //     $table_prefix = null
    // ) {
    //     if ($external_pdo_connection === null) {
    //         throw new Exception('MySQL external connection not provided!');
    //     }
    //
    //     self::$pdo               = $external_pdo_connection;
    //     self::$telegram          = $telegram;
    //     self::$mysql_credentials = [];
    //     self::$table_prefix      = $table_prefix;
    //
    //     self::defineTables();
    //
    //     return self::$pdo;
    // }

    /**
     * Define all the tables with the proper prefix
     */
    protected static function defineTables()
    {
        $tables = [
            'users',
            'medias',
            'published',
            'channels_users',
            'channels',
            'black_words',
            'admins_channels',
            'cronjobs',
        ];
        foreach ($tables as $table) {
            $table_name = 'TB_' . strtoupper($table);
            if (!defined($table_name)) {
                define($table_name, self::$table_prefix . $table);
            }
        }
    }

    /**
     * Check if database connection has been created
     *
     * @return bool
     */
    public static function isDbConnected()
    {
        return self::$pdo !== null;
    }

    /**
     * Get the PDO object of the connected database
     *
     * @return \PDO
     */
    public static function getPdo()
    {
        return self::$pdo;
    }

    /**
     * Fetch update(s) from DB
     *
     * @param int $limit Limit the number of updates to fetch
     *
     * @return array|bool Fetched data or false if not connected
     * @throws \Longman\TelegramBot\Exception\Exception
     */
    public static function selectTelegramUpdate($limit = null)
    {
        if (!self::isDbConnected()) {
            return false;
        }

        try {
            $sql = '
                SELECT `id`
                FROM `' . TB_TELEGRAM_UPDATE . '`
                ORDER BY `id` DESC
            ';

            if ($limit !== null) {
                $sql .= 'LIMIT :limit';
            }

            $sth = self::$pdo->prepare($sql);
            $sth->bindParam(':limit', $limit, PDO::PARAM_INT);
            $sth->execute();

            return $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Fetch message(s) from DB
     *
     * @param int $limit Limit the number of messages to fetch
     *
     * @return array|bool Fetched data or false if not connected
     * @throws \Longman\TelegramBot\Exception\Exception
     */
    public static function selectMessages($limit = null)
    {
        if (!self::isDbConnected()) {
            return false;
        }

        try {
            $sql = '
                SELECT *
                FROM `' . TB_MESSAGE . '`
                WHERE `update_id` != 0
                ORDER BY `message_id` DESC
            ';

            if ($limit !== null) {
                $sql .= 'LIMIT :limit';
            }

            $sth = self::$pdo->prepare($sql);
            $sth->bindParam(':limit', $limit, PDO::PARAM_INT);
            $sth->execute();

            return $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Convert from unix timestamp to timestamp
     *
     * @param int $time Unix timestamp (if null, current timestamp is used)
     *
     * @return string
     */
    protected static function getTimestamp($time = null)
    {
        if ($time === null) {
            $time = time();
        }

        return date('Y-m-d H:i:s', $time);
    }

    /**
     * Convert array of Entity items to a JSON array
     *
     * @todo Find a better way, as json_* functions are very heavy
     *
     * @param array|null $entities
     * @param mixed      $default
     *
     * @return mixed
     */
    public static function entitiesArrayToJson($entities, $default = null)
    {
        if (!is_array($entities)) {
            return $default;
        }

        //Convert each Entity item into an object based on its JSON reflection
        $json_entities = array_map(function ($entity) {
            return json_decode($entity, true);
        }, $entities);

        return json_encode($json_entities);
    }

    /**
     * Insert entry to telegram_update table
     *
     * @param int $id
     * @param int $chat_id
     * @param int $message_id
     * @param int $inline_query_id
     * @param int $chosen_inline_result_id
     * @param int $callback_query_id
     * @param int $edited_message_id
     *
     * @return bool If the insert was successful
     * @throws \Longman\TelegramBot\Exception\Exception
     */
    public static function insertUser(User $user) {

        if (!self::isDbConnected()) {
            return false;
        }

        try {
            $scanned = 0;
            if(array_key_exists('id', $user) && array_key_exists('biography', $user)){
                $scanned = 1;
            }
            $sth = self::$pdo->prepare('
                INSERT IGNORE INTO `' . TB_USERS . '`
                (`id`, `username`, `full_name`, `is_private`, `is_verified`, `scanned`, `followed_by`, `follows`, `profile_pic_url`, `profile_pic_url_hd`, `biography`, `connected_fb_page`, `external_url`, `external_url_linkshimmed`, `logging_page_id`, `created_at`, `updated_at`)
                VALUES
                (:id, :username, :full_name, :is_private, :is_verified, :scanned, :followed_by, :follows, :profile_pic_url, :profile_pic_url_hd, :biography, :connected_fb_page, :external_url, :external_url_linkshimmed, :logging_page_id, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    `is_private`                = :is_private,
                    `is_verified`               = :is_verified,
                    `scanned`                   = :scanned,
                    `followed_by`               = :followed_by,
                    `follows`                   = :follows,
                    `profile_pic_url`           = :profile_pic_url,
                    `profile_pic_url_hd`        = :profile_pic_url_hd,
                    `biography`                 = :biography,
                    `external_url`              = :external_url,
                    `external_url_linkshimmed`  = :external_url_linkshimmed,
                    `scanned`                   = :scanned,
                    `updated_at`                = NOW()
            ');
            if ($scanned){
                $a1 = $user->getProperty('id'); $sth->bindParam(':id', $a1, PDO::PARAM_INT);
                $a2 = $user->getProperty('followed_by'); $sth->bindParam(':followed_by', $a2, PDO::PARAM_INT);
            } else {
                $a3 = $user->getProperty('pk'); $sth->bindParam(':id', $a3, PDO::PARAM_INT);
                $a4 = $user->getProperty('follower_count'); $sth->bindParam(':followed_by', $a4, PDO::PARAM_INT);
            }
            // $sth->bindParam(':username', $user->getProperty('username'), PDO::PARAM_STR, 255);
            // $sth->bindParam(':full_name', $user->getProperty('full_name'), PDO::PARAM_STR, 255);
            // $sth->bindParam(':is_private', $user->getProperty('is_private'), PDO::PARAM_INT);
            // $sth->bindParam(':is_verified', $user->getProperty('is_verified'), PDO::PARAM_INT);
            // $sth->bindParam(':scanned', $scanned, PDO::PARAM_INT);
            // $sth->bindParam(':follows', $user->getProperty('follows'), PDO::PARAM_INT);
            // $sth->bindParam(':profile_pic_url', $user->getProperty('profile_pic_url'), PDO::PARAM_STR, 255);
            // $sth->bindParam(':profile_pic_url_hd', $user->getProperty('profile_pic_url_hd'), PDO::PARAM_STR, 255);
            // $sth->bindParam(':biography', $user->getProperty('biography'), PDO::PARAM_STR);
            // $sth->bindParam(':connected_fb_page', $user->getProperty('connected_fb_page'), PDO::PARAM_STR, 255);
            // $sth->bindParam(':external_url', $user->getProperty('external_url'), PDO::PARAM_STR, 255);
            // $sth->bindParam(':external_url_linkshimmed', $user->getProperty('external_url_linkshimmed'), PDO::PARAM_STR, 255);
            // $sth->bindParam(':logging_page_id', $user->getProperty('logging_page_id'), PDO::PARAM_STR, 255);

            $a5 = $user->getProperty('username'); $sth->bindParam(':username', $a5, PDO::PARAM_STR, 255);
            $a6 = $user->getProperty('full_name'); $sth->bindParam(':full_name', $a6, PDO::PARAM_STR, 255);
            $a7 = $user->getProperty('is_private'); $sth->bindParam(':is_private', $a7, PDO::PARAM_INT);
            $a8 = $user->getProperty('is_verified'); $sth->bindParam(':is_verified', $a8, PDO::PARAM_INT);
            $sth->bindParam(':scanned', $scanned, PDO::PARAM_INT);
            $a9 = $user->getProperty('follows'); $sth->bindParam(':follows', $a9, PDO::PARAM_INT);
            $a10 = $user->getProperty('profile_pic_url'); $sth->bindParam(':profile_pic_url', $a10, PDO::PARAM_STR, 255);
            $a11 = $user->getProperty('profile_pic_url_hd'); $sth->bindParam(':profile_pic_url_hd', $a11, PDO::PARAM_STR, 255);
            $a12 = $user->getProperty('biography'); $sth->bindParam(':biography', $a12, PDO::PARAM_STR);
            $a13 = $user->getProperty('connected_fb_page'); $sth->bindParam(':connected_fb_page', $a13, PDO::PARAM_STR, 255);
            $a14 = $user->getProperty('external_url'); $sth->bindParam(':external_url', $a14, PDO::PARAM_STR, 255);
            $a15 = $user->getProperty('external_url_linkshimmed'); $sth->bindParam(':external_url_linkshimmed', $a15, PDO::PARAM_STR, 255);
            $a16 = $user->getProperty('logging_page_id'); $sth->bindParam(':logging_page_id', $a16, PDO::PARAM_STR, 255);

            return $sth->execute();
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }






    public static function insertMedia(Media $media) {

        if (!self::isDbConnected()) {
            return false;
        }

        try {
            if ($media->getProperty('scanned')){
                $sth = self::$pdo->prepare('
                    INSERT IGNORE INTO `' . TB_MEDIAS . '`
                    (`id`, `user_id`, `caption`, `type`, `code`, `location`, `likes`, `video_views`, `comments`, `scanned`, `link`, `thumbnail_src`, `img_thumbnail`, `img_standard_resolution`, `img_low_resolution`, `vid_low_bandwidth`, `vid_low_resolution`, `vid_standard_resolution`, `created_time`, `updated_at`)
                    VALUES
                    (:id, :user_id, :caption, :type1, :code1, :location, :likes, :video_views, :comments, :scanned, :link, :thumbnail_src, :img_thumbnail, :img_standard_resolution, :img_low_resolution, :vid_low_bandwidth, :vid_low_resolution, :vid_standard_resolution, FROM_UNIXTIME(:created_time), NOW())
                    ON DUPLICATE KEY UPDATE
                        `location`                 = :location,
                        `likes`                    = :likes,
                        `video_views`              = :video_views,
                        `comments`                 = :comments,
                        `scanned`                  = :scanned,
                        `link`                     = :link,
                        `thumbnail_src`            = :thumbnail_src,
                        `img_thumbnail`            = :img_thumbnail,
                        `img_standard_resolution`  = :img_standard_resolution,
                        `img_low_resolution`       = :img_low_resolution,
                        `vid_low_resolution`       = :vid_low_resolution,
                        `vid_standard_resolution`  = :vid_standard_resolution,
                        `vid_low_bandwidth`        = :vid_low_bandwidth,
                        `created_time`               = FROM_UNIXTIME(:created_time),
                        `updated_at`               = NOW()
                ');

                $a1 = $media->getProperty('id'); $sth->bindParam(':id', $a1, PDO::PARAM_INT);
                $a2 = $media->getProperty('user_id'); $sth->bindParam(':user_id', $a2, PDO::PARAM_INT);
                $a3 = $media->getProperty('caption'); $sth->bindParam(':caption', $a3, PDO::PARAM_STR, 1800);
                $a4 = $media->getProperty('type'); $sth->bindParam(':type1', $a4, PDO::PARAM_STR, 100);
                $a5 = $media->getProperty('code'); $sth->bindParam(':code1', $a5, PDO::PARAM_STR, 100);
                $a6 = json_encode($media->getProperty('location'), true); $sth->bindParam(':location', $a6, PDO::PARAM_STR, 100);
                $a7 = $media->getProperty('likes'); $sth->bindParam(':likes', $a7, PDO::PARAM_INT);
                $a8 = $media->getProperty('video_views'); $sth->bindParam(':video_views', $a8, PDO::PARAM_INT);
                $a9 = $media->getProperty('comments'); $sth->bindParam(':comments', $a9, PDO::PARAM_INT);
                $a10 = $media->getProperty('scanned'); $sth->bindParam(':scanned', $a10, PDO::PARAM_INT);
                //$a11 = $media->getProperty('published'); $sth->bindParam(':published', $a11, PDO::PARAM_INT);
                $a11 = $media->getProperty('created_time'); $sth->bindParam(':created_time', $a11, PDO::PARAM_STR);
                //
                $a12 = $media->getProperty('link'); $sth->bindParam(':link', $a12, PDO::PARAM_STR, 255);
                $a13 = $media->getProperty('thumbnail_src'); $sth->bindParam(':thumbnail_src', $a13, PDO::PARAM_STR, 255);
                $a14 = $media->getProperty('img_thumbnail'); $sth->bindParam(':img_thumbnail', $a14, PDO::PARAM_STR, 255);
                $a15 = $media->getProperty('img_standard_resolution'); $sth->bindParam(':img_standard_resolution', $a15, PDO::PARAM_STR, 255);
                $a16 = $media->getProperty('img_low_resolution'); $sth->bindParam(':img_low_resolution', $a16, PDO::PARAM_STR, 255);
                $a17 = $media->getProperty('vid_low_bandwidth'); $sth->bindParam(':vid_low_bandwidth', $a17, PDO::PARAM_STR, 255);
                $a18 = $media->getProperty('vid_low_resolution'); $sth->bindParam(':vid_low_resolution', $a18, PDO::PARAM_STR, 255);
                //$a19 = $media->getProperty('vid_standard_resolution'); $sth->bindParam(':vid_standard_resolution', $a19, PDO::PARAM_STR, 255);
                //
                if ($media->getProperty('type') === 'carousel'){
                    $a20 = json_encode($media->getProperty('carousel_media'), true); $sth->bindParam(':vid_standard_resolution', $a20, PDO::PARAM_STR, 255);
                } else {
                    $a19 = $media->getProperty('vid_standard_resolution'); $sth->bindParam(':vid_standard_resolution', $a19, PDO::PARAM_STR, 255);
                }
            } else {
                $sth = self::$pdo->prepare('
                    INSERT IGNORE INTO `' . TB_MEDIAS . '`
                    (`id`, `user_id`, `caption`, `type`, `code`, `likes`, `video_views`, `comments`, `scanned`, `thumbnail_src`, `created_time`, `updated_at`)
                    VALUES
                    (:id, :user_id, :caption, :type1, :code1, :likes, :video_views, :comments, :scanned, :thumbnail_src, FROM_UNIXTIME(:created_time), NOW())
                    ON DUPLICATE KEY UPDATE
                        `likes`                    = :likes,
                        `video_views`              = :video_views,
                        `comments`                 = :comments,
                        `thumbnail_src`            = :thumbnail_src,
                        `updated_at`               = NOW()
                ');

                $a1 = $media->getProperty('id'); $sth->bindParam(':id', $a1, PDO::PARAM_INT);
                $a2 = $media->getProperty('user_id'); $sth->bindParam(':user_id', $a2, PDO::PARAM_INT);
                $a3 = $media->getProperty('caption'); $sth->bindParam(':caption', $a3, PDO::PARAM_STR, 1800);
                $a4 = $media->getProperty('type'); $sth->bindParam(':type1', $a4, PDO::PARAM_STR, 100);
                $a5 = $media->getProperty('code'); $sth->bindParam(':code1', $a5, PDO::PARAM_STR, 100);
                $a7 = $media->getProperty('likes'); $sth->bindParam(':likes', $a7, PDO::PARAM_INT);
                $a8 = $media->getProperty('video_views'); $sth->bindParam(':video_views', $a8, PDO::PARAM_INT);
                $a9 = $media->getProperty('comments'); $sth->bindParam(':comments', $a9, PDO::PARAM_INT);
                $a10 = $media->getProperty('scanned'); $sth->bindParam(':scanned', $a10, PDO::PARAM_INT);
                //
                $a11 = $media->getProperty('created_time'); $sth->bindParam(':created_time', $a11, PDO::PARAM_STR);
                //
                $a13 = $media->getProperty('thumbnail_src'); $sth->bindParam(':thumbnail_src', $a13, PDO::PARAM_STR, 255);

            }


            return $sth->execute();
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }






    public static function checkExistingMediaCode($codes) {

        if (!self::isDbConnected()) {
            return false;
        }

        try {
            $inQuery = implode(',', array_fill(0, count($codes), '?'));
            $query = 'SELECT code FROM ' .TB_MEDIAS .' WHERE code IN (' .$inQuery .')';
            echo $query;
            //
            $sth = self::$pdo->prepare($query);

            // bindvalue is 1-indexed, so $k+1
            foreach ($codes as $k => $id)
                $sth->bindValue(($k+1), $id);

            $sth->execute();

            return $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }



    public static function updateMedia($media_id, $data) {

        if (!self::isDbConnected()) {
            return false;
        }

        try {
            $tokens = [];
            $query = '
                UPDATE ' . TB_MEDIAS . '
                SET ';
            $d = 0;
            foreach ($data as $key => $value) {
                if($d){
                    $query .= ',';
                }
                $query .= $key .'=:' .$key;
                $tokens[':'.$key] = $value;
                $d = 1;
            }
            $query .= '
                WHERE  id=:media_id';
            $tokens[':media_id'] = $media_id;
            //
            $sth = self::$pdo->prepare($query);
            //
            return $sth->execute($tokens);
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }



    public static function insertPublished($channel_id, $media_id, $post_id, $type, $insfa_id) {

        if (!self::isDbConnected()) {
            return false;
        }

        try {
            $tokens = [];
            $sth = self::$pdo->prepare('
                INSERT IGNORE INTO `' . TB_PUBLISHED . '`
                (`channel_id`, `media_id`, `post_id`, `type`, `insfa_id`, `created_at`, `updated_at`)
                VALUES
                (:channel_id, :media_id, :post_id, :type, :insfa_id, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    `updated_at`               = NOW()
            ');

            $sth->bindParam(':channel_id', $channel_id, PDO::PARAM_INT);
            $sth->bindParam(':media_id', $media_id, PDO::PARAM_INT);
            $sth->bindParam(':post_id', $post_id, PDO::PARAM_INT);
            $sth->bindParam(':type', $type, PDO::PARAM_STR);
            $sth->bindParam(':insfa_id', $insfa_id, PDO::PARAM_INT);
            //
            return $sth->execute();
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }



    public static function selectPublished($channel_id = null, $limit = null, $date_from = null) {

        if (!self::isDbConnected()) {
            return false;
        }
        if (null === $date_from){
            $date_from = strtotime("-2 day");
        }
        try {
            $tokens = [];
            $query ='
                SELECT * FROM ' .TB_CHANNELS .',' .TB_USERS .',' .TB_PUBLISHED .' LEFT JOIN ' . TB_MEDIAS .' ON ' .TB_PUBLISHED .'.`media_id`=' .TB_MEDIAS .'.`id`
            ';
            if(null !== $channel_id) {
                $query .=' WHERE ' .TB_PUBLISHED .'.`channel_id`=:channel_id AND ' .TB_PUBLISHED .'.`created_at` >= FROM_UNIXTIME(:date_from)';
            } else {
                $query .= ' WHERE ' .TB_PUBLISHED .'.`created_at` >= FROM_UNIXTIME(:date_from)';
            }
            $query .= ' AND ' .TB_USERS .'.`id`=' .TB_MEDIAS .'.`user_id`';
            $query .= ' AND ' .TB_CHANNELS .'.`channel_id`=' .TB_PUBLISHED .'.`channel_id`';
            $query .= ' ORDER BY ' .TB_PUBLISHED .'.`created_at` DESC';
            if(null !== $limit) {
                $query .=' LIMIT :limit ';
            }
            //
            $sth = self::$pdo->prepare($query);
            //
            if(null !== $channel_id) {
                $sth->bindParam(':channel_id', $channel_id, PDO::PARAM_INT);
            }
            if(null !== $limit) {
                $sth->bindParam(':limit', $limit, PDO::PARAM_INT);
            }
            $sth->bindParam(':date_from', $date_from, PDO::PARAM_STR);
            //
            $sth->execute();
            return $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }



    public static function insertUsersToChannels($channel_id, $data) {

        if (!self::isDbConnected()) {
            return false;
        }

        try {
            $query ='
                INSERT INTO `' . TB_CHANNELS_USERS . '`
                (`channel_id`, `user_id`, `need_check`, `created_at`, `updated_at`)
                VALUES
                (:channel_id, (SELECT id FROM ' .TB_USERS .' WHERE `username`=:username), :need_check, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    `need_check`               = :need_check,
                    `updated_at`               = NOW()
            ';
            //
            foreach ($data as $key => $value) {
                $sth = self::$pdo->prepare($query);
                $sth->bindParam(':channel_id', $channel_id, PDO::PARAM_INT);
                $sth->bindParam(':username', $key, PDO::PARAM_INT);
                $sth->bindParam(':need_check', $value, PDO::PARAM_INT);
                $success []= $sth->execute();
            }
            return $success;
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }






    public static function selectUsersToChannels($channel_id) {

        if (!self::isDbConnected()) {
            return false;
        }

        try {
            $query ='SELECT ' .TB_CHANNELS_USERS .'.*, ' .TB_USERS .'.`username` FROM `' . TB_CHANNELS_USERS . '` LEFT JOIN ' .TB_USERS .' ON ' .TB_CHANNELS_USERS .'.`user_id`=' .TB_USERS .'.`id` WHERE `channel_id` = :channel_id';
            //
            $sth = self::$pdo->prepare($query);
            $sth->execute([':channel_id' => $channel_id]);

            return $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }





    public static function deleteUsersToChannels($channel_id, $user_ids) {

        if (!self::isDbConnected()) {
            return false;
        }

        try {
            $query ='DELETE FROM `' . TB_CHANNELS_USERS . '` WHERE `channel_id`=:channel_id AND `user_id`=(SELECT id FROM ' .TB_USERS .' WHERE `username`=:username)';
            //
            foreach ($user_ids as $value) {
                $sth = self::$pdo->prepare($query);
                $sth->bindParam(':channel_id', $channel_id, PDO::PARAM_INT);
                $sth->bindParam(':username', $value, PDO::PARAM_INT);
                $sth->execute();
                $succes [] = $sth->rowCount();
            }
            return $succes;
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }





    public static function selectChannels() {

        if (!self::isDbConnected()) {
            return false;
        }

        try {
            $query ='SELECT * FROM `' . TB_CHANNELS . '` ORDER BY id ASC';
            //
            $sth = self::$pdo->prepare($query);
            $sth->execute();

            return $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }



    public static function selectCrons() {

        if (!self::isDbConnected()) {
            return false;
        }

        try {
            $query ='SELECT * FROM `' . TB_CRONJOBS . '` ORDER BY id ASC';
            //
            $sth = self::$pdo->prepare($query);
            $sth->execute();

            return $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }



    public static function selectBlackWords() {

        if (!self::isDbConnected()) {
            return false;
        }

        try {
            $query ='SELECT * FROM `' . TB_BLACK_WORDS . '` ORDER BY id ASC';
            //
            $sth = self::$pdo->prepare($query);
            $sth->execute();

            return $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }




    public static function selectAdminsChannels($inputs = []) {

        if (!self::isDbConnected()) {
            return false;
        }

        try {
            $query ='SELECT * FROM `' . TB_ADMINS_CHANNELS .'`';
            $where  = [];
            $tokens = [];
            //
            if (null !== $inputs['admin_id']) {
          	    $where[]            = '`admin_id`=:admin_id';
          		$tokens[':admin_id'] = (int)$inputs['admin_id'];
          	}

            if (null !== $inputs['channel_id']) {
          	    $where[]            = '`channel_id`=:channel_id';
          		$tokens[':channel_id'] = (int)$inputs['channel_id'];
          	}
            if (!empty($where)) {
                $query .= ' WHERE ' . implode(' AND ', $where);
            }


            $sth = self::$pdo->prepare($query);
            $sth->execute($tokens);

            return $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }





    public static function selectUsers($username) {

        if (!self::isDbConnected()) {
            return false;
        }

        try {
            $query ='SELECT '. TB_USERS .'.*,
                SUM(' . TB_MEDIAS .' .`likes`) AS total_likes,
                SUM(' . TB_MEDIAS .' .`comments`) AS total_comments,
                MAX(' . TB_MEDIAS .' .`likes`) AS max_likes,
                MAX(' . TB_MEDIAS .' .`comments`) AS max_comments,
                AVG(' . TB_MEDIAS .' .`likes`) AS average_likes,
                AVG(' . TB_MEDIAS .' .`comments`) AS average_comments
                FROM `' . TB_USERS . '`
                LEFT JOIN ' . TB_MEDIAS .' ON ' . TB_USERS .'.`id` = ' . TB_MEDIAS .' .`user_id`
                WHERE `username` = :username';
            //
            $sth = self::$pdo->prepare($query);
            $sth->execute([':username' => $username]);

            return $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }







    //selectChatsNew('normal', ['users' => true, 'groups' => true, 'super_groups' => true], ['chat_id' => 123, 'text' => 'sadq']);

  	//selectChatsNew('bydummy', ['users' => true, 'groups' => true, 'super_groups' => true], ['chat_id' => 123, 'text' => 'sadq']);

  	//selectChatsNew('byhost', ['users' => true, 'groups' => true, 'super_groups' => true], ['host_id' => 123]);

  	public static function selectMedia(
  		    //$method = 'normal',//'bylocation','bydummy','byhost','normal'
          $select_in = null,//['videos' => true, 'images' => true, 'carousels' => true]
  		  $inputs = null,// $not_published_in, $scanned, $checked $caption_containing //search dar caption, $likes_grater_than $limit_rows, $sort_by, $user_belongs_to_channel, $user_not_publisih_x_recent_post, $special_user_id, $special_user_name
          // $checked_by, $has_edited_caption, $edited_by, $media_id
          $date_from = null,
          $date_to = null
      ) {
        if (!self::isDbConnected()) {
            return false;
        }

      	$videos = true;
      	$images = true;
      	$carousels = true;
      	if(is_array($select_in)) {
        		if(!$select_in['videos']){
        			$videos = false;
        		}
        		if(!$select_in['images']){
        			$images = false;
        		}
        		if(!$select_in['carousels']){
        			$carousels = false;
        		}
      	}
        if (!$videos && !$images && !$carousels) {
            return false;
        }
        try {
  			    // query
            $query = '
                SELECT * ,
                ' . TB_MEDIAS . '.`id` AS `media_id`,
                ' . TB_MEDIAS . '.`created_time` AS `created_at`,
                ' . TB_MEDIAS . '.`updated_at` AS `updated_at`,
                ' . TB_USERS . '.`id` AS `user_id1`
  			    ';
  			//from
  			$query .= 'FROM `' . TB_MEDIAS . '`';
  			//Building parts of query
        $where  = [];
        $tokens = [];
  			//joins
        $query .= '
            LEFT JOIN `' . TB_USERS . '`
            ON ' . TB_MEDIAS . '.`user_id`=' . TB_USERS . '.`id`
        ';

  			//where
  			//tokens

        if (!$videos || !$images || !$carousels) {
            $chat_or_user = [];
            //
            $videos && $chat_or_user[] = TB_MEDIAS . '.`type` = "video"';
            $images && $chat_or_user[] = TB_MEDIAS . '.`type` = "image"';
            $carousels && $chat_or_user[] = TB_MEDIAS . '.`type` = "carousel"';

            $where[] = '(' . implode(' OR ', $chat_or_user) . ')';
        }

        if (null !== $date_from) {
            $where[]              = TB_MEDIAS . '.`created_time` >= FROM_UNIXTIME(:date_from)';
            $tokens[':date_from'] = $date_from;
        }

        if (null !== $date_to) {
            $where[]            = TB_MEDIAS . '.`created_time` <= FROM_UNIXTIME(:date_to)';
            $tokens[':date_to'] = $date_to;
        }

  		if (null !== $inputs['not_published_in']) {
  		    $where[]               = 'NOT EXISTS ( SELECT * FROM ' .TB_PUBLISHED .' WHERE ' .TB_MEDIAS .'.`id`=' .TB_PUBLISHED .'.`media_id` AND ' .TB_PUBLISHED .'.`channel_id`=:channel_id)' ;
  		    $tokens[':channel_id'] = $inputs['not_published_in'];
  		}

        if (null !== $inputs['media_id']) {
  		    $where[]               = TB_MEDIAS .'.`id`=:media_id_id' ;
  		    $tokens[':media_id_id'] = $inputs['media_id'];
  		}

        if (null !== $inputs['user_belongs_to_channel']) {
  		    $where[]                = TB_MEDIAS .'.`user_id`' .' IN ( SELECT user_id FROM ' .TB_CHANNELS_USERS .' WHERE ' .TB_CHANNELS_USERS .'.`channel_id`=:channel_id1)' ;
  		    $tokens[':channel_id1'] = $inputs['user_belongs_to_channel'];
            if (null !== $inputs['user_not_publisih_x_recent_post']) {
       		     $where[]               = 'NOT EXISTS ( SELECT ' .TB_MEDIAS .'.`user_id` FROM ' .TB_PUBLISHED .' LEFT JOIN ' .TB_MEDIAS .' ON ' .TB_PUBLISHED .'.`media_id`=' .TB_MEDIAS .'.`id`' .' WHERE ' .TB_PUBLISHED .'.`channel_id`=:channel_id1 ORDER BY ' .TB_PUBLISHED .'.`created_at` DESC LIMIT ' .$inputs['user_not_publisih_x_recent_post'] .')';
       		     //$tokens[':posts'] = $inputs['user_not_publisih_x_recent_post'];
       		}
  		}


        if (null !== $inputs['scanned']) {
  		    $where[]            = TB_MEDIAS . '.`scanned` = :scanned';
  		    $tokens[':scanned'] = $inputs['scanned'];
  		}

        if (null !== $inputs['checked']) {
      	    $where[]            = TB_MEDIAS . '.`checked` = :checked';
      		$tokens[':checked'] = $inputs['checked'];
      	}

        if (null !== $inputs['checked_by']) {
      	    $where[]            = TB_MEDIAS . '.`checked_by` = :checked_by';
      		$tokens[':checked_by'] = $inputs['checked_by'];
      	}

        if (null !== $inputs['has_edited_caption']) {
      	    $where[]            = TB_MEDIAS . '.`has_edited_caption` = :has_edited_caption';
      		$tokens[':has_edited_caption'] = $inputs['has_edited_caption'];
      	}

        if (null !== $inputs['edited_by']) {
      	    $where[]            = TB_MEDIAS . '.`edited_by` = :edited_by';
      		$tokens[':edited_by'] = $inputs['edited_by'];
      	}

        if (null !== $inputs['likes_grater_than']) {
  			     $where[]          = TB_MEDIAS . '.`likes` > :likes';
  				   $tokens[':likes'] = $inputs['likes_grater_than'];
  			}

  			if (null !== $inputs['caption_containing']) {
    	      $where[] = '(
  						 LOWER('  . TB_MEDIAS . '.`caption`) LIKE :caption
  					)';
  					$tokens[':caption'] = '%' . strtolower($inputs['caption_containing']) . '%';
  			}

        if (null !== $inputs['special_user_name']) {
  			     $where[]            = TB_MEDIAS . '.`user_id` = (SELECT `id` FROM ' .TB_USERS .' WHERE `username`=:spname)';
  				   $tokens[':spname'] = $inputs['special_user_name'];
  			}

        if (null !== $inputs['special_user_id']) {
  			     $where[]            = TB_MEDIAS . '.`user_id` = :spuid';
  				   $tokens[':spuid'] = $inputs['special_user_id'];
  			}



        if (!empty($where)) {
            $query .= ' WHERE ' . implode(' AND ', $where);
        }

    		// if ($method === 'bylocation') {
        //     $query .= ' HAVING distance <= radius ';
        // }

        if (null !== $inputs['sort_by']) {
    	      $query .= ' ORDER BY ' . TB_MEDIAS . '.`' .$inputs['sort_by'] .'` DESC';
  			}

        if (null !== $inputs['limit_rows']) {
    	      $query .= ' LIMIT ' . $inputs['limit_rows'];
  			}


        $sth = self::$pdo->prepare($query);
        $sth->execute($tokens);

        return $sth->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        throw new Exception($e->getMessage());
    }
  }




























    /**
     * Get Telegram API request count for current chat / message
     *
     * @param integer $chat_id
     * @param string  $inline_message_id
     *
     * @return array|bool (Array containing TOTAL and CURRENT fields or false on invalid arguments)
     * @throws \Longman\TelegramBot\Exception\Exception
     */
    public static function getTelegramRequestCount($chat_id = null, $inline_message_id = null)
    {
        if (!self::isDbConnected()) {
            return false;
        }

        try {
            $sth = self::$pdo->prepare('SELECT
                (SELECT COUNT(*) FROM `' . TB_REQUEST_LIMITER . '` WHERE `created_at` >= :date) as LIMIT_PER_SEC_ALL,
                (SELECT COUNT(*) FROM `' . TB_REQUEST_LIMITER . '` WHERE ((`chat_id` = :chat_id AND `inline_message_id` IS NULL) OR (`inline_message_id` = :inline_message_id AND `chat_id` IS NULL)) AND `created_at` >= :date) as LIMIT_PER_SEC,
                (SELECT COUNT(*) FROM `' . TB_REQUEST_LIMITER . '` WHERE `chat_id` = :chat_id AND `created_at` >= :date_minute) as LIMIT_PER_MINUTE
            ');

            $date = self::getTimestamp(time());
            $date_minute = self::getTimestamp(strtotime('-1 minute'));

            $sth->bindParam(':chat_id', $chat_id, \PDO::PARAM_STR);
            $sth->bindParam(':inline_message_id', $inline_message_id, \PDO::PARAM_STR);
            $sth->bindParam(':date', $date, \PDO::PARAM_STR);
            $sth->bindParam(':date_minute', $date_minute, \PDO::PARAM_STR);

            $sth->execute();

            return $sth->fetch();
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    }


	//
	//
	//
	//
	//
	public static function getOutgoingRequestCount($chat_id = null, $inline_message_id = null)
    {
        if (!self::isDbConnected()) {
            return false;
        }

        try {
            $sth = self::$pdo->prepare('SELECT
                (SELECT COUNT(*) FROM `' . TB_REQUEST . '` WHERE ((`chat_id` = :chat_id AND `inline_message_id` IS NULL) OR (`inline_message_id` = :inline_message_id AND `chat_id` IS NULL)) AND `created_at` >= :date) as CURRENT,
                (SELECT COUNT(*) FROM `' . TB_REQUEST . '` WHERE `created_at` >= :date) as TOTAL
            ');

            $date = self::getTimestamp();

            $sth->bindParam(':chat_id', $chat_id, \PDO::PARAM_INT);
            $sth->bindParam(':inline_message_id', $inline_message_id, \PDO::PARAM_STR);
            $sth->bindParam(':date', $date, \PDO::PARAM_STR);

            $sth->execute();

            return $sth->fetch();
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    }


}
