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
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Entities\PhotoSize;
use Longman\TelegramBot\Entities\Chat;
use Longman\TelegramBot\DB;

/**
 * User "/inlinekeyboard" command
 */
class DeactiveCommand extends UserCommand
{
    /**#@+
     * {@inheritdoc}
     */
    protected $name = 'loc';
    protected $description = 'فعال کردن چت عمومی';
    protected $usage = '/active';
    protected $version = '0.1.0';
    /**#@-*/

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $message = $this->getMessage();
		$chat = $message->getChat();
        $chat_id = $message->getChat()->getId();
		$chat_type = $message->getChat()->getType();
		$user_id = $message->getFrom()->getId();
		$user_Fname = $message->getFrom()->getFirstName();
        $user_Lname = $message->getFrom()->getLastName();
        $user_Uname = $message->getFrom()->getUsername();
		$type = $message->getType();
        $text_m    = trim($message->getText(true));
		

		$can_continue = $this->getTelegram()->executeCommand("checker");
		if(!$can_continue){
			$d = ['chat_id' => $this->getMessage()->getChat()->getId(), 'text' => 'موقتا این دستور غیر فعال است.'];
			return Request::sendMessage($d);
		}
		
		

		$R = DB::insertChatDetails($chat, ['active' => '0']);
		//$R = DB::insertChatDetails($chat, ['lat' => $location->getLatitude, 'lng' => $location->getLongitude]);
				
		$keyboard = $this->getTelegram()->executeCommand("keyboard");
		
		$keyboard->setResizeKeyboard(true)
				 ->setOneTimeKeyboard(true)
				 ->setSelective(false);
				 
				
		$text = 'چت در حالت بی صدا قرار گرفت. ' .PHP_EOL;
		$data = [
			'chat_id'      => $chat_id,
			'text'         => $text,
			//'reply_markup' => $keyboard,
		];
				
		$data['reply_markup'] = $keyboard;
				
		return Request::sendMessage($data);
				


    }
}
