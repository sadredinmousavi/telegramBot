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
class MenuCommand extends UserCommand
{
    /**#@+
     * {@inheritdoc}
     */
    protected $name = 'menu';
    protected $description = 'show main menu';
    protected $usage = '/menu';
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



        $keyboard = new Keyboard([
            ['text' => $strings['menu_1']],
        ], [
			['text' => $strings['menu_2_1']],
			['text' => $strings['menu_2_2']],
        ], [
            ['text' => $strings['menu_3_1']],
            ['text' => $strings['menu_3_2']],
        ]);
		$keyboard->setResizeKeyboard(true)
				 ->setOneTimeKeyboard(true)
                 ->setSelective(false);

        $data = [
            'chat_id'      => $chat_id,
            'text'         => $strings['menu_intro'],
            'reply_markup' => $keyboard,
        ];

        return Request::sendMessage($data);
    }
}
