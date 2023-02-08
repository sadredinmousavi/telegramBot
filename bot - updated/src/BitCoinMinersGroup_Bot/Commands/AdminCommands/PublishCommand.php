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

use Longman\TelegramBot\TelegramLog;



// //require '/home/kafegame/public_html/instabot/core.php';
// //$path = $_SERVER['DOCUMENT_ROOT'] .'/instabot/core.php';
// require_once '/home2/kafegame/public_html/instabot/core.php';

use \Mylib\DB AS InstaDB;
use core;
use Longman\TelegramBot\MyFuns AS MyFun;
use Longman\TelegramBot\MyDB;



class PublishCommand extends AdminCommand
{
    protected $name = 'publish';

	protected $description = 'Publish a number of related post to all channels';

	protected $usage = '/publish';

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
        $channels = InstaDB::selectChannels();
        foreach ($channels as $channel) {
            // echo "<pre>", print_r($channel), "</pre>";
            if ($channel['videos'] > 0){
                MyFun::publish_update_posts($channel['channel_id'], 'videos', 'publish');
            }
            if ($channel['images'] > 0){
                MyFun::publish_update_posts($channel['channel_id'], 'images', 'publish');
            }
            if ($channel['carousels'] > 0){
                MyFun::publish_update_posts($channel['channel_id'], 'carousels', 'publish');
            }

        }







        return true;


	}
}
