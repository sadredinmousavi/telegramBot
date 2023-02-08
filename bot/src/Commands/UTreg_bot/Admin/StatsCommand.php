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

namespace Longman\TelegramBot\Commands\AdminCommands;

use Longman\TelegramBot\Commands\AdminCommand;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\DBHelper;
use Longman\TelegramBot\DB;
use Longman\TelegramBot\MyDB;
use Longman\TelegramBot\Entities\Chat;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\ConversationDB;

class StatsCommand extends AdminCommand
{
    protected $name = 'stats';
	
	protected $description = 'ÒÇÑÔ íÑí';
	
	protected $usage = '/stats';

    /**
     * @var string
     */
    protected $version = '1.1.0';

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
		
		$conversations = ConversationDB::findStoppedConversation();
		if (!empty($conversations)){
			//$filename = __DIR__ .'/utreg/' . date('Ymd') . '_utreg.csv';
			$filename = date('Ymd') . '_utreg.csv';
			$firstLineKeys = false;
			$fd = fopen ($filename, 'w');
			
			//$text = 'ÈÇ ÊÔ˜Ñ.';$data = ['chat_id'      => $chat_id,'text'         => $text,];
			//$a = Request::sendMessage($data);
			
            foreach ($conversations as $data1) {
				//$user_id    = $data1['id'];
				$notes               = json_decode($data1['notes'], true);
				$notes['id']    = $data1['id'];
				$notes['user_id']    = $data1['user_id'];
				
				//$results = DB::selectChats($group_search, $supergroup_search, $private_search, null, null, $query_string);
				$selection = ['users' => true, 'groups' => false, 'super_groups' => false];
				$results = MyDB::selectChatsN('normal', $selection, ['chat_id' => $data1['user_id']]);
				if (!empty($results)) {
					$result = reset($results);
				}
				
				if (is_array($result)) {
					$result['id'] = $result['chat_id'];
					$chat         = new Chat($result);

					$notes['first_name'] = $result['first_name'];
					$notes['last_name']  = $result['last_name'];
					$notes['username']   = $result['username'];
					$notes['host']       = $result['host']; 
				}
				
				if (empty($firstLineKeys)){
					$firstLineKeys = array_keys($notes);
					fputcsv($fd, $firstLineKeys);
					$firstLineKeys = array_flip($firstLineKeys);
					
				}
	
				fputcsv($fd, $notes);
				
				//$text = 'ÈÇ ÊÔ˜Ñ.';
				//$text .= PHP_EOL .json_encode($data1 ,true);;
					
				//$data = ['chat_id'      => $chat_id,'text'         => $text,];
				//$a = Request::sendMessage($data);
				
				
			}
			fclose($fd);
		}
		
		$text = 'ÝÇíá ÈÇ ãæÝÞíÊ ÓÇÎÊå ÔÏ.' 
				.PHP_EOL .'ÂÏÑÓ ÏÇäáæÏ ÝÇíá  : '
				.PHP_EOL .'kafegame.com/bot/' .$filename;
				  
		
			
		$data = [
			'chat_id'      => $chat_id,
			'text'         => $text,
		];
			
		return Request::sendMessage($data);
    
		
	}

   
}
