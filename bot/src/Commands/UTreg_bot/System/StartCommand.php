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
use Longman\TelegramBot\MyDB;
use Longman\TelegramBot\Entities\Chat;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\Keyboard;

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
			$host_id = 1000;
		} else {
			$host_id = $text_m;
		}
		
		$private_search = true;
		$group_search = false;
		$supergroup_search = false;
		
		
		//$results = DB::selectChats($group_search, $supergroup_search, $private_search, null, null, $query_string);
		$selection = ['users' => $private_search, 'groups' => $group_search, 'super_groups' => $supergroup_search];
		$results = MyDB::selectChatsN('normal', $selection, ['chat_id' => $chat_id]);
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
			
			//DB::insertChatInitialization($chat, $date, $host_id, $migrate_to_chat_id);
			$R = MyDB::insertChatDetails($chat, ['host_id' => $host_id]);
			
			$text = sprintf(
                'به' .' %s ' .'خوش آمدید' .json_decode('"\uD83C\uDF39"') .PHP_EOL,
                $this->telegram->getBotName()
            );
			
			$text .= 'این ربات  حاوی پرسش نامه ای جهت ارزيابي ميزان رضايت شما دانشجويان گرامي از فرايند ثبت نام در دانشگاه تهران در سال ١٣٩٦  می باشد' 
				  .PHP_EOL .'از اين رو تقاضا ميگردد با دقت به سوالات مطرح شده در اين پرسشنامه پاسخ دهيد تا ضمن شناسايي نقاط قوت و ضعف موجود در اين فرايند، زمينه لازم براي بهبود آن در سال هاي آتي فراهم گردد.';
			
			$data = [
				'chat_id'      => $chat_id,
				'text'         => $text,
			];
			
			if ($chat_type == 'private'){
				$result = Request::sendMessage($data);
			}
		} else {
			
		}
		
		
		
		
		$text = 'برای شروع پرسش نامه از دستور ' .PHP_EOL .'/survey' .PHP_EOL .'استفاده نمایید'
				.PHP_EOL .'برای اطلاع از سایر دستورهای ربات نیز دستور' .' /help ' .'را بزنید.';
			
		$data = [
			'chat_id'      => $chat_id,
			'text'         => $text,
		];
		if ($chat_type == 'private'){
			$result = Request::sendMessage($data);
		}

		

		
		

        return Request::emptyResponse();
    }
}
