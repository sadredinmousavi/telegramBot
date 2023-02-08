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
class BannerCommand extends UserCommand
{
    /**#@+
     * {@inheritdoc}
     */
    protected $name = 'banner';
    protected $description = 'show your banner';
    protected $usage = '/banner';
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

        $text = sprintf($strings['banner_text'], 'https://T.me/' .$this->telegram->getBotUsername() .'?start=' .base64_encode($chat_id));


        // $data = [
        //     'chat_id'      => $chat_id,
        //     'photo'        => 'AgADBAADmKkxG5ZTQFClCuxLNak7GqaouxkABFn-vuL-NlyloKcAAgI',
		// 	   'caption'      => $text,
        // ];
        //
        // return Request::sendPhoto($data);


        $data = [
            'chat_id'      => $chat_id,
            'text'         => $text,
        ];

        return Request::sendMessage($data);
    }
}
