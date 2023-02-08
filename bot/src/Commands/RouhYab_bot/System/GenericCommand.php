<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Request;

/**
 * Generic command
 */
class GenericCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'Generic';

    /**
     * @var string
     */
    protected $description = 'Handles generic commands or is executed by default when a command is not found';

    /**
     * @var string
     */
    protected $version = '1.1.0';

    /**
     * Command execute method
     *
     * @return mixed
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute()
    {
        $message = $this->getMessage();

        //You can use $command as param
        $chat_id = $message->getChat()->getId();
        $user_id = $message->getFrom()->getId();
        $command = $message->getCommand();

        //If the user is and admin and the command is in the format "/whoisXYZ", call the /whois command
        if (stripos($command, 'whois') === 0 && $this->telegram->isAdmin($user_id)) {
            return $this->telegram->executeCommand('whois');
        }
		
		//If the command is in the format "/userXYZ", call the /user command
        if (stripos($command, 'u') === 0 && stripos($command, 'un') === false){
            return $this->telegram->executeCommand('u');
        }
		
		if (stripos($command, 'ban') !== false || stripos($command, 'report') !== false || stripos($command, 'follow') !== false){
            return $this->telegram->executeCommand('relation');
        }
		

        $data = [
            'chat_id' => $chat_id,
            'text'    => 'دستور' .' /' . $command . ' وجود ندارد',
			'reply_markup' => $this->getTelegram()->executeCommand("keyboard"),
        ];

        return Request::sendMessage($data);
    }
}
