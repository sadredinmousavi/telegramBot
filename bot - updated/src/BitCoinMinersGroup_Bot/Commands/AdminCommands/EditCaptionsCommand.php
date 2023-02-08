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



class EditCaptionsCommand extends AdminCommand
{
    protected $name = 'editcaptions';

	protected $description = 'Edit captions to send...';

	protected $usage = '/editcaptions';

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
    $in_keyboard = ($text === $ok || $text === $not_ok || $text === 'stop' || $text === 'edit' || $text === 'modify');
    $in_keyboard_1 = ($text === 'save' || $text === 'preview' || $text === 'go back');

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
            if (!$admin[0]['can_edit']){
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

                $data['text'] = 'unpublished medias will show up then you can prepare them to publish.'
                                .PHP_EOL .PHP_EOL .'Please select a channel from the keyboard: (only channels that you are admin of them will show)';
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

                $data['text'] = 'Do you want original captions to show? some captions might be too long!!'
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
            $keyboard = new Keyboard(['keyboard' => [['edit', 'modify']], 'resize_keyboard' => true, 'one_time_keyboard' => true, 'selective' => true,]);
            if (!$in_keyboard || $notes['last_message_id'] === $message->getMessageId()) {
                $notes['state'] = 2;
                $this->conversation->update();
                if ($notes['last_message_id'] === $message->getMessageId()){
                    // echo "ok";
                    $channels = InstaDB::selectChannels();
                    $channel = $channels[array_search($notes['channel'], array_column($channels, 'channel_id'))];
                    $selection = ['videos' => false, 'images' => false, 'carousels' => false];
                    $channel['videos'] > 0 &&  $selection['videos'] = true;
                    $channel['images'] > 0 &&  $selection['images'] = true;
                    $channel['carousels'] > 0 &&  $selection['carousels'] = true;
                    //
                    //
                    $options = MyFun::selectionOptionsCreator($notes['channel']);
                    // unset($options['limit_rows']);
                    $data['text'] = 'Channel ' .$channel['user_name'] .' :' .PHP_EOL;
                    //
                    $options['has_edited_caption'] = 0;
                    $a = InstaDB::selectMedia($selection, $options, $date_from = strtotime("-" .$notes['date_from'] ." day"));
                    $data['text'] .= count($a) .' unpublished medias that are not edited.' .PHP_EOL;
                    $data['text'] .= 'You can edit them by selecting \'edit\'' .PHP_EOL .PHP_EOL;
                    //
                    $options['has_edited_caption'] = 1;
                    $a = InstaDB::selectMedia($selection, $options, $date_from = strtotime("-" .$notes['date_from'] ." day"));
                    $data['text'] .= count($a) .' unpublished medias that are edited earlier.' .PHP_EOL;
                    $data['text'] .= 'You can modify them by selecting \'modify\'' .PHP_EOL .PHP_EOL;
                    //
                    $data['text'] .= 'Or you can return using /cancel';
                    $data['reply_markup'] = $keyboard;
                    $result  = Request::sendMessage($data);
                } else {
                    $data['text']         = 'please only select from the \'Keyborad\'.';
                    $data['reply_markup'] = $keyboard;
                    $result  = Request::sendMessage($data);
                }
                break;
            }
            $notes['edit_modify'] = $text;
            $notes['last_message_id'] = $message->getMessageId();
            if ($text === 'modify'){
                $data['text'] .= 'Felan modify nadarim. edit ro bezan. kh kh kh.';
                $data['reply_markup'] = $keyboard;
                $result  = Request::sendMessage($data);
                break;
            }
        case 3:
            $keyboard = new Keyboard(['keyboard' => [['show next serie'], ['finish']], 'resize_keyboard' => true, 'one_time_keyboard' => true, 'selective' => true,]);
            if ($type !== 'photo') {
                $notes['state'] = 3;
                $this->conversation->update();
                //
                if ($notes['last_message_id'] === $message->getMessageId()){
                    $notes['last_shown'] = 0;
                    $this->conversation->update();
                    //
                    $data['text']         = 'If you want to select a post to edit its caption please only forward posts that i sent to me again!!' .PHP_EOL .PHP_EOL .'Or in case you want to see next serie select \'show next serie\' from keyboard.';
                    $data['reply_markup'] = $keyboard;
                    $result  = Request::sendMessage($data);
                } elseif (trim($message->getText(true)) === 'show next serie'){
                    $notes = self::sendAllPosts($notes, $keyboard, $data);
                    $this->conversation->update();
                } elseif (trim($message->getText(true)) === 'finish'){
                    $data['reply_markup'] = Keyboard::remove(['selective' => true]);
                    $data['text'] = 'Done. Congradulations.';
                    $a = Request::sendMessage($data);
                    $this->conversation->stop();
                    return $a;
                } else {
                    $data['text']         = 'please only forward posts that i sent to me again!!' .PHP_EOL .'Or in case you want to see next serie select \'show next serie\' from keyboard.';
                    $data['reply_markup'] = $keyboard;
                    $result  = Request::sendMessage($data);
                }
                break;
            } else {
                // echo "<pre>", print_r($message), "</pre>";
                // echo "<pre>", $message->getCaption(), "</pre>";
                try {
                    if($message->getForwardFrom()->getUsername() === 'instagfaBot'){
                        $notes['state'] = 4;
                        $notes['last_media_id'] = $message->getCaption();
                        $this->conversation->update();
                        //
                        $data['text']         = 'you select media with \'id\' = ' .$message->getCaption();
                        $data['reply_markup'] = Keyboard::remove(['selective' => true]);
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
            $notes['last_message_id'] = $message->getMessageId();

        case 4:
            $keyboard = new Keyboard(['keyboard' => [['save', 'preview'], ['go back']], 'resize_keyboard' => true, 'one_time_keyboard' => true, 'selective' => true,]);
            if ($notes['last_message_id'] === $message->getMessageId()) {
                $notes['state'] = 4;
                $this->conversation->update();
                //
                $data['text']         = 'tozih';
                $data['reply_markup'] = $keyboard;
                $result  = Request::sendMessage($data);

            } elseif ($in_keyboard_1){
                switch ($text) {
                    case 'save':
                        if (isset($notes['edited_caption'])){
                            InstaDB::updateMedia($notes['last_media_id'], ['edited_caption' => $notes['edited_caption']]);
                            InstaDB::updateMedia($notes['last_media_id'], ['has_edited_caption' => 1]);
                            InstaDB::updateMedia($notes['last_media_id'], ['edited_by' => $chat_id]);

                            $notes['state'] = 3;
                            unset($notes['last_media_id']);
                            unset($notes['edited_caption']);
                            $this->conversation->update();

                            $data['text']         = 'Done.';
                            $data['reply_markup'] = Keyboard::remove(['selective' => true]);
                            $result  = Request::sendMessage($data);

                            return $this->getTelegram()->executeCommand("editcaptions");
                        } else {
                            $data['text']         = 'You have not set the caption yet. To set a caption just type and send.';
                            $data['reply_markup'] = $Keyboard;
                            $result  = Request::sendMessage($data);
                        }
                        break;

                    case 'preview':
                        $notes = self::sendPost($notes, $keyboard, $data);
                        $this->conversation->update();
                        break;

                    case 'go back':
                        $notes['state'] = 3;
                        $this->conversation->update();
                        return $this->getTelegram()->executeCommand("editcaptions");
                        break;

                    default:
                        # code...
                        break;
                }
            } elseif ($type === 'message'){
                $notes['edited_caption'] = $text;
                $this->conversation->update();
                //
                $data['text']         = 'your new caption set.' .PHP_EOL .'It is not saved yet!!!';
                $data['reply_markup'] = $keyboard;
                $result  = Request::sendMessage($data);

            } elseif ($type === 'photo'){
                $data['text']         = 'please first \'save\' this or \'go back\' to select another media.';
                $data['reply_markup'] = $keyboard;
                $result  = Request::sendMessage($data);
            } else {
                $data['text']         = 'wrong answer. please follow the instructions.';
                $data['reply_markup'] = $keyboard;
                $result  = Request::sendMessage($data);
            }
      }
      return $result;

	}


    protected function sendPost($notes, $keyboard, $data)
    {
        //
        $channels = InstaDB::selectChannels();
        $channel = $channels[array_search($notes['channel'], array_column($channels, 'channel_id'))];
        $aa = InstaDB::selectMedia($selection, ['media_id' => $notes['last_media_id']], $date_from = strtotime("-" .$notes['date_from'] ." day"));
        $a = $aa[0];
        //
        //

        if (empty($a)){
            $data['reply_markup'] = Keyboard::remove(['selective' => true]);
            $data['text'] = 'Can\'t process the image. may telegram problem. please forward image to admin.';
            $result = Request::sendMessage($data);
            $this->conversation->stop();
            return $notes;
        }
        // echo "<pre>\n", print_r($a), print_r($options), $notes['date_from'], "</pre>\n";
        // $notes['last_media_id']    = $a['media_id'];
        if ($notes['show_caption'] === 'Yes'){
            $data['text']         = $a['caption'];
            $result = Request::sendMessage($data);
        }
        switch ($a['type']) {
            case 'image':
                $img_url = MyFun::edit_image_method_3($a, $channel['user_name'], true);
                if ($img_url !== false){
                    $a['img_standard_resolution'] = $img_url;
                }
                $data['photo']        = $a['img_standard_resolution'];
                // $data['caption']      = $a['media_id'];
                $data['caption']      = $notes['edited_caption'];
                $data['reply_markup'] = $keyboard;
                $result = Request::sendPhoto($data);
                // echo "<pre>\n", "sdsd213", print_r($result), print_r($data), "</pre>\n";
                break;
            case 'video':
                $data['video']        = $a['vid_standard_resolution'];
                // $data['caption']      = $a['media_id'];
                $data['caption']      = $notes['edited_caption'];
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
        if ($result->isOK()){
            if ($notes['edit_modify'] === 'modify'){
                $data['text']                = $a['edited_caption'];
                $data['reply_to_message_id'] = $result->getResult()->getmessage_id();
                $result = Request::sendMessage($data);
            }
        } else {
            InstaDB::updateMedia($notes['last_media_id'], ['scanned' => 0]);
            sleep(1);
        }

        return $notes;
    }




    protected function sendAllPosts($notes, $keyboard, $data)
    {
        $options = MyFun::selectionOptionsCreator($notes['channel']);
        $options['limit_rows'] = 10 + $notes['last_shown'];
        if ($notes['edit_modify'] === 'edit'){
            $options['has_edited_caption'] = 0;
        } else {
            $options['has_edited_caption'] = 1;
        }
        $options['sort_by'] = 'created_time';
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
            $data['text'] = 'There is no medias. Nothing to check.';
            $a = Request::sendMessage($data);
            $this->conversation->stop();
            return $notes;
        }
        // echo "<pre>\n", print_r($a), print_r($options), $notes['date_from'], "</pre>\n";
        for ($i=0; $i < $notes['last_shown']; $i++){
            unset($a[$i]);
        }

        foreach ($a as $media) {
            if ($notes['show_caption'] === 'Yes'){
                $data['text']         = $media['caption'];
                $result = Request::sendMessage($data);
            }
            switch ($media['type']) {
                case 'image':
                    $img_url = MyFun::edit_image_method_3($media, $channel['user_name'], true);
                    if ($img_url !== false){
                        $media['img_standard_resolution'] = $img_url;
                    }
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
            if ($result->isOK()){
                if ($notes['edit_modify'] === 'modify'){
                    $data['text']                = $a['edited_caption'];
                    $data['reply_to_message_id'] = $result->getResult()->getmessage_id();
                    $result = Request::sendMessage($data);
                }
            } else {
                InstaDB::updateMedia($notes['last_media_id'], ['scanned' => 0]);
                sleep(1);
            }
            $notes['last_shown'] = $notes['last_shown'] + 1;
        }

        return $notes;
    }


}
