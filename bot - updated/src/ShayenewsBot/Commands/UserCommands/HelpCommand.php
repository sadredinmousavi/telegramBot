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
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Request;

use Longman\TelegramBot\MyFuns AS MyFun;
use Longman\TelegramBot\MyDB;

/**
 * User "/inlinekeyboard" command
 */
class HelpCommand extends UserCommand
{
    /**#@+
     * {@inheritdoc}
     */
    protected $name = 'help';
    protected $description = 'show help';
    protected $usage = '/help';
    protected $version = '0.1.0';
    /**#@-*/

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $message = $this->getMessage();
    	$chat_id = $message->getChat()->getId();
        $type    = $message->getType();
        $text    = trim($message->getText(true));



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





        $data = [
            'chat_id'      => $chat_id,
            'text'         => $strings['help_intro'],
        ];

        return Request::sendMessage($data);
    }
}
