<?php
/**
 * Inline Games - Telegram Bot (@inlinegamesbot)
 *
 * Copyright (c) 2016 Jack'lul <https://jacklul.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\DBHelper;
use Longman\TelegramBot\DB;
use Longman\TelegramBot\Entities\Chat;

class StartCommand extends SystemCommand
{
    protected $name = 'Start';

    public function execute()
    {
        $message = $this->getMessage();
		$chat_id = $message->getChat()->getId();
		$chat_type = $message->getChat()->getType();
		$user_id = $message->getFrom()->getId();
		$user_Fname = $message->getFrom()->getFirstName();
        $user_Lname = $message->getFrom()->getLastName();
        $user_Uname = $message->getFrom()->getUsername();
		$type = $message->getType();
        $text_m    = trim($message->getText(true));
		
		if ($text_m === ''){
			$text = 'سلام'.PHP_EOL 
				.'یک سری توضیحات . . . . ' .PHP_EOL  .PHP_EOL 
				.'ورود بدون گد و بدون معرفی' .PHP_EOL;
			$host_id = 1001;
		} else {
			$text = 'سلام'.PHP_EOL 
				.'یک سری توضیحات . . . . ' .PHP_EOL  .PHP_EOL 
				.'ورود با کد' .PHP_EOL 
				.$text_m .PHP_EOL .PHP_EOL
				.'معرفی توسط کاربر با ایدی' .PHP_EOL 
				.base64_decode($text_m) .PHP_EOL;
			$host_id = base64_decode($text_m);
		}
        $data = [
            'chat_id'      => $chat_id,
            'text'         => $text,
        ];
		
		if ($chat_type == 'private'){
			$result = Request::sendMessage($data);
		}
		
		//
		//
		//
		$user_id    = $text;
        $chat       = null;
        $created_at = null;
        $updated_at = null;
        $result     = null;
			
		$private_search = false;
		$group_search = false;
		$supergroup_search = false;
		
		switch ($chat_type){
			case 'private':
				$private_search = true;
				$query_string = $user_id;
				break;
			case 'group':
				$group_search = true;
				$supergroup_search = true;
				$query_string = $chat_id;
				break;
			case 'supergroup':
				$group_search = true;
				$supergroup_search = true;
				$query_string = $chat_id;
				break;
			default:
		}
		$results = DB::selectChats(
            $group_search, //Select groups (group chat)
            $supergroup_search, //Select supergroups (super group chat)
            $private_search, //Select users (single chat)
            null, //'yyyy-mm-dd hh:mm:ss' date range from
            null, //'yyyy-mm-dd hh:mm:ss' date range to
            $query_string //Specific chat_id to select
        );
		if (!empty($results)) {
            $result = reset($results);
        }
		
		if (is_array($result)) {
            $result['id'] = $result['chat_id'];
            $chat         = new Chat($result);

            $user_id    = $result['id'];
            $created_at = $result['chat_created_at'];
            $updated_at = $result['chat_updated_at'];
            $old_id     = $result['old_id'];
			$host       = $result['host']; 
        }
		
		if (empty($host)){
			$migrate_to_chat_id = $this->getMessage()->getMigrateToChatId();		
			$chat               = $this->getMessage()->getChat();		
			$date               = date('Y-m-d H:i:s', time());
			
			DB::insertChatHostId($chat, $date, $host_id, $migrate_to_chat_id);
		}
		
		
		
		
			$text = 'سلام'.PHP_EOL 
				.'یک سری توضیحات . . . . ' .PHP_EOL  .PHP_EOL 
				.'ورود بدون گد و بدون معرفی' .PHP_EOL
				.!empty($results) .'     results' .PHP_EOL
				.empty($host) .'     host' .PHP_EOL;
			$data = [
				'chat_id'      => $chat_id,
				'text'         => $text,
			];
			
			if ($chat_type == 'private'){
				$result = Request::sendMessage($data);
			}
		//
		//
		//
        if ($message && $chat_type == 'private') {
            return $this->getTelegram()->executeCommand("menu");
        }

        return Request::emptyResponse();
    }
}
