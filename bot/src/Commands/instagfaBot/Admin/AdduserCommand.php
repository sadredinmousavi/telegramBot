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



class AdduserCommand extends AdminCommand
{
    protected $name = 'adduser';

	  protected $description = 'Add a group of users and scan their latest medias';

	  protected $usage = '/Adduser';

    /**
     * @var string
     */
    protected $version = '1.1.0';

    protected $conversation;

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



    in_array($type, ['command', 'text'], true) && $type = 'message';

    $text           = trim($message->getText(true));
    $text_yes_or_no = ($text === 'Yes' || $text === 'No');

    $data = [
        'chat_id' => $chat_id,
    ];
    // Conversation
    $this->conversation = new Conversation($user_id, $chat_id, $this->getName());

    $notes = &$this->conversation->notes;
    !is_array($notes) && $notes = [];

    $instagram = new core();
    //$instagram->enableMySql($mysql_credentials);
    $instagram->enableMySql();

    $channels = InstaDB::selectChannels();
    if (isset($notes['state'])) {
        $state = $notes['state'];
    } else {
        $state                    = 0;
        $notes['last_message_id'] = $message->getMessageId();
    }

    switch ($state) {
        default:
        case 0:
            // getConfig has been configured choose channel
            // if ($type !== 'message' || !in_array($text, array_column($channels, 'user_name'), true)) {
            //     $notes['state'] = 0;
            //     $this->conversation->update();
            //
            //     $keyboard = [];
            //     foreach ($channels as $channel) {
            //         $keyboard[] = [$channel['user_name']];
            //     }
            //     $data['reply_markup'] = new Keyboard(
            //         [
            //             'keyboard'          => $keyboard,
            //             'resize_keyboard'   => true,
            //             'one_time_keyboard' => true,
            //             'selective'         => true,
            //         ]
            //     );
            //
            //     $data['text'] = 'Select a channel from the keyboard:';
            //     $result       = Request::sendMessage($data);
            //     break;
            // }
            // $user_names = array_column($channels, 'user_name');
            // $user_ids = array_column($channels, 'channel_id');
            // $notes['channel']         = $user_ids[array_search ($text, $user_names)];
            // $notes['last_message_id'] = $message->getMessageId();

        // no break
        case 1:
            insert:
            if (($type === 'message' && $text === '') || $notes['last_message_id'] === $message->getMessageId()) {
                $notes['state'] = 1;
                $this->conversation->update();

                $data['reply_markup'] = Keyboard::remove(['selective' => true]);
                $data['text']         = 'Please insert list of names in the following format (each username in a seperate line)'
                                        .PHP_EOL .'reza'
                                        .PHP_EOL .'davood'
                                        .PHP_EOL .'dadollah'
                                        .PHP_EOL .'ramsey';
                $result               = Request::sendMessage($data);
                break;
            }
            $notes['last_message_id'] = $message->getMessageId();
            $notes['message']         = $message->getText(true);;
            $notes['message_type']    = $type;
        // no break
        case 2:
            if (!$text_yes_or_no || $notes['last_message_id'] === $message->getMessageId()) {
                $notes['state'] = 2;
                $this->conversation->update();

                // Execute this just with object that allow caption
                $data['reply_markup'] = new Keyboard(
                    [
                        'keyboard'          => [['Yes', 'No']],
                        'resize_keyboard'   => true,
                        'one_time_keyboard' => true,
                        'selective'         => true,
                    ]
                );

                $data['text'] = 'Would you like to do a full scan?'
                                .PHP_EOL .'please note that a full scan means get all medias of each person from the begining.'
                                .PHP_EOL .'so this means it takes a long time.'
                                .PHP_EOL .'and this is not necesary for now.';
                if (!$text_yes_or_no && $notes['last_message_id'] !== $message->getMessageId()) {
                    $data['text'] .= PHP_EOL . 'Type Yes or No';
                }
                $result = Request::sendMessage($data);
                break;
            }
            $notes['full_scan']     = ($text === 'Yes');
            $notes['last_message_id'] = $message->getMessageId();
        // no break
        case 3:
            $notes['state'] = 3;
            $this->conversation->update();
            $data['reply_markup'] = Keyboard::remove(['selective' => true]);
            //
            $usernames = explode("\n", $notes['message']);
            //
            //
            $response_text_h = 'please wait until process is done.'
                              .PHP_EOL .'you entered ' .count($usernames) .' usernames.'
                              .PHP_EOL;
            $response_text_f ='';


            $data = [];
            $data['chat_id'] = $chat_id;
            $data['text'] = $response_text_h;
            $message_data = Request::sendMessage($data);

            $data = [];
            $data['chat_id'] = $chat_id;
            $data['message_id'] = $message_data->getResult()->getMessageId();

            foreach ($usernames as $username) {
                $username = trim(trim($username, "@\t\n-"));
                if(strlen($username) < 1){
                    continue;
                }
                $response_text_m = PHP_EOL .'fetching ' .$username .' profile data ...' .PHP_EOL .PHP_EOL;

                $data['text'] = $response_text_h .$response_text_m .$response_text_f;
                $a = Request::editMessageText($data);
                //$a = Request::sendMessage($data);

                if ($notes['full_scan'] == 'yes') {
                    $res = $instagram->getUserfromInsta($username, true);
                } else {
                    $res = $instagram->getUserfromInsta($username);
                }

                $response_text_m = PHP_EOL .'fetching ' .$username .' latest media data ...' .PHP_EOL .PHP_EOL;

                $data['text'] = $response_text_h .$response_text_m .$response_text_f;
                $a = Request::editMessageText($data);


                $instagram->getUserLastMediafromInsta($username);
                $res_t = $res === true ? 'ok' : 'not ok';
                $response_text_f .= $username .' -->  ' .$res_t .PHP_EOL;

                //$data['text'] = $response_text_f;
                //$a = Request::sendMessage($data);
            }

            $response_text_m = PHP_EOL .'done  cheers' .PHP_EOL .PHP_EOL;
            $data['text'] = $response_text_h .$response_text_m .$response_text_f;
            $result = Request::editMessageText($data);
            //$a = Request::sendMessage($data);

            $data['text'] = 'Done. Congradulations.';
            $data['reply_markup'] = Keyboard::remove(['selective' => true]);
            $a = Request::sendMessage($data);

            $this->conversation->stop();
      }
      return $result;

	}


}
