<?php

namespace Includes;

use PDO;
use PDOException;

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
            'user',
            'media',
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
    public static function insertTelegramUpdate(
        $id,
        $chat_id,
        $message_id,
        $inline_query_id,
        $chosen_inline_result_id,
        $callback_query_id,
        $edited_message_id
    ) {
        if ($message_id === null && $inline_query_id === null && $chosen_inline_result_id === null && $callback_query_id === null && $edited_message_id === null) {
            throw new Exception('message_id, inline_query_id, chosen_inline_result_id, callback_query_id, edited_message_id are all null');
        }

        if (!self::isDbConnected()) {
            return false;
        }

        try {
            $sth = self::$pdo->prepare('
                INSERT IGNORE INTO `' . TB_TELEGRAM_UPDATE . '`
                (`id`, `chat_id`, `message_id`, `inline_query_id`, `chosen_inline_result_id`, `callback_query_id`, `edited_message_id`)
                VALUES
                (:id, :chat_id, :message_id, :inline_query_id, :chosen_inline_result_id, :callback_query_id, :edited_message_id)
            ');

            $sth->bindParam(':id', $id, PDO::PARAM_INT);
            $sth->bindParam(':chat_id', $chat_id, PDO::PARAM_INT);
            $sth->bindParam(':message_id', $message_id, PDO::PARAM_INT);
            $sth->bindParam(':inline_query_id', $inline_query_id, PDO::PARAM_INT);
            $sth->bindParam(':chosen_inline_result_id', $chosen_inline_result_id, PDO::PARAM_INT);
            $sth->bindParam(':callback_query_id', $callback_query_id, PDO::PARAM_INT);
            $sth->bindParam(':edited_message_id', $edited_message_id, PDO::PARAM_INT);

            return $sth->execute();
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Insert users and save their connection to chats
     *
     * @param  \Longman\TelegramBot\Entities\User $user
     * @param  string                             $date
     * @param  \Longman\TelegramBot\Entities\Chat $chat
     *
     * @return bool If the insert was successful
     * @throws \Longman\TelegramBot\Exception\Exception
     */
    public static function insertUser(User $user, $date, Chat $chat = null)
    {
        if (!self::isDbConnected()) {
            return false;
        }

        $user_id    = $user->getId();
        $username   = $user->getUsername();
        $first_name = $user->getFirstName();
        $last_name  = $user->getLastName();

        try {
            $sth = self::$pdo->prepare('
                INSERT INTO `' . TB_USER . '`
                (`id`, `username`, `first_name`, `last_name`, `created_at`, `updated_at`)
                VALUES
                (:id, :username, :first_name, :last_name, :date, :date)
                ON DUPLICATE KEY UPDATE
                    `username`   = :username,
                    `first_name` = :first_name,
                    `last_name`  = :last_name,
                    `updated_at` = :date
            ');

            $sth->bindParam(':id', $user_id, PDO::PARAM_INT);
            $sth->bindParam(':username', $username, PDO::PARAM_STR, 255);
            $sth->bindParam(':first_name', $first_name, PDO::PARAM_STR, 255);
            $sth->bindParam(':last_name', $last_name, PDO::PARAM_STR, 255);
            $sth->bindParam(':date', $date, PDO::PARAM_STR);

            $status = $sth->execute();
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }

        //insert also the relationship to the chat into user_chat table
        if ($chat instanceof Chat) {
            $chat_id = $chat->getId();
            try {
                $sth = self::$pdo->prepare('
                    INSERT IGNORE INTO `' . TB_USER_CHAT . '`
                    (`user_id`, `chat_id`)
                    VALUES
                    (:user_id, :chat_id)
                ');

                $sth->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $sth->bindParam(':chat_id', $chat_id, PDO::PARAM_INT);

                $status = $sth->execute();
            } catch (PDOException $e) {
                throw new Exception($e->getMessage());
            }
        }

        return $status;
    }

    /**
     * Insert chat
     *
     * @param  \Longman\TelegramBot\Entities\Chat $chat
     * @param  string                             $date
     * @param  int                                $migrate_to_chat_id
     *
     * @return bool If the insert was successful
     * @throws \Longman\TelegramBot\Exception\Exception
     */
    public static function insertChat(Chat $chat, $date, $migrate_to_chat_id = null)
    {
        if (!self::isDbConnected()) {
            return false;
        }

        $chat_id                             = $chat->getId();
        $chat_title                          = $chat->getTitle();
        $chat_username                       = $chat->getUsername();
        $chat_type                           = $chat->getType();
        $chat_all_members_are_administrators = $chat->getAllMembersAreAdministrators();

        try {
            $sth = self::$pdo->prepare('
                INSERT IGNORE INTO `' . TB_CHAT . '`
                (`id`, `type`, `title`, `username`, `all_members_are_administrators`, `created_at` ,`updated_at`, `old_id`)
                VALUES
                (:id, :type, :title, :username, :all_members_are_administrators, :date, :date, :oldid)
                ON DUPLICATE KEY UPDATE
                    `type`                           = :type,
                    `title`                          = :title,
                    `username`                       = :username,
                    `all_members_are_administrators` = :all_members_are_administrators,
                    `updated_at`                     = :date
            ');

            if ($migrate_to_chat_id) {
                $chat_type = 'supergroup';

                $sth->bindParam(':id', $migrate_to_chat_id, PDO::PARAM_INT);
                $sth->bindParam(':oldid', $chat_id, PDO::PARAM_INT);
            } else {
                $sth->bindParam(':id', $chat_id, PDO::PARAM_INT);
                $sth->bindParam(':oldid', $migrate_to_chat_id, PDO::PARAM_INT);
            }

            $sth->bindParam(':type', $chat_type, PDO::PARAM_INT);
            $sth->bindParam(':title', $chat_title, PDO::PARAM_STR, 255);
            $sth->bindParam(':username', $chat_username, PDO::PARAM_STR, 255);
            $sth->bindParam(':all_members_are_administrators', $chat_all_members_are_administrators, PDO::PARAM_INT);
            $sth->bindParam(':date', $date, PDO::PARAM_STR);

            return $sth->execute();
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }

	///
	///ME
	///
	public static function insertChatHostId(Chat $chat, $date, $host_id, $migrate_to_chat_id = null)
    {
        if (!self::isDbConnected()) {
            return false;
        }

        $chat_id                             = $chat->getId();
        $chat_title                          = $chat->getTitle();
        $chat_username                       = $chat->getUsername();
        $chat_type                           = $chat->getType();
        $chat_all_members_are_administrators = $chat->getAllMembersAreAdministrators();

        try {
            $sth = DB::$pdo->prepare('
				INSERT IGNORE INTO `' . TB_CHAT . '`
				(`id`, `type`, `title`, `username`, `host`, `all_members_are_administrators`, `created_at` ,`updated_at`, `old_id`)
				VALUES
				(:id, :type, :title, :username, :host, :all_members_are_administrators, :date, :date, :oldid)
				ON DUPLICATE KEY UPDATE
					`type`                           = :type,
					`title`                          = :title,
					`username`                       = :username,
					`host`                           = :host,
					`all_members_are_administrators` = :all_members_are_administrators,
					`updated_at`                     = :date
			');

            if ($migrate_to_chat_id) {
                $chat_type = 'supergroup';

                $sth->bindParam(':id', $migrate_to_chat_id, PDO::PARAM_INT);
                $sth->bindParam(':oldid', $chat_id, PDO::PARAM_INT);
            } else {
                $sth->bindParam(':id', $chat_id, PDO::PARAM_INT);
                $sth->bindParam(':oldid', $migrate_to_chat_id, PDO::PARAM_INT);
            }

            $sth->bindParam(':type', $chat_type, PDO::PARAM_INT);
            $sth->bindParam(':title', $chat_title, PDO::PARAM_STR, 255);
            $sth->bindParam(':username', $chat_username, PDO::PARAM_STR, 255);
			$sth->bindParam(':host', $host_id, PDO::PARAM_INT);
            $sth->bindParam(':all_members_are_administrators', $chat_all_members_are_administrators, PDO::PARAM_INT);
            $sth->bindParam(':date', $date, PDO::PARAM_STR);

            return $sth->execute();
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }

	public static function insertChatInitialization(Chat $chat, $date, $host_id, $migrate_to_chat_id = null)
    {
        if (!self::isDbConnected()) {
            return false;
        }

        $chat_id                             = $chat->getId();
        $chat_title                          = $chat->getTitle();
        $chat_username                       = $chat->getUsername();
        $chat_type                           = $chat->getType();
        $chat_all_members_are_administrators = $chat->getAllMembersAreAdministrators();

        try {
            $sth = DB::$pdo->prepare('
				INSERT IGNORE INTO `' . TB_CHAT . '`
				(`id`, `type`, `title`, `username`, `host`, `all_members_are_administrators`, `created_at` ,`updated_at`, `old_id`)
				VALUES
				(:id, :type, :title, :username, :host, :all_members_are_administrators, :date, :date, :oldid)
				ON DUPLICATE KEY UPDATE
					`type`                           = :type,
					`title`                          = :title,
					`username`                       = :username,
					`host`                           = :host,
					`all_members_are_administrators` = :all_members_are_administrators,
					`updated_at`                     = :date,
					`dummy_id`                       = :dummy_id,
					`lat`                            = :lat,
					`lng`                            = :lng,
					`coins`                          = :coins,
					`premium`                        = :premium,
					`radius`                         = :radius,
					`active`                         = :active
			');


            if ($migrate_to_chat_id) {
                $chat_type = 'supergroup';

                $sth->bindParam(':id', $migrate_to_chat_id, PDO::PARAM_INT);
                $sth->bindParam(':oldid', $chat_id, PDO::PARAM_INT);
            } else {
                $sth->bindParam(':id', $chat_id, PDO::PARAM_INT);
                $sth->bindParam(':oldid', $migrate_to_chat_id, PDO::PARAM_INT);
            }

            $sth->bindParam(':type', $chat_type, PDO::PARAM_INT);
            $sth->bindParam(':title', $chat_title, PDO::PARAM_STR, 255);
            $sth->bindParam(':username', $chat_username, PDO::PARAM_STR, 255);
			$sth->bindParam(':host', $host_id, PDO::PARAM_INT);
            $sth->bindParam(':all_members_are_administrators', $chat_all_members_are_administrators, PDO::PARAM_INT);
            $sth->bindParam(':date', $date, PDO::PARAM_STR);

			$new = false;
			while ($new == false){
				$today = substr(date("Ymdhi"),3);
				$rand = sprintf("%03d", rand(0,999));
				$unique = $today . $rand;
				$res = $results = DB::selectChatsByDummy(true, true, true, null, null, $unique);
				if (empty($res)){
					$new = true;
				}
			}
			$lat = '35.7';
			$lng = '51.4';
			$coins = 5;
			$premium = 0;
			$radius = 1200;
			$active = 1;

			$sth->bindParam(':dummy_id', $unique, PDO::PARAM_INT);
			$sth->bindParam(':lat', $lat, PDO::PARAM_STR);
			$sth->bindParam(':lng', $lng, PDO::PARAM_STR);
			$sth->bindParam(':coins', $coins, PDO::PARAM_INT);
			$sth->bindParam(':premium', $premium, PDO::PARAM_INT);
			$sth->bindParam(':radius', $radius, PDO::PARAM_INT);
			$sth->bindParam(':active', $active, PDO::PARAM_INT);

            return $sth->execute();
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }

	//public static function insertChatDetails(Chat $chat, $lat = null, $lng = null, $radius = null, $active = null, $host_id = null )
	public static function insertChatDetails(Chat $chat, $inputs)
    {
        if (!self::isDbConnected()) {
            return false;
        }
		$date = self::getTimestamp();

        $chat_id                             = $chat->getId();
        $chat_title                          = $chat->getTitle();
        $chat_username                       = $chat->getUsername();
        $chat_type                           = $chat->getType();
        $chat_all_members_are_administrators = $chat->getAllMembersAreAdministrators();

        try {

			$query ='
				INSERT IGNORE INTO `' . TB_CHAT . '`
				(`id`, `type`, `title`, `username`, `updated_at`)
				VALUES
				(:id, :type, :title, :username, :date)
				ON DUPLICATE KEY UPDATE
					`type`                           = :type,
					`title`                          = :title,
					`username`                       = :username,
					`updated_at`                     = :date,
					';

			if (null !== $inputs['host_id']) {
				$results = DB::selectChats(null, null, true, null, null, $chat_id);
				if (!empty($results)) {
					$result = reset($results);
					if (is_array($result)) {
						$host_old       = $result['host'];
					}
				}
				if(empty($host_old)){
					$new = false;
					while ($new == false){
						$today = substr(date("Ymdhi"),3);
						$rand = sprintf("%03d", rand(0,999));
						$unique = $today . $rand;
						$res = $results = DB::selectChatsByDummy(true, true, true, null, null, $unique);
						if (empty($res)){
							$new = true;
						}
					}
					$query .= '`dummy_id`  = :dummy_id,' .PHP_EOL;
					$query .= '`host`      = :host_id,' .PHP_EOL;
				}
			}

			if (null !== $inputs['lat'] && null !== $inputs['lng']) {
				$query .= '`lat`      = :lat,'.PHP_EOL;
				$query .= '`lng`      = :lng,'.PHP_EOL;
			}

			if (null !== $inputs['radius']) {
				$query .= '`radius`   = :radius,'.PHP_EOL;
			}

			if (null !== $inputs['active']) {
				$query .= '`active`   = :active,'.PHP_EOL;
			}

			if (null !== $inputs['coins']) {
				$query .= '`coins`    = :coins,'.PHP_EOL;
			}

			if (null !== $inputs['premium']) {
				$query .= '`premium`  = :premium,'.PHP_EOL;
			}

			if (null !== $inputs['dummy_username']) {
				$query .= '`dummy_username`   = :dummy_username,'.PHP_EOL;
			}

			if (null !== $inputs['dummy_photoid']) {
				$query .= '`dummy_photoid`   = :dummy_photoid,'.PHP_EOL;
			}

			if (null !== $inputs['personal_type']) {
				$query .= '`personal_type`   = :personal_type,'.PHP_EOL;
			}

			if (null !== $inputs['gender']) {
				$query .= '`gender`   = :gender,'.PHP_EOL;
			}
			$query = rtrim($query ,',' .PHP_EOL);

			$sth = DB::$pdo->prepare($query);

			//
			//
			//

            $sth->bindParam(':id', $chat_id, PDO::PARAM_INT);
            $sth->bindParam(':type', $chat_type, PDO::PARAM_INT);
            $sth->bindParam(':title', $chat_title, PDO::PARAM_STR, 255);
            $sth->bindParam(':username', $chat_username, PDO::PARAM_STR, 255);
            $sth->bindParam(':date', $date, PDO::PARAM_STR);

			if (null !== $inputs['host_id']) {
				$sth->bindParam(':dummy_id', $unique, PDO::PARAM_INT);
				$sth->bindParam(':host_id', $inputs['host_id'], PDO::PARAM_INT);
			}
			if (null !== $inputs['lat'] && null !== $inputs['lng']) {
				$sth->bindParam(':lat', $inputs['lat'], PDO::PARAM_STR);
				$sth->bindParam(':lng', $inputs['lng'], PDO::PARAM_STR);
			}
			if (null !== $inputs['radius']) {
				$sth->bindParam(':radius', $inputs['radius'], PDO::PARAM_INT);
			}
			if (null !== $inputs['active']) {
				$sth->bindParam(':active', $inputs['active'], PDO::PARAM_INT);
			}
			if (null !== $inputs['coins']) {
				$sth->bindParam(':coins', $inputs['coins'], PDO::PARAM_INT);
			}
			if (null !== $inputs['premium']) {
				$sth->bindParam(':premium', $inputs['premium'], PDO::PARAM_INT);
			}
			if (null !== $inputs['dummy_username']) {
				$sth->bindParam(':dummy_username', $inputs['dummy_username'], PDO::PARAM_STR);
			}
			if (null !== $inputs['dummy_photoid']) {
				$sth->bindParam(':dummy_photoid', $inputs['dummy_photoid'], PDO::PARAM_STR);
			}
			if (null !== $inputs['personal_type']) {
				$sth->bindParam(':personal_type', $inputs['personal_type'], PDO::PARAM_STR);
			}
			if (null !== $inputs['gender']) {
				$sth->bindParam(':gender', $inputs['gender'], PDO::PARAM_STR, 255);
			}



			return $sth->execute();
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }

	public static function selectChatsByHosts(
        $select_groups = true,
        $select_super_groups = true,
        $select_users = true,
        $date_from = null,
        $date_to = null,
        $chat_id = null,
        $text = null
    ) {
        if (!self::isDbConnected()) {
            return false;
        }

        if (!$select_groups && !$select_users && !$select_super_groups) {
            return false;
        }

        try {
            $query = '
                SELECT * ,
                ' . TB_CHAT . '.`id` AS `chat_id`,
                ' . TB_CHAT . '.`created_at` AS `chat_created_at`,
                ' . TB_CHAT . '.`updated_at` AS `chat_updated_at`
            ';
            if ($select_users) {
                $query .= '
                    , ' . TB_USER . '.`id` AS `user_id`
                    FROM `' . TB_CHAT . '`
                    LEFT JOIN `' . TB_USER . '`
                    ON ' . TB_CHAT . '.`id`=' . TB_USER . '.`id`
                ';
            } else {
                $query .= 'FROM `' . TB_CHAT . '`';
            }

            //Building parts of query
            $where  = [];
            $tokens = [];

            if (!$select_groups || !$select_users || !$select_super_groups) {
                $chat_or_user = [];

                $select_groups && $chat_or_user[] = TB_CHAT . '.`type` = "group"';
                $select_super_groups && $chat_or_user[] = TB_CHAT . '.`type` = "supergroup"';
                $select_users && $chat_or_user[] = TB_CHAT . '.`type` = "private"';

                $where[] = '(' . implode(' OR ', $chat_or_user) . ')';
            }

            if (null !== $date_from) {
                $where[]              = TB_CHAT . '.`updated_at` >= :date_from';
                $tokens[':date_from'] = $date_from;
            }

            if (null !== $date_to) {
                $where[]            = TB_CHAT . '.`updated_at` <= :date_to';
                $tokens[':date_to'] = $date_to;
            }

            if (null !== $chat_id) {
                $where[]            = TB_CHAT . '.`host` = :chat_id';
                $tokens[':chat_id'] = $chat_id;
            }

            if (null !== $text) {
                if ($select_users) {
                    $where[] = '(
                        LOWER(' . TB_CHAT . '.`title`) LIKE :text
                        OR LOWER(' . TB_USER . '.`first_name`) LIKE :text
                        OR LOWER(' . TB_USER . '.`last_name`) LIKE :text
                        OR LOWER(' . TB_USER . '.`username`) LIKE :text
                    )';
                } else {
                    $where[] = 'LOWER(' . TB_CHAT . '.`title`) LIKE :text';
                }
                $tokens[':text'] = '%' . strtolower($text) . '%';
            }

            if (!empty($where)) {
                $query .= ' WHERE ' . implode(' AND ', $where);
            }

            $query .= ' ORDER BY ' . TB_CHAT . '.`updated_at` ASC';

			//
			//$query = 'SELECT * FROM' . TB_CHAT . 'WHERE `host` = :chat_id';

            $sth = self::$pdo->prepare($query);
            $sth->execute($tokens);

            return $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }

    }

	public static function selectChatsByDummy(
        $select_groups = true,
        $select_super_groups = true,
        $select_users = true,
        $date_from = null,
        $date_to = null,
        $chat_id = null,
        $text = null
    ) {
        if (!self::isDbConnected()) {
            return false;
        }

        if (!$select_groups && !$select_users && !$select_super_groups) {
            return false;
        }

        try {
            $query = '
                SELECT * ,
                ' . TB_CHAT . '.`dummy_id` AS `chat_id`,
                ' . TB_CHAT . '.`created_at` AS `chat_created_at`,
                ' . TB_CHAT . '.`updated_at` AS `chat_updated_at`
            ';
            if ($select_users) {
                $query .= '
                    , ' . TB_USER . '.`id` AS `user_id`
                    FROM `' . TB_CHAT . '`
                    LEFT JOIN `' . TB_USER . '`
                    ON ' . TB_CHAT . '.`id`=' . TB_USER . '.`id`
                ';
            } else {
                $query .= 'FROM `' . TB_CHAT . '`';
            }

            //Building parts of query
            $where  = [];
            $tokens = [];

            if (!$select_groups || !$select_users || !$select_super_groups) {
                $chat_or_user = [];

                $select_groups && $chat_or_user[] = TB_CHAT . '.`type` = "group"';
                $select_super_groups && $chat_or_user[] = TB_CHAT . '.`type` = "supergroup"';
                $select_users && $chat_or_user[] = TB_CHAT . '.`type` = "private"';

                $where[] = '(' . implode(' OR ', $chat_or_user) . ')';
            }

            if (null !== $date_from) {
                $where[]              = TB_CHAT . '.`updated_at` >= :date_from';
                $tokens[':date_from'] = $date_from;
            }

            if (null !== $date_to) {
                $where[]            = TB_CHAT . '.`updated_at` <= :date_to';
                $tokens[':date_to'] = $date_to;
            }

            if (null !== $chat_id) {
                $where[]            = TB_CHAT . '.`dummy_id` = :chat_id';
                $tokens[':chat_id'] = $chat_id;
            }

            if (null !== $text) {
                if ($select_users) {
                    $where[] = '(
                        LOWER(' . TB_CHAT . '.`dummy_username`) LIKE :text
						OR LOWER(' . TB_CHAT . '.`dummy_id`) LIKE :text
					)';
					//	OR LOWER(' . TB_CHAT . '.`id`) LIKE :text
                    //    OR LOWER(' . TB_USER . '.`first_name`) LIKE :text
                    //    OR LOWER(' . TB_USER . '.`last_name`) LIKE :text
                    //    OR LOWER(' . TB_USER . '.`username`) LIKE :text
                    //)';
                } else {
                    $where[] = 'LOWER(' . TB_CHAT . '.`title`) LIKE :text';
                }
                $tokens[':text'] = '%' . strtolower($text) . '%';
            }

            if (!empty($where)) {
                $query .= ' WHERE ' . implode(' AND ', $where);
            }

            $query .= ' ORDER BY ' . TB_CHAT . '.`updated_at` ASC';

            $sth = self::$pdo->prepare($query);
            $sth->execute($tokens);

            return $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }



	public static function selectChatsByLocation(
        $select_groups = false,
        $select_super_groups = false,
        $select_users = true,
        $date_from = null,
        $date_to = null,
        $chat_id = null,
        $text = null,
		$sender_id,
		$sender_location
    ) {
        if (!self::isDbConnected()) {
            return false;
        }

        if (!$select_groups && !$select_users && !$select_super_groups) {
            return false;
        }

        try {
            $query = '
                SELECT * ,
                ' . TB_CHAT . '.`id` AS `chat_id`,
                ' . TB_CHAT . '.`created_at` AS `chat_created_at`,
                ' . TB_CHAT . '.`updated_at` AS `chat_updated_at`
            ';
            //if ($select_users) {
            //    $query .= '
            //        , ' . TB_USER . '.`id` AS `user_id`
            //        FROM `' . TB_CHAT . '`
            //        LEFT JOIN `' . TB_USER . '`
            //        ON ' . TB_CHAT . '.`id`=' . TB_USER . '.`id`
            //    ';
            //} else {
            //    $query .= 'FROM `' . TB_CHAT . '`';
            //}


            //Building parts of query
            $where  = [];
            $tokens = [];

			////
			$query .= '
                , relations.`id1` AS `id1`
                FROM `' . TB_CHAT . '`
                LEFT JOIN `relations`
				ON ' . TB_CHAT . '.`id`=relations.`id1`
				AND relations.`id2`=:sender_id
				AND relations.`ban`=:ban
            ';
			$where[] = 'relations.`id1` is NULL';
			$ban = 'ban';
			$tokens[':ban'] = $ban;
			////

            if (!$select_groups || !$select_users || !$select_super_groups) {
                $chat_or_user = [];

                $select_groups && $chat_or_user[] = TB_CHAT . '.`type` = "group"';
                $select_super_groups && $chat_or_user[] = TB_CHAT . '.`type` = "supergroup"';
                $select_users && $chat_or_user[] = TB_CHAT . '.`type` = "private"';

                $where[] = '(' . implode(' OR ', $chat_or_user) . ')';
            }

            if (null !== $date_from) {
                $where[]              = TB_CHAT . '.`updated_at` >= :date_from';
                $tokens[':date_from'] = $date_from;
            }

            if (null !== $date_to) {
                $where[]            = TB_CHAT . '.`updated_at` <= :date_to';
                $tokens[':date_to'] = $date_to;
            }

            if (null !== $chat_id) {
                $where[]            = TB_CHAT . '.`id` = :chat_id';
                $tokens[':chat_id'] = $chat_id;
            }

            if (null !== $text) {
                if ($select_users) {
                    $where[] = '(
                        LOWER(' . TB_CHAT . '.`title`) LIKE :text
						OR LOWER(' . TB_CHAT . '.`id`) LIKE :text
                        OR LOWER(' . TB_USER . '.`first_name`) LIKE :text
                        OR LOWER(' . TB_USER . '.`last_name`) LIKE :text
                        OR LOWER(' . TB_USER . '.`username`) LIKE :text
                    )';
                } else {
                    $where[] = 'LOWER(' . TB_CHAT . '.`title`) LIKE :text';
                }
                $tokens[':text'] = '%' . strtolower($text) . '%';
            }

			$active = 1;
			$where[] = 'LOWER(' . TB_CHAT . '.`active`) = :active';
			$tokens[':active'] = $active;

			$where[] = 'LOWER(' . TB_CHAT . '.`id`) != :sender_id';
			$tokens[':sender_id'] = $sender_id;

            if (!empty($where)) {
                $query .= ' WHERE ' . implode(' AND ', $where);
            }

            $query .= ' ORDER BY ' . TB_CHAT . '.`updated_at` ASC';

            $sth = self::$pdo->prepare($query);
            $sth->execute($tokens);

            return $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }

	//selectChatsNew('normal', ['users' => true, 'groups' => true, 'super_groups' => true], ['chat_id' => 123, 'text' => 'sadq']);

	//selectChatsNew('bydummy', ['users' => true, 'groups' => true, 'super_groups' => true], ['chat_id' => 123, 'text' => 'sadq']);

	//selectChatsNew('byhost', ['users' => true, 'groups' => true, 'super_groups' => true], ['host_id' => 123]);

	public static function selectChatsNew(
		$method = 'normal',//'bylocation','bydummy','byhost','normal'
        $select_in = null,//['users' => true, 'groups' => true, 'super_groups' => true]
		$inputs = null,// $chat_id, $text, $sender_id, $sender_lat, $sender_lng, $host_id
        $date_from = null,
        $date_to = null
    ) {
        if (!self::isDbConnected()) {
            return false;
        }

		$select_groups = true;
		$select_super_groups = true;
		$select_users = true;
		if(is_array($select_in)) {
			if(!$select_in['groups']){
				$select_groups = false;
			}
			if(!$select_in['super_groups']){
				$select_super_groups = false;
			}
			if(!$select_in['users']){
				$select_users = false;
			}
		}

        if (!$select_groups && !$select_users && !$select_super_groups) {
            return false;
        }

        try {
			// query
            $query = '
                SELECT * ,
                ' . TB_CHAT . '.`id` AS `chat_id`,
                ' . TB_CHAT . '.`created_at` AS `chat_created_at`,
                ' . TB_CHAT . '.`updated_at` AS `chat_updated_at`
            ';
			if ($select_users) {
                $query .= '
                    , ' . TB_USER . '.`id` AS `user_id`
				';
			}
			switch ($method){
				case 'normal':

					break;
				case 'bylocation':
					$query .= '
						, relations.`id1` AS `id1`
						, ( 6371 * acos( cos( radians(:sender_lat) ) * cos( radians( lat ) )
						* cos( radians(lng) - radians(:sender_lng)) + sin(radians(:sender_lat))
						* sin( radians(lat)))) AS distance
					';
					$tokens[':sender_lat'] = $inputs['sender_lat'];
					$tokens[':sender_lng'] = $inputs['sender_lng'];
					break;
				case 'byhost':

					break;
				case 'bydummy':

					break;
			}
			//from

			$query .= 'FROM `' . TB_CHAT . '`';

			//Building parts of query
            $where  = [];
            $tokens = [];

			//joins

            if ($select_users) {
                $query .= '
                    LEFT JOIN `' . TB_USER . '`
                    ON ' . TB_CHAT . '.`id`=' . TB_USER . '.`id`
                ';
            }
			switch ($method){
				case 'normal':

					break;

				case 'bylocation':
					$query .= '
						LEFT JOIN `relations`
						ON ' . TB_CHAT . '.`id`=relations.`id1`
						AND relations.`id2`=:sender_id
						AND relations.`ban`=:ban
					';
					$where[] = 'relations.`id1` is NULL';
					$ban = 'ban';
					$tokens[':ban'] = $ban;
					break;

				case 'byhost':

					break;

				case 'bydummy':

					break;
			}

			//where
			//tokens

            if (!$select_groups || !$select_users || !$select_super_groups) {
                $chat_or_user = [];

                $select_groups && $chat_or_user[] = TB_CHAT . '.`type` = "group"';
                $select_super_groups && $chat_or_user[] = TB_CHAT . '.`type` = "supergroup"';
                $select_users && $chat_or_user[] = TB_CHAT . '.`type` = "private"';

                $where[] = '(' . implode(' OR ', $chat_or_user) . ')';
            }

            if (null !== $date_from) {
                $where[]              = TB_CHAT . '.`updated_at` >= :date_from';
                $tokens[':date_from'] = $date_from;
            }

            if (null !== $date_to) {
                $where[]            = TB_CHAT . '.`updated_at` <= :date_to';
                $tokens[':date_to'] = $date_to;
            }


			switch ($method){
				case 'normal':
					if (null !== $inputs['chat_id']) {
						$where[]            = TB_CHAT . '.`id` = :chat_id';
						$tokens[':chat_id'] = $inputs['chat_id'];
					}

					if (null !== $inputs['text']) {
						if ($select_users) {
							$where[] = '(
								LOWER(' . TB_CHAT . '.`title`) LIKE :text
								OR LOWER(' . TB_CHAT . '.`id`) LIKE :text
								OR LOWER(' . TB_USER . '.`first_name`) LIKE :text
								OR LOWER(' . TB_USER . '.`last_name`) LIKE :text
								OR LOWER(' . TB_USER . '.`username`) LIKE :text
							)';
						} else {
							$where[] = 'LOWER(' . TB_CHAT . '.`title`) LIKE :text';
						}
						$tokens[':text'] = '%' . strtolower($inputs['text']) . '%';
					}
					break;

				case 'bylocation':
					$active = 1;
					$where[] = 'LOWER(' . TB_CHAT . '.`active`) = :active';
					$tokens[':active'] = $active;
					//
					$where[] = 'LOWER(' . TB_CHAT . '.`id`) != :sender_id';
					$tokens[':sender_id'] = $inputs['sender_id'];
					break;

				case 'byhost':
					if (null !== $inputs['host_id']) {
						$where[]            = TB_CHAT . '.`host` = :chat_id';
						$tokens[':chat_id'] = $inputs['host_id'];
					}
					break;

				case 'bydummy':
					if (null !== $inputs['dummy_id']) {
						$where[]            = TB_CHAT . '.`dummy_id` = :chat_id';
						$tokens[':chat_id'] = $inputs['dummy_id'];
					}

					if (null !== $inputs['text']) {
						if ($select_users) {
							$where[] = '(
								LOWER(' . TB_CHAT . '.`dummy_username`) LIKE :text
								OR LOWER(' . TB_CHAT . '.`dummy_id`) LIKE :text
							)';
							//	OR LOWER(' . TB_CHAT . '.`id`) LIKE :text
							//    OR LOWER(' . TB_USER . '.`first_name`) LIKE :text
							//    OR LOWER(' . TB_USER . '.`last_name`) LIKE :text
							//    OR LOWER(' . TB_USER . '.`username`) LIKE :text
							//)';
						} else {
							$where[] = 'LOWER(' . TB_CHAT . '.`title`) LIKE :text';
						}
						$tokens[':text'] = '%' . strtolower($inputs['text']) . '%';
					}
					break;
			}





            if (!empty($where)) {
                $query .= ' WHERE ' . implode(' AND ', $where);
            }

			if ($method === 'bylocation') {
                $query .= ' HAVING distance <= radius ';
            }

            $query .= ' ORDER BY ' . TB_CHAT . '.`updated_at` ASC';

            $sth = self::$pdo->prepare($query);
            $sth->execute($tokens);

            return $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }


	///
	///END
	///




    /**
     * Insert request into database
     *
     * @todo self::$pdo->lastInsertId() - unsafe usage if expected previous insert fails?
     *
     * @param \Longman\TelegramBot\Entities\Update $update
     *
     * @return bool
     * @throws \Longman\TelegramBot\Exception\Exception
     */
    public static function insertRequest(Update $update)
    {
        $update_id   = $update->getUpdateId();
        $update_type = $update->getUpdateType();

        if ($update_type === 'message') {
            $message = $update->getMessage();

            if (self::insertMessageRequest($message)) {
                $message_id = $message->getMessageId();
                $chat_id    = $message->getChat()->getId();

                return self::insertTelegramUpdate($update_id, $chat_id, $message_id, null, null, null, null);
            }
        } elseif ($update_type === 'edited_message') {
            $edited_message = $update->getEditedMessage();

            if (self::insertEditedMessageRequest($edited_message)) {
                $chat_id                 = $edited_message->getChat()->getId();
                $edited_message_local_id = self::$pdo->lastInsertId();

                return self::insertTelegramUpdate(
                    $update_id,
                    $chat_id,
                    null,
                    null,
                    null,
                    null,
                    $edited_message_local_id
                );
            }
        } elseif ($update_type === 'channel_post') {
            $channel_post = $update->getChannelPost();

            if (self::insertMessageRequest($channel_post)) {
                $message_id = $channel_post->getMessageId();
                $chat_id    = $channel_post->getChat()->getId();

                return self::insertTelegramUpdate($update_id, $chat_id, $message_id, null, null, null, null);
            }
        } elseif ($update_type === 'edited_channel_post') {
            $edited_channel_post = $update->getEditedChannelPost();

            if (self::insertEditedMessageRequest($edited_channel_post)) {
                $chat_id                      = $edited_channel_post->getChat()->getId();
                $edited_channel_post_local_id = self::$pdo->lastInsertId();

                return self::insertTelegramUpdate(
                    $update_id,
                    $chat_id,
                    null,
                    null,
                    null,
                    null,
                    $edited_channel_post_local_id
                );
            }
        } elseif ($update_type === 'inline_query') {
            $inline_query = $update->getInlineQuery();

            if (self::insertInlineQueryRequest($inline_query)) {
                $inline_query_id = $inline_query->getId();

                return self::insertTelegramUpdate($update_id, null, null, $inline_query_id, null, null, null);
            }
        } elseif ($update_type === 'chosen_inline_result') {
            $chosen_inline_result = $update->getChosenInlineResult();

            if (self::insertChosenInlineResultRequest($chosen_inline_result)) {
                $chosen_inline_result_local_id = self::$pdo->lastInsertId();

                return self::insertTelegramUpdate(
                    $update_id,
                    null,
                    null,
                    null,
                    $chosen_inline_result_local_id,
                    null,
                    null
                );
            }
        } elseif ($update_type === 'callback_query') {
            $callback_query = $update->getCallbackQuery();

            if (self::insertCallbackQueryRequest($callback_query)) {
                $callback_query_id = $callback_query->getId();

                return self::insertTelegramUpdate($update_id, null, null, null, null, $callback_query_id, null);
            }
        }

        return false;
    }

    /**
     * Insert inline query request into database
     *
     * @param \Longman\TelegramBot\Entities\InlineQuery $inline_query
     *
     * @return bool If the insert was successful
     * @throws \Longman\TelegramBot\Exception\Exception
     */
    public static function insertInlineQueryRequest(InlineQuery $inline_query)
    {
        if (!self::isDbConnected()) {
            return false;
        }

        try {
            $sth = self::$pdo->prepare('
                INSERT IGNORE INTO `' . TB_INLINE_QUERY . '`
                (`id`, `user_id`, `location`, `query`, `offset`, `created_at`)
                VALUES
                (:inline_query_id, :user_id, :location, :query, :param_offset, :created_at)
            ');

            $date            = self::getTimestamp();
            $inline_query_id = $inline_query->getId();
            $from            = $inline_query->getFrom();
            $user_id         = null;
            if ($from instanceof User) {
                $user_id = $from->getId();
                self::insertUser($from, $date);
            }

            $location = $inline_query->getLocation();
            $query    = $inline_query->getQuery();
            $offset   = $inline_query->getOffset();

            $sth->bindParam(':inline_query_id', $inline_query_id, PDO::PARAM_INT);
            $sth->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $sth->bindParam(':location', $location, PDO::PARAM_STR);
            $sth->bindParam(':query', $query, PDO::PARAM_STR);
            $sth->bindParam(':param_offset', $offset, PDO::PARAM_STR);
            $sth->bindParam(':created_at', $date, PDO::PARAM_STR);

            return $sth->execute();
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Insert chosen inline result request into database
     *
     * @param \Longman\TelegramBot\Entities\ChosenInlineResult $chosen_inline_result
     *
     * @return bool If the insert was successful
     * @throws \Longman\TelegramBot\Exception\Exception
     */
    public static function insertChosenInlineResultRequest(ChosenInlineResult $chosen_inline_result)
    {
        if (!self::isDbConnected()) {
            return false;
        }

        try {
            $sth = self::$pdo->prepare('
                INSERT INTO `' . TB_CHOSEN_INLINE_RESULT . '`
                (`result_id`, `user_id`, `location`, `inline_message_id`, `query`, `created_at`)
                VALUES
                (:result_id, :user_id, :location, :inline_message_id, :query, :created_at)
            ');

            $date      = self::getTimestamp();
            $result_id = $chosen_inline_result->getResultId();
            $from      = $chosen_inline_result->getFrom();
            $user_id   = null;
            if ($from instanceof User) {
                $user_id = $from->getId();
                self::insertUser($from, $date);
            }

            $location          = $chosen_inline_result->getLocation();
            $inline_message_id = $chosen_inline_result->getInlineMessageId();
            $query             = $chosen_inline_result->getQuery();

            $sth->bindParam(':result_id', $result_id, PDO::PARAM_STR);
            $sth->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $sth->bindParam(':location', $location, PDO::PARAM_STR);
            $sth->bindParam(':inline_message_id', $inline_message_id, PDO::PARAM_STR);
            $sth->bindParam(':query', $query, PDO::PARAM_STR);
            $sth->bindParam(':created_at', $date, PDO::PARAM_STR);

            return $sth->execute();
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Insert callback query request into database
     *
     * @param \Longman\TelegramBot\Entities\CallbackQuery $callback_query
     *
     * @return bool If the insert was successful
     * @throws \Longman\TelegramBot\Exception\Exception
     */
    public static function insertCallbackQueryRequest(CallbackQuery $callback_query)
    {
        if (!self::isDbConnected()) {
            return false;
        }

        try {
            $sth = self::$pdo->prepare('
                INSERT IGNORE INTO `' . TB_CALLBACK_QUERY . '`
                (`id`, `user_id`, `chat_id`, `message_id`, `inline_message_id`, `data`, `created_at`)
                VALUES
                (:callback_query_id, :user_id, :chat_id, :message_id, :inline_message_id, :data, :created_at)
            ');

            $date              = self::getTimestamp();
            $callback_query_id = $callback_query->getId();
            $from              = $callback_query->getFrom();
            $user_id           = null;
            if ($from instanceof User) {
                $user_id = $from->getId();
                self::insertUser($from, $date);
            }

            $message    = $callback_query->getMessage();
            $chat_id    = null;
            $message_id = null;
            if ($message instanceof Message) {
                $chat_id    = $message->getChat()->getId();
                $message_id = $message->getMessageId();

                $is_message = self::$pdo->query('
                    SELECT *
                    FROM `' . TB_MESSAGE . '`
                    WHERE `id` = ' . $message_id . '
                      AND `chat_id` = ' . $chat_id . '
                    LIMIT 1
                ')->rowCount();

                if ($is_message) {
                    self::insertEditedMessageRequest($message);
                } else {
                    self::insertMessageRequest($message);
                }
            }

            $inline_message_id = $callback_query->getInlineMessageId();
            $data              = $callback_query->getData();

            $sth->bindParam(':callback_query_id', $callback_query_id, PDO::PARAM_INT);
            $sth->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $sth->bindParam(':chat_id', $chat_id, PDO::PARAM_INT);
            $sth->bindParam(':message_id', $message_id, PDO::PARAM_INT);
            $sth->bindParam(':inline_message_id', $inline_message_id, PDO::PARAM_STR);
            $sth->bindParam(':data', $data, PDO::PARAM_STR);
            $sth->bindParam(':created_at', $date, PDO::PARAM_STR);

            return $sth->execute();
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Insert Message request in db
     *
     * @param \Longman\TelegramBot\Entities\Message $message
     *
     * @return bool If the insert was successful
     * @throws \Longman\TelegramBot\Exception\Exception
     */
    public static function insertMessageRequest(Message $message)
    {
        if (!self::isDbConnected()) {
            return false;
        }

        $from = $message->getFrom();
        $chat = $message->getChat();

        $chat_id = $chat->getId();

        $date = self::getTimestamp($message->getDate());

        $forward_from            = $message->getForwardFrom();
        $forward_from_chat       = $message->getForwardFromChat();
        $forward_from_message_id = $message->getForwardFromMessageId();
        $photo                   = self::entitiesArrayToJson($message->getPhoto(), '');
        $entities                = self::entitiesArrayToJson($message->getEntities(), null);
        $new_chat_member         = $message->getNewChatMember();
        $new_chat_photo          = self::entitiesArrayToJson($message->getNewChatPhoto(), '');
        $left_chat_member        = $message->getLeftChatMember();
        $migrate_to_chat_id      = $message->getMigrateToChatId();

        //Insert chat, update chat id in case it migrated
        self::insertChat($chat, $date, $migrate_to_chat_id);

        //Insert user and the relation with the chat
        if (is_object($from)) {
            self::insertUser($from, $date, $chat);
        }

        //Insert the forwarded message user in users table
        if ($forward_from instanceof User) {
            $forward_date = self::getTimestamp($message->getForwardDate());
            self::insertUser($forward_from, $forward_date);
            $forward_from = $forward_from->getId();
        }

        if ($forward_from_chat instanceof Chat) {
            $forward_date = self::getTimestamp($message->getForwardDate());
            self::insertChat($forward_from_chat, $forward_date);
            $forward_from_chat = $forward_from_chat->getId();
        }

        //New and left chat member
        if ($new_chat_member instanceof User) {
            //Insert the new chat user
            self::insertUser($new_chat_member, $date, $chat);
            $new_chat_member = $new_chat_member->getId();
        } elseif ($left_chat_member instanceof User) {
            //Insert the left chat user
            self::insertUser($left_chat_member, $date, $chat);
            $left_chat_member = $left_chat_member->getId();
        }

        try {
            $sth = self::$pdo->prepare('
                INSERT IGNORE INTO `' . TB_MESSAGE . '`
                (
                    `id`, `user_id`, `chat_id`, `date`, `forward_from`, `forward_from_chat`, `forward_from_message_id`,
                    `forward_date`, `reply_to_chat`, `reply_to_message`, `text`, `entities`, `audio`, `document`,
                    `photo`, `sticker`, `video`, `voice`, `caption`, `contact`,
                    `location`, `venue`, `new_chat_member`, `left_chat_member`,
                    `new_chat_title`,`new_chat_photo`, `delete_chat_photo`, `group_chat_created`,
                    `supergroup_chat_created`, `channel_chat_created`,
                    `migrate_from_chat_id`, `migrate_to_chat_id`, `pinned_message`
                ) VALUES (
                    :message_id, :user_id, :chat_id, :date, :forward_from, :forward_from_chat, :forward_from_message_id,
                    :forward_date, :reply_to_chat, :reply_to_message, :text, :entities, :audio, :document,
                    :photo, :sticker, :video, :voice, :caption, :contact,
                    :location, :venue, :new_chat_member, :left_chat_member,
                    :new_chat_title, :new_chat_photo, :delete_chat_photo, :group_chat_created,
                    :supergroup_chat_created, :channel_chat_created,
                    :migrate_from_chat_id, :migrate_to_chat_id, :pinned_message
                )
            ');

            $message_id = $message->getMessageId();

            if (is_object($from)) {
                $from_id = $from->getId();
            } else {
                $from_id = null;
            }

            $reply_to_message    = $message->getReplyToMessage();
            $reply_to_message_id = null;
            if ($reply_to_message instanceof ReplyToMessage) {
                $reply_to_message_id = $reply_to_message->getMessageId();
                // please notice that, as explained in the documentation, reply_to_message don't contain other
                // reply_to_message field so recursion deep is 1
                self::insertMessageRequest($reply_to_message);
            }

            $text                    = $message->getText();
            $audio                   = $message->getAudio();
            $document                = $message->getDocument();
            $sticker                 = $message->getSticker();
            $video                   = $message->getVideo();
            $voice                   = $message->getVoice();
            $caption                 = $message->getCaption();
            $contact                 = $message->getContact();
            $location                = $message->getLocation();
            $venue                   = $message->getVenue();
            $new_chat_title          = $message->getNewChatTitle();
            $delete_chat_photo       = $message->getDeleteChatPhoto();
            $group_chat_created      = $message->getGroupChatCreated();
            $supergroup_chat_created = $message->getSupergroupChatCreated();
            $channel_chat_created    = $message->getChannelChatCreated();
            $migrate_from_chat_id    = $message->getMigrateFromChatId();
            $migrate_to_chat_id      = $message->getMigrateToChatId();
            $pinned_message          = $message->getPinnedMessage();

            $sth->bindParam(':chat_id', $chat_id, PDO::PARAM_INT);
            $sth->bindParam(':message_id', $message_id, PDO::PARAM_INT);
            $sth->bindParam(':user_id', $from_id, PDO::PARAM_INT);
            $sth->bindParam(':date', $date, PDO::PARAM_STR);
            $sth->bindParam(':forward_from', $forward_from, PDO::PARAM_INT);
            $sth->bindParam(':forward_from_chat', $forward_from_chat, PDO::PARAM_INT);
            $sth->bindParam(':forward_from_message_id', $forward_from_message_id, PDO::PARAM_INT);
            $sth->bindParam(':forward_date', $forward_date, PDO::PARAM_STR);

            $reply_to_chat_id = null;
            if ($reply_to_message_id) {
                $reply_to_chat_id = $chat_id;
            }

            $sth->bindParam(':reply_to_chat', $reply_to_chat_id, PDO::PARAM_INT);
            $sth->bindParam(':reply_to_message', $reply_to_message_id, PDO::PARAM_INT);
            $sth->bindParam(':text', $text, PDO::PARAM_STR);
            $sth->bindParam(':entities', $entities, PDO::PARAM_STR);
            $sth->bindParam(':audio', $audio, PDO::PARAM_STR);
            $sth->bindParam(':document', $document, PDO::PARAM_STR);
            $sth->bindParam(':photo', $photo, PDO::PARAM_STR);
            $sth->bindParam(':sticker', $sticker, PDO::PARAM_STR);
            $sth->bindParam(':video', $video, PDO::PARAM_STR);
            $sth->bindParam(':voice', $voice, PDO::PARAM_STR);
            $sth->bindParam(':caption', $caption, PDO::PARAM_STR);
            $sth->bindParam(':contact', $contact, PDO::PARAM_STR);
            $sth->bindParam(':location', $location, PDO::PARAM_STR);
            $sth->bindParam(':venue', $venue, PDO::PARAM_STR);
            $sth->bindParam(':new_chat_member', $new_chat_member, PDO::PARAM_INT);
            $sth->bindParam(':left_chat_member', $left_chat_member, PDO::PARAM_INT);
            $sth->bindParam(':new_chat_title', $new_chat_title, PDO::PARAM_STR);
            $sth->bindParam(':new_chat_photo', $new_chat_photo, PDO::PARAM_STR);
            $sth->bindParam(':delete_chat_photo', $delete_chat_photo, PDO::PARAM_INT);
            $sth->bindParam(':group_chat_created', $group_chat_created, PDO::PARAM_INT);
            $sth->bindParam(':supergroup_chat_created', $supergroup_chat_created, PDO::PARAM_INT);
            $sth->bindParam(':channel_chat_created', $channel_chat_created, PDO::PARAM_INT);
            $sth->bindParam(':migrate_from_chat_id', $migrate_from_chat_id, PDO::PARAM_INT);
            $sth->bindParam(':migrate_to_chat_id', $migrate_to_chat_id, PDO::PARAM_INT);
            $sth->bindParam(':pinned_message', $pinned_message, PDO::PARAM_STR);

            return $sth->execute();
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Insert Edited Message request in db
     *
     * @param \Longman\TelegramBot\Entities\Message $edited_message
     *
     * @return bool If the insert was successful
     * @throws \Longman\TelegramBot\Exception\Exception
     */
    public static function insertEditedMessageRequest(Message $edited_message)
    {
        if (!self::isDbConnected()) {
            return false;
        }

        $from = $edited_message->getFrom();
        $chat = $edited_message->getChat();

        $chat_id = $chat->getId();

        $edit_date = self::getTimestamp($edited_message->getEditDate());

        $entities = self::entitiesArrayToJson($edited_message->getEntities(), null);

        //Insert chat
        self::insertChat($chat, $edit_date);

        //Insert user and the relation with the chat
        if (is_object($from)) {
            self::insertUser($from, $edit_date, $chat);
        }

        try {
            $sth = self::$pdo->prepare('
                INSERT IGNORE INTO `' . TB_EDITED_MESSAGE . '`
                (`chat_id`, `message_id`, `user_id`, `edit_date`, `text`, `entities`, `caption`)
                VALUES
                (:chat_id, :message_id, :user_id, :date, :text, :entities, :caption)
            ');

            $message_id = $edited_message->getMessageId();

            if (is_object($from)) {
                $from_id = $from->getId();
            } else {
                $from_id = null;
            }

            $text    = $edited_message->getText();
            $caption = $edited_message->getCaption();

            $sth->bindParam(':chat_id', $chat_id, PDO::PARAM_INT);
            $sth->bindParam(':message_id', $message_id, PDO::PARAM_INT);
            $sth->bindParam(':user_id', $from_id, PDO::PARAM_INT);
            $sth->bindParam(':date', $edit_date, PDO::PARAM_STR);
            $sth->bindParam(':text', $text, PDO::PARAM_STR);
            $sth->bindParam(':entities', $entities, PDO::PARAM_STR);
            $sth->bindParam(':caption', $caption, PDO::PARAM_STR);

            return $sth->execute();
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Select Group and/or single Chats
     *
     * @param bool   $select_groups
     * @param bool   $select_super_groups
     * @param bool   $select_users
     * @param string $date_from
     * @param string $date_to
     * @param int    $chat_id
     * @param string $text
     *
     * @return array|bool (Selected chats or false if invalid arguments)
     * @throws \Longman\TelegramBot\Exception\Exception
     */
    public static function selectChats(
        $select_groups = true,
        $select_super_groups = true,
        $select_users = true,
        $date_from = null,
        $date_to = null,
        $chat_id = null,
        $text = null
    ) {
        if (!self::isDbConnected()) {
            return false;
        }

        if (!$select_groups && !$select_users && !$select_super_groups) {
            return false;
        }

        try {
            $query = '
                SELECT * ,
                ' . TB_CHAT . '.`id` AS `chat_id`,
                ' . TB_CHAT . '.`created_at` AS `chat_created_at`,
                ' . TB_CHAT . '.`updated_at` AS `chat_updated_at`
            ';
            if ($select_users) {
                $query .= '
                    , ' . TB_USER . '.`id` AS `user_id`
                    FROM `' . TB_CHAT . '`
                    LEFT JOIN `' . TB_USER . '`
                    ON ' . TB_CHAT . '.`id`=' . TB_USER . '.`id`
                ';
            } else {
                $query .= 'FROM `' . TB_CHAT . '`';
            }

            //Building parts of query
            $where  = [];
            $tokens = [];

            if (!$select_groups || !$select_users || !$select_super_groups) {
                $chat_or_user = [];

                $select_groups && $chat_or_user[] = TB_CHAT . '.`type` = "group"';
                $select_super_groups && $chat_or_user[] = TB_CHAT . '.`type` = "supergroup"';
                $select_users && $chat_or_user[] = TB_CHAT . '.`type` = "private"';

                $where[] = '(' . implode(' OR ', $chat_or_user) . ')';
            }

            if (null !== $date_from) {
                $where[]              = TB_CHAT . '.`updated_at` >= :date_from';
                $tokens[':date_from'] = $date_from;
            }

            if (null !== $date_to) {
                $where[]            = TB_CHAT . '.`updated_at` <= :date_to';
                $tokens[':date_to'] = $date_to;
            }

            if (null !== $chat_id) {
                $where[]            = TB_CHAT . '.`id` = :chat_id';
                $tokens[':chat_id'] = $chat_id;
            }

            if (null !== $text) {
                if ($select_users) {
                    $where[] = '(
                        LOWER(' . TB_CHAT . '.`title`) LIKE :text
						OR LOWER(' . TB_CHAT . '.`id`) LIKE :text
                        OR LOWER(' . TB_USER . '.`first_name`) LIKE :text
                        OR LOWER(' . TB_USER . '.`last_name`) LIKE :text
                        OR LOWER(' . TB_USER . '.`username`) LIKE :text
                    )';
                } else {
                    $where[] = 'LOWER(' . TB_CHAT . '.`title`) LIKE :text';
                }
                $tokens[':text'] = '%' . strtolower($text) . '%';
            }

            if (!empty($where)) {
                $query .= ' WHERE ' . implode(' AND ', $where);
            }

            $query .= ' ORDER BY ' . TB_CHAT . '.`updated_at` ASC';

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

    /**
     * Insert Telegram API request in db
     *
     * @param string $method
     * @param array  $data
     *
     * @return bool If the insert was successful
     * @throws \Longman\TelegramBot\Exception\Exception
     */
    public static function insertTelegramRequest($method, $data)
    {
        if (!self::isDbConnected()) {
            return false;
        }

        $chat_id = ((isset($data['chat_id'])) ? $data['chat_id'] : null);
        $inline_message_id = (isset($data['inline_message_id']) ? $data['inline_message_id'] : null);

        try {
            $sth = self::$pdo->prepare('INSERT INTO `' . TB_REQUEST_LIMITER . '`
                (
                `method`, `chat_id`, `inline_message_id`, `created_at`
                )
                VALUES (
                :method, :chat_id, :inline_message_id, :date
                );
            ');

            $created_at = self::getTimestamp();

            $sth->bindParam(':chat_id', $chat_id, \PDO::PARAM_STR);
            $sth->bindParam(':inline_message_id', $inline_message_id, \PDO::PARAM_STR);
            $sth->bindParam(':method', $method, \PDO::PARAM_STR);
            $sth->bindParam(':date', $created_at, \PDO::PARAM_STR);

            return $sth->execute();
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
	//
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

    public static function insertOutgoingRequest($method, $chat_id = null, $inline_message_id = null)
    {
        if (!self::isDbConnected()) {
            return false;
        }

        try {
            $sth = self::$pdo->prepare('INSERT INTO `' . TB_REQUEST . '`
                (
                `method`, `chat_id`, `inline_message_id`, `created_at`
                )
                VALUES (
                :method, :chat_id, :inline_message_id, :date
                );
            ');

            $created_at = self::getTimestamp();

            $sth->bindParam(':chat_id', $chat_id, \PDO::PARAM_INT);
            $sth->bindParam(':inline_message_id', $inline_message_id, \PDO::PARAM_STR);
            $sth->bindParam(':method', $method, \PDO::PARAM_STR);
            $sth->bindParam(':date', $created_at, \PDO::PARAM_STR);

            return $sth->execute();
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
