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

use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Commands\SystemCommand;

use Longman\TelegramBot\MyFuns AS MyFun;
use Longman\TelegramBot\MyDB;

/**
 * Generic message command
 */
class GenericmessageCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'Genericmessage';

    /**
     * @var string
     */
    protected $description = 'Handle generic message';

    /**
     * @var string
     */
    protected $version = '1.1.0';

    /**
     * @var bool
     */
    protected $need_mysql = true;

    /**
     * Execution if MySQL is required but not available
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     */
    public function executeNoDb()
    {
        //Do nothing
        return Request::emptyResponse();
    }

    /**
     * Execute command
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute()
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();
		$type = $message->getType();
        $text    = trim($message->getText(true));

        //If a conversation is busy, execute the conversation command after handling the message
        $conversation = new Conversation(
            $this->getMessage()->getFrom()->getId(),
            $this->getMessage()->getChat()->getId()
        );
        //Fetch conversation command if it exists and execute it
        if ($conversation->exists() && ($command = $conversation->getCommand())) {
            return $this->telegram->executeCommand($command);
        }


        // $selection = ['users' => true, 'groups' => false, 'super_groups' => false];
        // $results = MyDB::selectChatsN('normal', $selection, ['chat_id' => $chat_id]);
        // if (!empty($results)) {
        //     $result = reset($results);
        //     if (is_array($result)) {
        //         $lang = $result['lang'];
        //     }
        // }
        // $strings = MyFun::getStrings($lang);
        $strings = MyFun::getStrings();


        switch ($text){
            case $strings['menu_2_1']:
                return $this->getTelegram()->executeCommand("banner");
                break;
            case $strings['menu_2_2']:
                return $this->getTelegram()->executeCommand("myscore");
                break;
            case $strings['menu_3_1']:
                return $this->getTelegram()->executeCommand("adminhelp");
                break;
            case $strings['menu_3_2']:
                return $this->getTelegram()->executeCommand("help");
                break;
            case $strings['menu_1']:
                return $this->getTelegram()->executeCommand("newpost");
                break;
            default:
                $result = Request::sendMessage([
                    'chat_id' => $chat_id,
                    'text' => $strings['menu_undefined'],
                    ]);
                return $this->getTelegram()->executeCommand("menu");
                break;
        }



        return Request::emptyResponse();
    }
}
