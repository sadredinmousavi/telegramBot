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



class ManualCheckThePostsCommand extends AdminCommand
{
    protected $name = 'manualchecktheposts';

	protected $description = 'manually check if a post is advertisement or inappropriate.';

	protected $usage = '/manualchecktheposts';

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
    $text_yes_or_no = ($text === 'Yes' || $text === 'No' || $text === 'add' || $text === 'remove');
    $ok = json_decode('"\u2714\uFE0F"');
    $not_ok = json_decode('"\u26D4\uFE0F"');
    $in_keyboard = ($text === $ok || $text === $not_ok || $text === 'stop' || $text === 'check 50 in a row');

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
    $ids = [];
    foreach ($channels as $channel) {
        $admin = InstaDB::selectAdminsChannels(['admin_id' => $chat_id, 'channel_id' => $channel['channel_id']]);
        if (!empty($admin)){
            if (!$admin[0]['can_check']){
                $ids [] =array_search($channel['channel_id'], array_column($channels, 'channel_id'));
            }
        } else {
            $ids [] =array_search($channel['channel_id'], array_column($channels, 'channel_id'));
        }
    }
    foreach ($ids as $key => $value) {
        if (!in_array($chat_id, ['270783544', '63200004'])){
            unset($channels[$value]);
        }
    }
    if (empty($channels)){
        $data['text'] = 'You are not admin to any channel.';
        $result = Request::sendMessage($data);
        return true;
    }
    


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
            if ($type !== 'message' || !in_array($text, array_column($channels, 'user_name'), true)) {
                $notes['state'] = 0;
                $this->conversation->update();

                $keyboard = [];
                foreach ($channels as $channel) {
                    $keyboard[] = [$channel['user_name']];
                }
                $data['reply_markup'] = new Keyboard(
                    [
                        'keyboard'          => $keyboard,
                        'resize_keyboard'   => true,
                        'one_time_keyboard' => true,
                        'selective'         => true,
                    ]
                );

                $data['text'] = 'unchecked posts will showup then you can flag them as \'ad\', \'inappropraite\', or \'good\''
                                .PHP_EOL .PHP_EOL .'Please select a channel from the keyboard:';
                $result       = Request::sendMessage($data);
                break;
            }
            $user_names = array_column($channels, 'user_name');
            $user_ids = array_column($channels, 'channel_id');
            $date_froms = array_column($channels, 'date_from');
            $notes['channel']         = $user_ids[array_search ($text, $user_names)];
            $notes['date_from']       = $date_froms[array_search ($text, $user_names)];
            $notes['last_message_id'] = $message->getMessageId();
            //
            //
            // $list = InstaDB::selectUsersToChannels($notes['channel']);
            // $text = count($list) .' users are assigned to channel (' .$user_names[array_search ($text, $user_names)] .') :' .PHP_EOL;
            // foreach ($list as $user) {
            //     $text .= PHP_EOL .$user['username'];
            // }
            // $data['text'] = $text;
            // $result       = Request::sendMessage($data);
            //
            // no break
        case 1:
            if (!$text_yes_or_no || $notes['last_message_id'] === $message->getMessageId()) {
                $notes['state'] = 1;
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

                $data['text'] = 'Do you want captions to show? some captions might be too long!!'
                                .PHP_EOL .PHP_EOL .'show captions before pic or vid? --> just press \'Yes\''
                                .PHP_EOL .PHP_EOL .'only show pic or vid? --> just press \'No\'';
                if (!$text_yes_or_no && $notes['last_message_id'] !== $message->getMessageId()) {
                    $data['text'] .= PHP_EOL . 'Type Yes or No';
                }
                $result = Request::sendMessage($data);
                break;
            }
            $notes['show_caption']    = $text;//($text === 'Yes');
            $notes['last_message_id'] = $message->getMessageId();
        // no break
        case 2:
            $keyboard = new Keyboard(['keyboard' => [[$ok, $not_ok], ['stop'], ['check 50 in a row']], 'resize_keyboard' => true, 'one_time_keyboard' => true, 'selective' => true,]);
            if (!$in_keyboard || $notes['last_message_id'] === $message->getMessageId()) {
                $notes['state'] = 2;
                $this->conversation->update();
                if ($notes['last_message_id'] === $message->getMessageId()){
                    // echo "ok";
                    $notes = self::sendPost($notes, $keyboard, $data);
                    $this->conversation->update();
                } else {
                    $data['text']         = 'please only select from the \'Keyborad\'.';
                    $data['reply_markup'] = $keyboard;
                    $result  = Request::sendMessage($data);
                }
                break;
            } elseif ($in_keyboard){
                switch ($text) {
                    case $ok:
                        InstaDB::updateMedia($notes['last_media_id'], ['checked' => 1]);
                        InstaDB::updateMedia($notes['last_media_id'], ['checked_by' => $chat_id]);
                        break;
                    case $not_ok:
                        InstaDB::updateMedia($notes['last_media_id'], ['checked' => 1]);
                        InstaDB::updateMedia($notes['last_media_id'], ['checked_by' => $chat_id]);
                        InstaDB::insertPublished($notes['channel'], $notes['last_media_id'], null, null, null);
                        break;

                    case 'stop':
                        $data['reply_markup'] = Keyboard::remove(['selective' => true]);
                        $data['text'] = 'Done. Congradulations.';
                        $a = Request::sendMessage($data);
                        $this->conversation->stop();
                        return $a;

                    case 'check 50 in a row':
                        $notes['state'] = 3;
                        $this->conversation->update();
                        $keyboard = new Keyboard(['keyboard' => [['finished']], 'resize_keyboard' => true, 'one_time_keyboard' => true, 'selective' => true,]);
                        self::sendAllPosts($notes, $keyboard, $data);
                        $data['text']         = 'please only forward inappropriapte posts to me!!'
                                                .PHP_EOL .'I will remove it.';
                        $data['reply_markup'] = $keyboard;
                        $result  = Request::sendMessage($data);
                        break;

                    default:
                        break;
                }
                if ($notes['state'] !== 3){
                    $notes = self::sendPost($notes, $keyboard, $data);
                    $this->conversation->update();
                }
            }
            if ($notes['state'] !== 3){
                break;
            }
        case 3:
            if ($type !== 'photo') {
                $notes['state'] = 3;
                $this->conversation->update();
                //
                if (trim($message->getText(true)) === 'finished'){
                    $data['reply_markup'] = Keyboard::remove(['selective' => true]);
                    $data['text'] = 'Done. Congradulations.';
                    $a = Request::sendMessage($data);
                    $this->conversation->stop();
                    return $a;
                } else{
                    $data['text']         = 'please only forward inappropriapte posts to me!!';
                    $data['reply_markup'] = $keyboard;
                    $result  = Request::sendMessage($data);
                }
                break;
            } else {
                // echo "<pre>", print_r($message), "</pre>";
                // echo "<pre>", $message->getCaption(), "</pre>";
                try {
                    if($message->getForwardFrom()->getUsername() === 'instagfaBot'){
                        InstaDB::updateMedia($message->getCaption(), ['checked' => 1]);
                        InstaDB::insertPublished($notes['channel'], $message->getCaption(), null, null, null);
                        $data['text']         = 'media ' .$message->getCaption() .'is set to inappropriate in this channel.';
                        $data['reply_markup'] = $keyboard;
                        $result  = Request::sendMessage($data);
                    } else {
                        $data['text']         = 'you doesn\'t forward my posts. ay sheitoon!!';
                        $data['reply_markup'] = $keyboard;
                        $result  = Request::sendMessage($data);
                    }
                } catch (Exception $e){
                    $data['text']         = 'you doesn\'t forward my posts. ay sheitoon!!';
                    $data['reply_markup'] = $keyboard;
                    $result  = Request::sendMessage($data);
                }

            }
            break;
      }
      return $result;

	}


    protected function sendPost($notes, $keyboard, $data)
    {
        $options = MyFun::selectionOptionsCreator($notes['channel']);
        $options['limit_rows'] = 1;
        $options['checked'] = 0;
        //$options['is_checked'] = 0;
        //
        $channels = InstaDB::selectChannels();
        $channel = $channels[array_search($notes['channel'], array_column($channels, 'channel_id'))];
        $selection = ['videos' => false, 'images' => false, 'carousels' => false];
        $channel['videos'] > 0 &&  $selection['videos'] = true;
        $channel['images'] > 0 &&  $selection['images'] = true;
        $channel['carousels'] > 0 &&  $selection['carousels'] = true;
        do {
            $a = InstaDB::selectMedia($selection, $options, $date_from = strtotime("-" .$notes['date_from'] ." day"));
            if (empty($a)){
                $data['reply_markup'] = Keyboard::remove(['selective' => true]);
                $data['text'] = 'All posts are reviewed. Nothing to check.';
                $a = Request::sendMessage($data);
                $this->conversation->stop();
                return $notes;
            }
            // echo "<pre>\n", print_r($a), print_r($options), $notes['date_from'], "</pre>\n";
            $notes['last_media_id']    = $a[0]['media_id'];
            if ($notes['show_caption'] === 'Yes'){
                $data['text']         = $a[0]['caption'];
                $result = Request::sendMessage($data);
            }
            switch ($a[0]['type']) {
                case 'image':
                    $data['photo']        = $a[0]['img_standard_resolution'];
                    $data['caption']      = $a[0]['media_id'];
                    $data['reply_markup'] = $keyboard;
                    $result = Request::sendPhoto($data);
                    // echo "<pre>\n", "sdsd213", print_r($result), print_r($data), "</pre>\n";
                    break;
                case 'video':
                    $data['video']        = $a[0]['vid_standard_resolution'];
                    $data['caption']      = $a[0]['media_id'];
                    $data['reply_markup'] = $keyboard;
                    $result = Request::sendVideo($data);
                    // echo "<pre>\n", "sdsd213", print_r($result), print_r($data), "</pre>\n";
                    break;
                case 'carousel':
                    $type = 'photo';
                    break;
                default:
                    break;
            }
            if (!$result->isOK()){
                InstaDB::updateMedia($notes['last_media_id'], ['scanned' => 0]);
                sleep(1);
            }
        } while (!$result->isOK());
        return $notes;
    }




    protected function sendAllPosts($notes, $keyboard, $data)
    {
        $options = MyFun::selectionOptionsCreator($notes['channel']);
        $options['limit_rows'] = 50;
        $options['checked'] = 0;
        //$options['is_checked'] = 0;
        //
        $channels = InstaDB::selectChannels();
        $channel = $channels[array_search($notes['channel'], array_column($channels, 'channel_id'))];
        $selection = ['videos' => false, 'images' => false, 'carousels' => false];
        $channel['videos'] > 0 &&  $selection['videos'] = true;
        $channel['images'] > 0 &&  $selection['images'] = true;
        $channel['carousels'] > 0 &&  $selection['carousels'] = true;
        $a = InstaDB::selectMedia($selection, $options, $date_from = strtotime("-" .$notes['date_from'] ." day"));
        if (empty($a)){
            $data['reply_markup'] = Keyboard::remove(['selective' => true]);
            $data['text'] = 'All posts are reviewed. Nothing to check.';
            $a = Request::sendMessage($data);
            $this->conversation->stop();
            return $notes;
        }
        // echo "<pre>\n", print_r($a), print_r($options), $notes['date_from'], "</pre>\n";
        foreach ($a as $media) {
            if ($notes['show_caption'] === 'Yes'){
                $data['text']         = $media['caption'];
                $result = Request::sendMessage($data);
            }
            switch ($media['type']) {
                case 'image':
                    $data['photo']        = $media['img_standard_resolution'];
                    $data['caption']      = $media['media_id'];
                    $data['reply_markup'] = $keyboard;
                    $result = Request::sendPhoto($data);
                    //
                    InstaDB::updateMedia($media['media_id'], ['checked' => 1]);
                    // echo "<pre>\n", "sdsd213", print_r($result), print_r($data), "</pre>\n";
                    break;
                case 'video':
                    $data['video']        = $media['vid_standard_resolution'];
                    $data['caption']      = $media['media_id'];
                    $data['reply_markup'] = $keyboard;
                    $result = Request::sendVideo($data);
                    //
                    InstaDB::updateMedia($media['media_id'], ['checked' => 1]);
                    // echo "<pre>\n", "sdsd213", print_r($result), print_r($data), "</pre>\n";
                    break;
                case 'carousel':
                    $type = 'photo';
                    break;
                default:
                    break;
            }
            if (!$result->isOK()){
                InstaDB::updateMedia($media['media_id'], ['scanned' => 0]);
                sleep(1);
            }
        }

        return $notes;
    }


}
