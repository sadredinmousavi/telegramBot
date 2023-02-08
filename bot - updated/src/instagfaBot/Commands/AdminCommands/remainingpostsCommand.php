<?php

namespace Longman\TelegramBot\Commands\AdminCommands;


use Longman\TelegramBot\Commands\AdminCommand;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\DB;
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
use Longman\TelegramBot\MyFuns AS MyFun;
use Longman\TelegramBot\MyDB;



class remainingpostsCommand extends AdminCommand
{
    protected $name = 'remainingposts';

    protected $description = 'Report unpublished posts in database';

	protected $usage = '/remainingposts';

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
        $i = 0;
        foreach ($channels as $channel) {
            $options = MyFun::selectionOptionsCreator($channel['channel_id']);
            $data['text'] .= 'Channel (' .$i .') [' .$channel['user_name'] .']' .PHP_EOL;
            if ($channel['videos'] > 0){
                $a = InstaDB::selectMedia(['videos' => true, 'images' => false, 'carousels' => false], $options, $date_from = strtotime("-" .$channel['date_from'] ." day"));
                $data['text'] .= count($a) .' remaining unpublished videos' .PHP_EOL;
            }
            if ($channel['images'] > 0){
                $a = InstaDB::selectMedia(['videos' => false, 'images' => true, 'carousels' => false], $options, $date_from = strtotime("-" .$channel['date_from'] ." day"));
                //echo "<pre>", print_r($a), "</pre>";
                $data['text'] .= count($a) .' remaining unpublished images' .PHP_EOL;
            }
            if ($channel['carousels'] > 0){
                $a = InstaDB::selectMedia(['videos' => false, 'images' => false, 'carousels' => true], $options, $date_from = strtotime("-" .$channel['date_from'] ." day"));
                $data['text'] .= count($a) .' remaining unpublished carousels' .PHP_EOL;
            }
            $data['text'] .= PHP_EOL .PHP_EOL;
            $i = $i + 1;
        }
        return Request::sendMessage($data);

    }
}
