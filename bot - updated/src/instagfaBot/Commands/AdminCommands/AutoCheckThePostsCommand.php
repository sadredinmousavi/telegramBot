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



class AutoCheckThePostsCommand extends AdminCommand
{
    protected $name = 'autochecktheposts';

	protected $description = 'Automatically check if a post is advertisement or inappropriate.';

	protected $usage = '/autochecktheposts';

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
        //
        //unset($options['not_published_in']);
        //$options['limit_rows'] = 1;
        $options['checked'] = 0;
        //
        $selection = ['videos' => false, 'images' => false, 'carousels' => false];
        $channel['videos'] > 0 &&  $selection['videos'] = true;
        $channel['images'] > 0 &&  $selection['images'] = true;
        $channel['carousels'] > 0 &&  $selection['carousels'] = true;
        //
        $a = InstaDB::selectMedia($selection, $options, $date_from = strtotime("-" .$notes['date_from'] ." day"));
        foreach ($a as $media) {
            if (MyFun::checkCaptionForAds($media)){
                echo "<pre>", print_r($media), "</pre>";
            }

        }
    }
    $data['text'] .= 'Done.';
    return Request::sendMessage($data);

	}



}
