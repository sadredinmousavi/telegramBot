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



class TestCommand extends AdminCommand
{
    protected $name = 'test';

	protected $description = 'Publish a number of related post to all channels';

	protected $usage = '/test';

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


        // $data2 =[];
        // $data2['message_id'] = '1024';
        // $data2['chat_id'] = '-1001057227310';//'270783544';//'-1001057227310';
        // $data2['text'] = 'دیوید جونز، سازنده بازی GTA، در سال 1990 در آمریکا،پس از سرقت یک تانک ،به خیابان های شهر رفته و چندین ماشین را له کرد تا ایده بازی خود را به صورت واقعی تست کند! '
        //                 .'(ویدئوی زیر)' .PHP_EOL
        //                 .'درنهایت وی توسط پلیس دستگیر میشود و پس از گذراندن دوران محکومیت خود پس از 7 سال آزاد شده و اولین سری GTA، را در سال 1997 به بازار عرضه میکند.'
        //                 .PHP_EOL
        //                 .'<a href="https://t.me/zasxwq/5">&#8205;</a>'
        //                 .PHP_EOL
        //                 .' لینک عضویت:
        //                     https://telegram.me/joinchat/A7z4Vj8EAi4YrFaeEBuoOQ';
        //
        // $data2['disable_web_page_preview'] = false;
        // $data2['disable_notification'] = true;
        // $data2['parse_mode'] = 'HTML';
        // $result = Request::editMessageText($data2);
        // //$result = Request::sendMessage($data2);






        $instagram = new core();
        //$instagram->enableMySql($mysql_credentials);
        $instagram->enableMySql();

        //
        // $channels = InstaDB::selectChannels();
        // foreach ($channels as $channel) {
        //     // echo "<pre>", print_r($channel), "</pre>";
        //     if ($channel['videos'] > 0){
        //         MyFun::publish_update_posts($channel['channel_id'], 'videos', 'publish');
        //     }
        //     if ($channel['images'] > 0){
        //         MyFun::publish_update_posts($channel['channel_id'], 'images', 'publish');
        //     }
        //     if ($channel['carousels'] > 0){
        //         MyFun::publish_update_posts($channel['channel_id'], 'carousels', 'publish');
        //     }
        //
        // }
        MyFun::publish_update_posts(270783544, 'images', 'publish');







        return true;


	}
}
