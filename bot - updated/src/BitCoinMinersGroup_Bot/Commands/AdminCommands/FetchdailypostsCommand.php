<?php


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


// //require '/home/kafegame/public_html/instabot/core.php';
// //$path = $_SERVER['DOCUMENT_ROOT'] .'/instabot/core.php';
// require_once '/home2/kafegame/public_html/instabot/core.php';

use \Mylib\DB AS InstaDB;
use core;



class FetchdailypostsCommand extends AdminCommand
{
    protected $name = 'fetchdailyposts';

	  protected $description = 'Fetch daily posts according to lists indicated in channels database';

	  protected $usage = '/Fetchdailyposts';

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




        $instagram = new core();
        //$instagram->enableMySql($mysql_credentials);
        $instagram->enableMySql();

        //
        $data = [];
        $data['chat_id'] = $chat_id;
        $data['text'] = '';
        $channels = InstaDB::selectChannels();
        foreach ($channels as $channel) {
            if ($channel['check_latest_commits'] === '0') {
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
    }




}
