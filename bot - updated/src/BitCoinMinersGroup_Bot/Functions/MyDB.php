<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Longman\TelegramBot;

use Exception;
use Longman\TelegramBot\Exception\TelegramException;
use PDO;
use Longman\TelegramBot\Entities\CallbackQuery;
use Longman\TelegramBot\Entities\Chat;
use Longman\TelegramBot\Entities\ChosenInlineResult;
use Longman\TelegramBot\Entities\InlineQuery;
use Longman\TelegramBot\Entities\Message;
use Longman\TelegramBot\Entities\ReplyToMessage;
use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Entities\User;

class MyDB extends DB
{
    /**
     * Initilize conversation table
     */
    public static function initializeConversation()
    {
        if (!defined('TB_CONVERSATION')) {
            define('TB_CONVERSATION', self::$table_prefix . 'conversation');
        }
    }

    /**
     * Select a conversation from the DB
     *
     * @param int  $user_id
     * @param int  $chat_id
     * @param bool $limit
     *
     * @return array|bool
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public static function selectConversation($user_id, $chat_id, $limit = null)
    {
        if (!self::isDbConnected()) {
            return false;
        }

        try {
            $query = 'SELECT * FROM `' . TB_CONVERSATION . '` ';
            $query .= 'WHERE `status` = :status ';
            $query .= 'AND `chat_id` = :chat_id ';
            $query .= 'AND `user_id` = :user_id ';

            if (!is_null($limit)) {
                $query .= ' LIMIT :limit';
            }
            $sth = self::$pdo->prepare($query);

            $active = 'active';
            $sth->bindParam(':status', $active, PDO::PARAM_STR);
            $sth->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $sth->bindParam(':chat_id', $chat_id, PDO::PARAM_INT);
            $sth->bindParam(':limit', $limit, PDO::PARAM_INT);
            $sth->execute();

            $results = $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new TelegramException($e->getMessage());
        }
        return $results;
    }

    /**
     * Insert the conversation in the database
     *
     * @param int    $user_id
     * @param int    $chat_id
     * @param string $command
     *
     * @return bool
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public static function insertConversation($user_id, $chat_id, $command)
    {
        if (!self::isDbConnected()) {
            return false;
        }

        try {
            $sth    = self::$pdo->prepare('INSERT INTO `' . TB_CONVERSATION . '`
                (
                `status`, `user_id`, `chat_id`, `command`, `notes`, `created_at`, `updated_at`
                )
                VALUES (
                :status, :user_id, :chat_id, :command, :notes, :date, :date
                )
               ');
            $active = 'active';
            $notes  = '[]';
            $created_at = self::getTimestamp();

            $sth->bindParam(':status', $active);
            $sth->bindParam(':command', $command);
            $sth->bindParam(':user_id', $user_id);
            $sth->bindParam(':chat_id', $chat_id);
            $sth->bindParam(':notes', $notes);
            $sth->bindParam(':date', $created_at);

            $status = $sth->execute();
        } catch (Exception $e) {
            throw new TelegramException($e->getMessage());
        }
        return $status;
    }

    /**
     * Update a specific conversation
     *
     * @param array $fields_values
     * @param array $where_fields_values
     *
     * @return bool
     */
    public static function updateConversation(array $fields_values, array $where_fields_values)
    {
        return self::update(TB_CONVERSATION, $fields_values, $where_fields_values);
    }

