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



class UserstochannelrelationCommand extends AdminCommand
{
    protected $name = 'userstochannelrelation';

	protected $description = 'Define which users belongs to which channels - full CRUD';

	protected $usage = '/Userstochannelrelation';

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
            if (!$admin[0]['can_user']){
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

                $data['text'] = 'first a List of users assigned to each channels will show up. then you can add or delete or update status od each one.'
                                .PHP_EOL .PHP_EOL .'Please select a channel from the keyboard:';
                $result       = Request::sendMessage($data);
                break;
            }
            $user_names = array_column($channels, 'user_name');
            $user_ids = array_column($channels, 'channel_id');
            $notes['channel']         = $user_ids[array_search ($text, $user_names)];
            $notes['last_message_id'] = $message->getMessageId();
            //
            //
            $list = InstaDB::selectUsersToChannels($notes['channel']);
            $text = count($list) .' users are assigned to channel (' .$user_names[array_search ($text, $user_names)] .') :' .PHP_EOL;
            foreach ($list as $user) {
                $text .= PHP_EOL .$user['username'];
            }
            $data['text'] = $text;
            $result       = Request::sendMessage($data);
            //
            // no break
        case 1:
            if (!$text_yes_or_no || $notes['last_message_id'] === $message->getMessageId()) {
                $notes['state'] = 1;
                $this->conversation->update();

                // Execute this just with object that allow caption
                $data['reply_markup'] = new Keyboard(
                    [
                        'keyboard'          => [['add', 'remove']],
                        'resize_keyboard'   => true,
                        'one_time_keyboard' => true,
                        'selective'         => true,
                    ]
                );

                $data['text'] = 'Now select the action. or you can exit by /cancel'
                                .PHP_EOL .PHP_EOL .'Add a new entry or update is the same --> just press \'add\''
                                .PHP_EOL .PHP_EOL .'to remove a group of users --> just press \'remove\'';
                if (!$text_yes_or_no && $notes['last_message_id'] !== $message->getMessageId()) {
                    $data['text'] .= PHP_EOL . 'Type Yes or No';
                }
                $result = Request::sendMessage($data);
                break;
            }
            $notes['add_or_remove']     = $text;//($text === 'Yes');
            $notes['last_message_id'] = $message->getMessageId();
        // no break
        case 2:
            if (($type === 'message' && $text === '') || $notes['last_message_id'] === $message->getMessageId()) {
                $notes['state'] = 2;
                $this->conversation->update();

                $data['reply_markup'] = Keyboard::remove(['selective' => true]);
                $data['text']         = 'Input style is as follows. \' - n\' format is just for \'add \' action.'
                                        .PHP_EOL .PHP_EOL .'please note that with \' - n \' format at end of the username yppu specify that the user \'need check\' before sending each post'
                                        .PHP_EOL .PHP_EOL .'davood'
                                        .PHP_EOL .'dadollah - n'
                                        .PHP_EOL .'ramsey';
                $result               = Request::sendMessage($data);
                break;
            }
            $notes['last_message_id'] = $message->getMessageId();
            $notes['message']         = $message->getText(true);;
            $notes['message_type']    = $type;
        // no break
        case 3:
            $notes['state'] = 3;
            $this->conversation->update();
            $data['reply_markup'] = Keyboard::remove(['selective' => true]);
            //
            $usernames = explode("\n", $notes['message']);
            //
            //
            $response_text_h = 'proccessing ...'
                               .PHP_EOL .'you entered ' .count($usernames) .' usernames.'
                               .PHP_EOL;
            $response_text_m = '';
            $response_text_f = PHP_EOL .PHP_EOL .PHP_EOL .'Done. you can check by calling this command agian. /userstochannelrelation';


            $data = [];
            $data['chat_id'] = $chat_id;
            $data['text'] = $response_text_h;
            $message_data = Request::sendMessage($data);

            $data = [];
            $data['chat_id'] = $chat_id;
            $data['message_id'] = $message_data->getResult()->getMessageId();

            $input_to_db = [];
            foreach ($usernames as $username) {
                if ($a = strpos($username, '-')){
                    $username = substr($username, 0, $a-1);
                    $value = 1;
                } else {
                    $value = 0;
                }
                $username = trim(trim($username, "@\t\n-"));
                if(strlen($username) < 1){
                    continue;
                }
                if ($notes['add_or_remove'] == 'add') {
                    $input_to_db[$username] = $value;
                } else {
                    $input_to_db [] = $username;
                }
            }
            echo "<pre>";
            print_r($input_to_db);
            echo "</pre>";
            if ($notes['add_or_remove'] == 'add') {
                $res = InstaDB::insertUsersToChannels($notes['channel'], $input_to_db);
                $i = 0;
                foreach ($input_to_db as $key => $value) {
                    if ($res[strval($i)]){
                        $res_t = 'ok';
                    } else {
                        $res_t = 'not ok';
                    }
                    $response_text_m .= PHP_EOL .$key .' --> ' .$res_t;
                    $i = $i + 1;
                }
            } else {
                $res = InstaDB::deleteUsersToChannels($notes['channel'], $input_to_db);
                echo "<pre>";
                print_r($res);
                echo json_encode($res);
                echo "</pre>";
                $i = 0;
                foreach ($input_to_db as $key) {
                    if ($res[strval($i)]){
                        $res_t = 'ok';
                    } else {
                        $res_t = 'not ok';
                    }
                    $response_text_m .= PHP_EOL .$key .' --> ' .$res_t;
                    $i = $i + 1;
                }
            }

            $data['text'] = $response_text_h .$response_text_m .$response_text_f;
            $result = Request::editMessageText($data);

            $data['text'] = 'Done. Congradulations.';
            $a = Request::sendMessage($data);

            $this->conversation->stop();
      }
      return $result;

	}


}
