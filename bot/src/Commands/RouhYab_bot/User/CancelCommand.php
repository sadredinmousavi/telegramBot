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
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Request;

/**
 * User "/cancel" command
 *
 * This command cancels the currently active conversation and
 * returns a message to let the user know which conversation it was.
 * If no conversation is active, the returned message says so.
 */
class CancelCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'cancel';

    /**
     * @var string
     */
    protected $description = 'لغو پروسه و بازگشت به محیط اصلی';

    /**
     * @var string
     */
    protected $usage = '/cancel';

    /**
     * @var string
     */
    protected $version = '0.2.0';

    /**
     * @var bool
     */
    protected $need_mysql = true;

    /**
     * Command execute method
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute()
    {
        $text = 'هیچ پروسه ای باز نیست!';
		
		
		$can_continue = $this->getTelegram()->executeCommand("checker");
		if(!$can_continue){
			$d = ['chat_id' => $this->getMessage()->getChat()->getId(), 'text' => 'موقتا این دستور غیر فعال است.'];
			return Request::sendMessage($d);
		}
		

        //Cancel current conversation if any
        $conversation = new Conversation(
            $this->getMessage()->getFrom()->getId(),
            $this->getMessage()->getChat()->getId()
        );

        if ($conversation_command = $conversation->getCommand()) {
            $conversation->cancel();
            $text = 'پروسه' .' " ' . $conversation_command . ' " ' .'لغو شد.';
        }

        return $this->removeKeyboard($text);
    }

    /**
     * Remove the keyboard and output a text
     *
     * @param string $text
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    private function removeKeyboard($text)
    {
        return Request::sendMessage(
            [
                //'reply_markup' => Keyboard::remove(['selective' => true]),
				'reply_markup' => $this->getTelegram()->executeCommand("keyboard"),
                'chat_id'      => $this->getMessage()->getChat()->getId(),
                'text'         => $text,
            ]
        );
    }

    /**
     * Execute no db
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function executeNoDb()
    {
        return $this->removeKeyboard('Nothing to cancel.');
    }
}
