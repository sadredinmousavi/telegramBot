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
class MenuCommand extends UserCommand
{
    /**#@+
     * {@inheritdoc}
     */
    protected $name = 'menu';
    protected $description = 'نشان دادن منوی اصلی';
    protected $usage = '/menu';
    protected $version = '0.1.0';
    /**#@-*/

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $chat_id = $this->getMessage()->getChat()->getId();

		$can_continue = $this->getTelegram()->executeCommand("checker");
		if(!$can_continue){
			$d = ['chat_id' => $this->getMessage()->getChat()->getId(), 'text' => 'موقتا این دستور غیر فعال است.'];
			return Request::sendMessage($d);
		}
		
		$conversation = new Conversation($user_id, $chat_id);
		if ($conversation->exists() && ($command = $conversation->getCommand())) {
			$notes = $conversation->notes;
			//if ($command === 'spchat'){
				return $this->telegram->executeCommand($command);
			//}
        }
		
        $keyboard = $this->getTelegram()->executeCommand("keyboard");
		$keyboard->setResizeKeyboard(true)
				 ->setOneTimeKeyboard(true)
                 ->setSelective(false);

        $data = [
            'chat_id'      => $chat_id,
            'text'         => 'منو :',
            'reply_markup' => $keyboard,
        ];

        return Request::sendMessage($data);
    }
}
