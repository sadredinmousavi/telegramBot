<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\DB;
use Longman\TelegramBot\Entities\Chat;

/**
 * User "/inlinekeyboard" command
 */
class ShowpageCommand extends UserCommand
{
    /**#@+
     * {@inheritdoc}
     */
    protected $name = 'showpage';
    protected $description = 'مشاهده پست های پیج های خصوصی اینستاگرام';
    protected $usage = '/showpage';
    protected $version = '0.1.0';
    /**#@-*/

    /**
     * {@inheritdoc}
     */
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
		
		
		$data = [
            'chat_id'      => '@Position',
			'user_id'      => $user_id,
        ];
		$Result = Request::getChatMember($data);
		$Results = $Result->getResult();
		if(!empty($Results)){
			$user_status = $Results->getStatus();
		} else {
			$inline_keyboard = new InlineKeyboard([
				['text' => 'عضویت در کانال ', 'url' => 'https://t.me/joinchat/AAAAAD8ppY04HTib-hBn3A'],
			]);
			$text = 'شما هنوز عضو کانال نشده اید' .PHP_EOL 
				.'برای کار با ربات باید ایتدا در کانال عضو شوید' .PHP_EOL .PHP_EOL .PHP_EOL;
			$data = [
				'chat_id'      => $chat_id,
				'text'         => $text,
				'reply_markup' => $inline_keyboard,
			];
			return Request::sendMessage($data);			
		}
		
		
		
		if ($user_status == 'member' || $user_status == 'creator' || $user_status == 'administrator'){
			//$score = $this->getTelegram()->executeCommand("myscore");
			$results = DB::selectChatsByHosts(
				true, //Select groups (group chat)
				true, //Select supergroups (super group chat)
				true, //Select users (single chat)
				null, //'yyyy-mm-dd hh:mm:ss' date range from
				null, //'yyyy-mm-dd hh:mm:ss' date range to
				$user_id, //Specific chat_id to select
				null
			);	
			$user_chats        = 0;
			$group_chats       = 0;
			$super_group_chats = 0;
			if (is_array($results)) {
				foreach ($results as $result) {
					//Initialize a chat object
					$result['id'] = $result['chat_id'];
					$chat         = new Chat($result);
					if ($chat->isPrivateChat()) {
						++$user_chats;
					} elseif ($chat->isSuperGroup()) {
						++$super_group_chats;
					} elseif ($chat->isGroupChat()) {
						++$group_chats;
					}
				}
			}
			if ($user_chats > 4){
				$user_chats = 4;
			}
			$score = $user_chats;
		} else if ($user_status == 'left' || $user_status == 'kicked'){
			$inline_keyboard = new InlineKeyboard([
				['text' => 'عضویت در کانال ', 'url' => 'https://t.me/joinchat/AAAAAD8ppY04HTib-hBn3A'],
			]);
			$text = 'شما از کانال خارج شده اید' .PHP_EOL 
				.'برای کار با ربات باید ایتدا در کانال عضو شوید' .PHP_EOL .PHP_EOL .PHP_EOL;
			$data = [
				'chat_id'      => $chat_id,
				'text'         => $text,
				'reply_markup' => $inline_keyboard,
			];
			return Request::sendMessage($data);	
		}
		$howmany = 5 - $score;
		$text = 'تعداد کاربرانی که شما دعوت کرده اید ' .$score .PHP_EOL 
			.' نفر است. برای استفاده از امکانات ربات باید ' .$howmany .'نفر دیگر به ربات اضافه کنید.' .PHP_EOL .PHP_EOL .PHP_EOL
			.'برای دریافت بنر اختصاصی از دستور زیر استفاده کنید.' .PHP_EOL 
			.'/banner' .PHP_EOL;
        $data = [
            'chat_id'      => $chat_id,
            'text'         => $text,
        ];

        return Request::sendMessage($data);
    }
}
