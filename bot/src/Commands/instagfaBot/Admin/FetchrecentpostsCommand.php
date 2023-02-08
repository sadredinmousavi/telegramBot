<?php
/**
 * Inline Games - Telegram Bot (@inlinegamesbot)
 *
 * Copyright (c) 2016 Jack'lul <https://jacklul.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace Longman\TelegramBot\Commands\AdminCommands;


use Longman\TelegramBot\Commands\AdminCommand;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\DBHelper;
use Longman\TelegramBot\DB;
use Longman\TelegramBot\MyDB;
use Longman\TelegramBot\Entities\Chat;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\ConversationDB;


//require '/home/kafegame/public_html/instabot/core.php';
//$path = $_SERVER['DOCUMENT_ROOT'] .'/instabot/core.php';
require_once '/home2/kafegame/public_html/instabot/core.php';

use \Mylib\DB AS InstaDB;
use core;



class FetchrecentpostsCommand extends AdminCommand
{
    protected $name = 'fetchrecentposts';

	  protected $description = 'Fetch recent posts according to hot list indicated in channels database';

	  protected $usage = '/Fetchrecentposts';

    /**
     * @var string
     */
    protected $version = '1.1.0';

    public function execute()
    {
    $message = $this->getMessage();
		$chat_id = $message->getChat()->getId();
		$chat_type = $message->getChat()->getType();
		$user_id = $message->getFrom()->getId();
		$user_Fname = $message->getFrom()->getFirstName();
    $user_Lname = $message->getFrom()->getLastName();
    $user_Uname = $message->getFrom()->getUsername();
		$type = $message->getType();
    $text_m    = trim($message->getText(true));




    $type = 'photo';
    $instagram = new core();
    //$instagram->enableMySql($mysql_credentials);
    $instagram->enableMySql();

    //
    $data = [];
    $data['chat_id'] = $chat_id;
    $data['text'] = '';
    $channels = InstaDB::selectChannels();
    foreach ($channels as $channel) {
        if ($channel['check_latest_commits'] === '1') {
            $current_ch_id = $channel['channel_id'];
            $a = InstaDB::selectUsersToChannels($current_ch_id);
            foreach ($a as $user) {
                $username = $user['username'];
                $res = $instagram->getUserfromInsta($username);
                $instagram->getUserLastMediafromInsta($username);
                $res_t = $res === true ? 'ok' : 'not ok';
            }
        // $data['text'] .= 'Channel (' .$i .') [' .$channel['user_name'] .']'
        //                     .PHP_EOL .count($a) .' remaining unpublished posts' .PHP_EOL .PHP_EOL;
        //     $i = $i + 1;
        }

    }
    $data['text'] .= 'Done';
    return Request::sendMessage($data);
    //
    //
    //
    //

    if(empty($a)){
        return true;
    }


    foreach ($a as $media) {
        $reg = '/#\S+\s*/';
        $media['caption'] = preg_replace($reg, '', $media['caption']);
        if (strlen($media['caption'])>200){
            $media['caption'] = ' ' .PHP_EOL;
        }
        if(!isset($media['link'])){
            $media['link'] = 'https://www.instagram.com/p/' .$media['code'];
        }
        $user_mention = '<a href="' .$media['link'] .'">' .$media['username'] .'</a>';
        //
        //
        //
        $data = [];
        $data['chat_id'] = $current_ch_id;
        $data['text'] = json_decode('"\uD83D\uDC64"') .$user_mention .json_decode('"\uD83D\uDC64"');
        $data['caption'] = json_decode('"\uD83D\uDC64"') .$media['username'] .json_decode('"\uD83D\uDC64"')
                          .PHP_EOL .json_decode('"\u2764\uFE0F"') .' ' .$media['likes']
                          .PHP_EOL .$media['caption']
                          .PHP_EOL .PHP_EOL .json_decode('"\uD83D\uDC49"') .' @instagfa';
        $data['disable_notification'] = true;
        //$data['disable_web_page_preview'] = true;
        //$data['parse_mode'] = 'HTML';
        //
        //
        //
        //
        if ($type === 'message') {
            //$data['text'] = $message->getText(true);
            //$data['text'] = 'asdasd';
        } elseif ($type === 'photo') {
            //$data['photo'] = $message->getPhoto()[0]->getFileId();
            $data['photo'] = $media['img_standard_resolution'];
        } elseif ($type === 'video') {
            $data['video'] = $message->getVideo()->getFileId();
        }

        $callback_path     = 'Longman\TelegramBot\Request';
        $callback_function = 'send' . ucfirst($type);
        if (!method_exists($callback_path, $callback_function)) {
            throw new TelegramException('Methods: ' . $callback_function . ' not found in class Request.');
        }
        //
        //
        //
        $result = $callback_path::$callback_function($data);
        InstaDB::insertPublished($current_ch_id, $media['media_id'], $result->getResult()->getmessage_id());
    }

    return true;


	}


}