    /**
     * Update the conversation in the database
     *
     * @param string $table
     * @param array  $fields_values
     * @param array  $where_fields_values
     *
     * @todo This function is generic should be moved in DB.php
     *
     * @return bool
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public static function update($table, array $fields_values, array $where_fields_values)
    {
        if (!self::isDbConnected()) {
            return false;
        }
        //Auto update the field update_at
        $fields_values['updated_at'] = self::getTimestamp();

        //Values
        $update         = '';
        $tokens         = [];
        $tokens_counter = 0;
        $a              = 0;
        foreach ($fields_values as $field => $value) {
            if ($a) {
                $update .= ', ';
            }
            ++$a;
            ++$tokens_counter;
            $update .= '`' . $field . '` = :' . $tokens_counter;
            $tokens[':' . $tokens_counter] = $value;
        }

        //Where
        $a     = 0;
        $where = '';
        foreach ($where_fields_values as $field => $value) {
            if ($a) {
                $where .= ' AND ';
            } else {
                ++$a;
                $where .= 'WHERE ';
            }
            ++$tokens_counter;
            $where .= '`' . $field . '`= :' . $tokens_counter;
            $tokens[':' . $tokens_counter] = $value;
        }

        $query = 'UPDATE `' . $table . '` SET ' . $update . ' ' . $where;
        try {
            $sth    = self::$pdo->prepare($query);
            $status = $sth->execute($tokens);
        } catch (Exception $e) {
            throw new TelegramException($e->getMessage());
        }
        return $status;
    }


	///
	///ME
	///



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
				$results = DB::selectChats(['chat_id' => $chat_id]);
				if (!empty($results)) {
					$result = reset($results);
					if (is_array($result)) {
						$host_old       = $result['host'];
					}
				}
				if(empty($host_old)){
					$query .= '`host`      = :host_id,' .PHP_EOL;
				}
			}

            if (null !== $inputs['last_satoshi_date']) {
				$query .= '`last_satoshi_date`   = NOW(),'.PHP_EOL;
			}

			if (null !== $inputs['ads_seen']) {
				$query .= '`ads_seen`   = :ads_seen,'.PHP_EOL;
			}

			if (null !== $inputs['lang']) {
				$query .= '`lang`   = :lang,'.PHP_EOL;
			}

			if (null !== $inputs['satoshi']) {
				$query .= '`satoshi`    = :satoshi,'.PHP_EOL;
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
				$sth->bindParam(':host_id', $inputs['host_id'], PDO::PARAM_INT);
			}
			if (null !== $inputs['ads_seen']) {
				$sth->bindParam(':ads_seen', $inputs['ads_seen'], PDO::PARAM_INT);
			}
			if (null !== $inputs['lang']) {
				$sth->bindParam(':lang', $inputs['lang'], PDO::PARAM_STR);
			}
			if (null !== $inputs['satoshi']) {
				$sth->bindParam(':satoshi', $inputs['satoshi'], PDO::PARAM_INT);
			}



			return $sth->execute();
        } catch (PDOException $e) {
            throw new TelegramException($e->getMessage());
        }
    }



	//selectChatsN('normal', ['users' => true, 'groups' => true, 'super_groups' => true], ['chat_id' => 123, 'text' => 'sadq']);

	//selectChatsN('bydummy', ['users' => true, 'groups' => true, 'super_groups' => true], ['chat_id' => 123, 'text' => 'sadq']);

	//selectChatsN('byhost', ['users' => true, 'groups' => true, 'super_groups' => true], ['host_id' => 123]);

	//selectChatsN('bylocation', ['users' => true, 'groups' => true, 'super_groups' => true], ['sender_id' => $id, 'sender_lat' => $sender_lat, 'sender_lng' => $sender_lng]);//'sender_radius' => 13//for reverse

	public static function selectChatsN(
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
			//Building parts of query
            $where  = [];
            $tokens = [];


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
						, ( 6371 * acos( cos( radians(:sender_lat) ) * cos( radians( chat.`lat` ) )
						* cos( radians(chat.`lng`) - radians(:sender_lng)) + sin(radians(:sender_lat))
						* sin( radians(chat.`lat`)))) AS `distance`
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
				if(null !== $inputs['sender_radius']){
					$query .= ' HAVING distance <= :sender_radius' ;
					$tokens[':sender_radius'] = $inputs['sender_radius'];
				} else {
					$query .= ' HAVING distance <= ' . TB_CHAT . '.`radius`';
				}
            }

            $query .= ' ORDER BY ' . TB_CHAT . '.`updated_at` ASC';

            $sth = self::$pdo->prepare($query);
            $result = $sth->execute($tokens);
			//if ($result) {
				return $sth->fetchAll(PDO::FETCH_ASSOC);
			//} else {
			//	return $query .json_encode($tokens);
			//}
        } catch (PDOException $e) {
            throw new TelegramException($e->getMessage());
        }
    }

	public static function HandleRelation ($id1, $dummy_id = null, $id2 = null, $command = null){
		// $dummy_id is of $id1
		$date = self::getTimestamp();
		switch ($command){
			case 'ban':

			case 'unban':
				try {
					$sth = DB::$pdo->prepare('
						INSERT IGNORE INTO `relations`
						(`id1`, `id2`, `ban`,`updated_at`)
						VALUES
						(:id1, :id2, :ban, :date)
						ON DUPLICATE KEY UPDATE
							`ban`                          = :ban,
							`updated_at`                   = :date
					');
					$sth->bindParam(':id1', $id1, PDO::PARAM_INT);
					$sth->bindParam(':id2', $id2, PDO::PARAM_INT);
					$sth->bindParam(':ban', $command, PDO::PARAM_INT);
					$sth->bindParam(':date', $date, PDO::PARAM_STR);

					return $sth->execute();
				} catch (PDOException $e) {
					throw new TelegramException($e->getMessage());
				}
				break;
			case 'report':

			case 'unreport':
				try {
					$sth = DB::$pdo->prepare('
						INSERT IGNORE INTO `relations`
						(`id1`, `id2`, `report`,`updated_at`)
						VALUES
						(:id1, :id2, :report, :date)
						ON DUPLICATE KEY UPDATE
							`report`                       = :report,
							`updated_at`                   = :date
					');
					$sth->bindParam(':id1', $id1, PDO::PARAM_INT);
					$sth->bindParam(':id2', $id2, PDO::PARAM_INT);
					$sth->bindParam(':report', $command, PDO::PARAM_INT);
					$sth->bindParam(':date', $date, PDO::PARAM_STR);

					return $sth->execute();
				} catch (PDOException $e) {
					throw new TelegramException($e->getMessage());
				}
				break;
			case 'follow':

			case 'unfollow':
				try {
					$sth = DB::$pdo->prepare('
						INSERT IGNORE INTO `relations`
						(`id1`, `id2`, `follow`,`updated_at`)
						VALUES
						(:id1, :id2, :follow, :date)
						ON DUPLICATE KEY UPDATE
							`follow`                       = :follow,
							`updated_at`                   = :date
					');
					$sth->bindParam(':id1', $id1, PDO::PARAM_INT);
					$sth->bindParam(':id2', $id2, PDO::PARAM_INT);
					$sth->bindParam(':follow', $command, PDO::PARAM_INT);
					$sth->bindParam(':date', $date, PDO::PARAM_STR);

					return $sth->execute();
				} catch (PDOException $e) {
					throw new TelegramException($e->getMessage());
				}
				break;
			default:
				try {
					//Building parts of query
					$where  = [];
					$tokens = [];


					$query = '
						SELECT * ,
						relations.`id1` AS `id1`,
						relations.`id2` AS `id2`,
						relations.`updated_at` AS `updated_at`
					';
					$query .= ' FROM `relations`';

					//$query = 'SELECT * FROM `relations`';


					$tokens[':dummy_id'] = $dummy_id;


					$where[]            = ' `id1` = :chat_id';
					$tokens[':chat_id'] = $id1;

					$where[]            = ' `id2` = :chat_id';
					//$tokens[':chat_id'] = $id1;


					if (!empty($where)) {
						$query .= ' WHERE ' . implode(' OR ', $where);
					}

					$query .= ' ORDER BY `updated_at` ASC';

					$sth = self::$pdo->prepare($query);
					$result = $sth->execute($tokens);

					//if ($result) {
						return $sth->fetchAll(PDO::FETCH_ASSOC);
					//} else {
					//	return $query .json_encode($tokens);
					//}
				} catch (PDOException $e) {
					throw new TelegramException($e->getMessage());
				}
		}
		return false;
	}

    //$date_from = date('Y-m-d H:i:s', time() - 60 * 5);//additional time in sec
	//$inputs === ['command' => , 'user_id' => ,'chat_id' => optional ,'status' => 'active' optional, 'date_from' => $date_from, 'my_id' => 123]
	public static function selectConversationN(array $inputs, $limit = null)
    {
        if (!self::isDbConnected()) {
            return false;
        }
		if (null !== $inputs['user_id']){
			$user_id = $inputs['user_id'];
			if (null !== $inputs['chat_id']){
				$chat_id = $inputs['chat_id'];
			} else {
				$chat_id = $user_id;
			}
		}
		if (null !== $inputs['status']){
			$active = $inputs['status'];
		} else {
			$active = 'active';
		}

        try {
            $query = 'SELECT * FROM `conversation` ';
            $query .= 'WHERE `status` = :status ';
			if (null !== $inputs['user_id']){
				$query .= 'AND `chat_id` = :chat_id ';
				$query .= 'AND `user_id` = :user_id ';
			}
			if (null !== $inputs['command']){
				$query .= 'AND `command` = :command ';
			}
			if (null !== $inputs['date_from']) {
                $query .= 'AND `updated_at` >= :date_from ';
            }
			if (null !== $inputs['my_id']) {
                $query .= 'AND `user_id` != :my_id ';
            }
            if (!is_null($limit)) {
                $query .= ' LIMIT :limit ';
            }

            $sth = self::$pdo->prepare($query);

            $sth->bindParam(':status', $active, PDO::PARAM_STR);
			if (null !== $inputs['user_id']){
				$sth->bindParam(':user_id', $user_id, PDO::PARAM_INT);
				$sth->bindParam(':chat_id', $chat_id, PDO::PARAM_INT);
			}
			if (null !== $inputs['command']){
				$sth->bindParam(':command', $inputs['command'], PDO::PARAM_STR);
			}
			if (!is_null($limit)) {
				$sth->bindParam(':limit', $limit, PDO::PARAM_INT);
			}
			if (null !== $inputs['date_from']) {
                $sth->bindParam(':date_from', $inputs['date_from'], PDO::PARAM_STR);
            }
			if (null !== $inputs['my_id']) {
                $sth->bindParam(':my_id', $inputs['my_id'], PDO::PARAM_INT);
            }

            $sth->execute();

            $results = $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new TelegramException($e->getMessage());
        }
        return $results;
    }
}
