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


use Longman\TelegramBot\MyFuns AS MyFun;
use Longman\TelegramBot\MyDB;

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
    protected $description = 'Cancel the currently active conversation';

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

        $selection = ['users' => true, 'groups' => false, 'super_groups' => false];
        $results = MyDB::selectChatsN('normal', $selection, ['chat_id' => $chat_id]);
        if (!empty($results)) {
            $result = reset($results);
            if (is_array($result)) {
                $lang = $result['lang'];
            }
        }
        $strings = MyFun::getStrings($lang);


        $text = $strings['cancel_nth'];

        //Cancel current conversation if any
        $conversation = new Conversation(
            $this->getMessage()->getFrom()->getId(),
            $this->getMessage()->getChat()->getId()
        );

        if ($conversation_command = $conversation->getCommand()) {
            $conversation->cancel();
            $text = sprintf($strings['cancel_sth'], $conversation_command);
        }

        $a = $this->removeKeyboard($text);

        return $this->getTelegram()->executeCommand("menu");
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
                'reply_markup' => Keyboard::remove(['selective' => true]),
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
