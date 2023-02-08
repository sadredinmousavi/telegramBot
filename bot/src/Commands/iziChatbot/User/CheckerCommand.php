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
use Longman\TelegramBot\MyDB;

/**
 * User "/inlinekeyboard" command
 */
class CheckerCommand extends UserCommand
{
    /**#@+
     * {@inheritdoc}
     */
    protected $name = 'checker';
    protected $description = ' ';
    protected $usage = '/checker';
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
		//
        $text_m    = trim($message->getText(true));
		$command_m = $message->getCommand();
		
        $conversation = new Conversation($user_id, $chat_id);
		if ($conversation->exists() && ($command = $conversation->getCommand())) {
			$notes = $conversation->notes;
			if ($command === 'spchat'){
				
				return false;
			} else {
				//return $this->telegram->executeCommand($command);
				return true;
			}
        }
		return true;

       
    }
}
