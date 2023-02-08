<?php


namespace Longman\TelegramBot\Commands\UserCommands;


use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\DB;
use Longman\TelegramBot\Entities\Chat;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\ConversationDB;
use Longman\TelegramBot\Conversationdetails;



use Longman\TelegramBot\MyFuns AS MyFun;
use Longman\TelegramBot\MyDB;



class NewpostCommand extends UserCommand
{
    protected $name = 'newpost';

	protected $description = 'create new custom post';

	protected $usage = '/newpost';

    /**
     * @var string
     */
    protected $version = '1.1.0';

    protected $conversation;

    public function execute()
    {
    $message = $this->getMessage();
	$chat_id = $message->getChat()->getId();
    $user_id = $message->getFrom()->getId();
    $type    = $message->getType();
    $text    = trim($message->getText(true));

    $available_satoshi = 5000;
    $last_satoshi_date = null;
    $satoshi = 0;
    $channel = '@BitCoinMinersGroup';
    $channel_upload_id = '-1001337683673';
    $channel_upload = 'zasxwq';

    in_array($type, ['command', 'text'], true) && $type = 'message';

    $selection = ['users' => true, 'groups' => false, 'super_groups' => false];
    $results = MyDB::selectChatsN('normal', $selection, ['chat_id' => $chat_id]);
    // if (!empty($results)) {
    //     $result = reset($results);
    //     if (is_array($result)) {
    //         $lang              = $result['lang'];
    //         $last_satoshi_date = $result['last_satoshi_date'];
    //         $satoshi           = $result['satoshi'];
    //     }
    // }
    // $strings = MyFun::getStrings($lang);
    $strings = MyFun::getStrings();

    $in_keyboard_1 = ($text === $strings['newpost_1_nocaption']);
    $in_keyboard_2 = ($text === $strings['newpost_2_butt1'] || $text === $strings['newpost_2_butt2'] || $text === $strings['newpost_2_butt3']);
    $in_keyboard_3 = ($text === $strings['newpost_2_butt2']);
    $in_keyboard_4 = ($text === $strings['newpost_3_butt1'] || $text === $strings['newpost_3_butt2']);
    $in_keyboard_5 = ($text === $strings['newpost_7_butt1'] || $text === $strings['newpost_7_butt2'] || $text === $strings['newpost_7_butt3']);


    $data = [
        'chat_id' => $chat_id,
    ];
    // Conversation
    $this->conversation = new Conversationdetails($user_id, $chat_id, $this->getName());

    $notes = &$this->conversation->notes;
    !is_array($notes) && $notes = [];

    if (isset($notes['state'])) {
        $state = $notes['state'];
    } else {
        $state                    = 0;
        $notes['last_message_id'] = $message->getMessageId();
    }


    switch ($state) {
        default:
        case 0:
            if ($type === 'message') {
                $notes['state'] = 0;
                $this->conversation->update();

                $data['reply_markup'] = Keyboard::remove(['selective' => true]);

                $data['text'] = $strings['newpost_0_intro'];
                if ($notes['last_message_id'] !== $message->getMessageId()) {
                    $data['text'] = $strings['newpost_0_skey'];
                }
                $result = Request::sendMessage($data);
                break;
            }
            $notes['type'] = $type;
            $notes['last_message_id'] = $message->getMessageId();
            $notes['state']           = 1;
            //
            if (in_array($type, ['audio', 'document', 'photo', 'video', 'voice'], true)) {
    			$doc = call_user_func([$message, 'get' . ucfirst($type)]);
    			($type === 'photo') && $doc = $doc[0];
    			// $file = Request::getFile(['file_id' => $doc->getFileId()]);
    			// if ($file->isOk()) {
    			// 	Request::downloadFile($file->getResult());
    			// }
    		}
            //$notes[$type]           = $file;
            $notes[$type]           = $doc->getFileId();
            //
            $data =[];
            $data['chat_id'] = $channel_upload_id;
            //$data[$type] = $doc->getFileId();
            $data[$notes['type']] = $notes[$notes['type']];
            switch ($notes['type']) {
                case 'photo':
                    $result = Request::sendPhoto($data);
                    break;
                case 'video':
                    $result = Request::sendVideo($data);
                    break;
                default:

                    break;
            }
            if ($result->isOK()){
                $notes['link'] = 'T.me/' .$channel_upload .'/' .$result->getResult()->getmessage_id();
                $m_id = $result->getResult()->getmessage_id();
            } else {
                $data['chat_id'] = $chat_id;
                $data['text'] = $strings['newpost_0_error'];
                $result = Request::sendMessage($data);
                $this->getTelegram()->executeCommand("cancel");
            }
            $this->conversation->update();
            //
            // $data =[];
            // $data['chat_id'] = '@' .$channel_upload;
            // $data['message_id'] = $m_id;
            // $result = Request::deleteMessage($data);
            //

        // no break
        case 1:
            if ($type !== 'message' || $in_keyboard_3) {
                $notes['state'] = 1;
                $this->conversation->update();

                $data = [];
                $data['chat_id'] = $chat_id;
                $data['reply_markup'] = new Keyboard([
                        'keyboard'          => [[$strings['newpost_1_nocaption']]],
                        'resize_keyboard'   => true,
                        'one_time_keyboard' => true,
                        'selective'         => true,
                    ]);

                $data['text'] = sprintf($strings['newpost_1_intro'], $strings['newpost_1_nocaption']);
                if ($notes['last_message_id'] !== $message->getMessageId()) {
                    $data['text'] = sprintf($strings['newpost_1_skey'], $strings['newpost_1_nocaption']);
                }
                $result = Request::sendMessage($data);
                break;
            } elseif (!$in_keyboard_1) {
                $notes['caption'] = $text;
            }
            //
            $data = [];
            $data['chat_id'] = $chat_id;
            //
            $keyboard =[];
            $counter = 0;
            for ($i=1; $i <4 ; $i++) {
                if (isset($notes['keyboard' .$i])) {
                    $keyboard[]=$notes['keyboard' .$i];
                    $counter++;
                }
            }
            if ($counter>0){
                $data['reply_markup'] =new InlineKeyboard(...$keyboard);
            }
            //
            if (strlen($notes['caption']) > 250){
                $data['text'] = '<a href="' .$notes['link'] .'">&#8205;</a>' .PHP_EOL .$notes['caption'];
                $data['disable_web_page_preview'] = false;
                $data['parse_mode'] = 'HTML';
                $result = Request::sendMessage($data);
            } else {
                $data['caption'] = $notes['caption'];
                $data[$notes['type']] = $notes[$notes['type']];
                switch ($notes['type']) {
                    case 'photo':
                        $result = Request::sendPhoto($data);
                        break;
                    case 'video':
                        $result = Request::sendVideo($data);
                        break;
                    default:

                        break;
                }
            }
            //
            $notes['last_message_id'] = $message->getMessageId();
            $notes['state']           = 2;
            $this->conversation->update();

        case 2:
            if (!$in_keyboard_2) {
                $notes['state'] = 2;
                $this->conversation->update();

                $data = [];
                $data['chat_id'] = $chat_id;
                $data['reply_markup'] = new Keyboard([
                        'keyboard'          => [[$strings['newpost_2_butt1']], [$strings['newpost_2_butt2']], [$strings['newpost_2_butt3']]],
                        'resize_keyboard'   => true,
                        'one_time_keyboard' => true,
                        'selective'         => true,
                    ]);

                $data['text'] = sprintf($strings['newpost_2_intro'], $strings['newpost_2_butt3']);
                if ($notes['last_message_id'] !== $message->getMessageId()) {
                    $data['text'] = sprintf($strings['newpost_2_skey'], $strings['newpost_2_butt3']);
                }
                $result = Request::sendMessage($data);
                break;
            } else {
                switch ($text) {
                    case $strings['newpost_2_butt3']:
                        $notes['last_message_id'] = $message->getMessageId();
                        $notes['state']           = 8;
                        $this->conversation->update();
                        return $this->getTelegram()->executeCommand("newpost");
                        break;

                    case $strings['newpost_2_butt2']:
                        $notes['last_message_id'] = $message->getMessageId();
                        $notes['state']           = 1;
                        $this->conversation->update();
                        return $this->getTelegram()->executeCommand("newpost");
                        break;

                    case $strings['newpost_2_butt1']:
                        $data['reply_markup'] = Keyboard::remove(['selective' => true]);
                        $data['text'] = $strings['newpost_2_concu'];
                        $result = Request::sendMessage($data);
                        break;

                    default:
                        # code...
                        break;
                }
            }
            $notes['last_message_id'] = $message->getMessageId();
            $notes['state']           = 3;
            $this->conversation->update();

        case 3:
            $line_number = $notes['state'] - 2;
            if ($type !== 'message' || $notes['last_message_id'] === $message->getMessageId()) {
                $notes['state'] = 3;
                $this->conversation->update();

                $data = [];
                $data['chat_id'] = $chat_id;
                $data['reply_markup'] = new Keyboard([
                        'keyboard'          => [[$strings['newpost_3_butt1']], [$strings['newpost_3_butt2']]],
                        'resize_keyboard'   => true,
                        'one_time_keyboard' => true,
                        'selective'         => true,
                    ]);

                $data['text'] = sprintf($strings['newpost_3_intro'], $line_number);
                if ($notes['last_message_id'] !== $message->getMessageId()) {
                    $data['text'] = sprintf($strings['newpost_3_skey1'], $line_number);
                }
                $result = Request::sendMessage($data);
                break;
            } elseif ($in_keyboard_4) {
                switch ($text) {
                    case $text === $strings['newpost_3_butt1']:
                        $notes['last_message_id'] = $message->getMessageId();
                        $notes['state']           = 7;
                        $this->conversation->update();
                        return $this->getTelegram()->executeCommand("newpost");
                        break;

                    case $text === $strings['newpost_3_butt2']:
                        $notes['last_message_id'] = $message->getMessageId();
                        $notes['state']           = 3;
                        $this->conversation->update();
                        return $this->getTelegram()->executeCommand("newpost");
                        break;

                    default:
                        # code...
                        break;
                }
            } elseif ($type === 'message') {
                $keys = explode("\n", $text);
                $inline_keys = [];
                for ($i=0; $i <2 ; $i++) {
                    if (isset($keys[2*$i+1])) {
                        $inline_keys []= new InlineKeyboardButton(['text' => $keys[2*$i], 'url' => $keys[2*$i+1]]);
                    }
                }
                $keyboard =[];
                $keyboard[]=$inline_keys;
                //$keyboard[]=$inline_keys;

                $data = [
                    'chat_id'      => $chat_id,
                    'text'         => sprintf($strings['newpost_3_preview'], $line_number),
                    'reply_markup' => new InlineKeyboard(...$keyboard),
                ];


                $result = Request::sendMessage($data);

                if(!$result->isOK()){
                    $data = [
                        'chat_id'      => $chat_id,
                        'text'         => $strings['newpost_3_error'],
                    ];
                    $result = Request::sendMessage($data);
                }
            }
            $notes['has_keyboard']           = 1;
            $notes['keyboard' .$line_number] = $inline_keys;
            $notes['last_message_id']        = $message->getMessageId();
            $notes['state']                  = 4;
            $this->conversation->update();

        case 4:
            $line_number = $notes['state'] - 2;
            if ($type !== 'message' || $notes['last_message_id'] === $message->getMessageId()) {
                $notes['state'] = 4;
                $this->conversation->update();

                $data = [];
                $data['chat_id'] = $chat_id;
                $data['reply_markup'] = new Keyboard([
                        'keyboard'          => [[$strings['newpost_3_butt1']], [$strings['newpost_3_butt2']]],
                        'resize_keyboard'   => true,
                        'one_time_keyboard' => true,
                        'selective'         => true,
                    ]);

                $data['text'] = sprintf($strings['newpost_3_intro'], $line_number);
                if ($notes['last_message_id'] !== $message->getMessageId()) {
                    $data['text'] = sprintf($strings['newpost_3_skey1'], $line_number);
                }
                $result = Request::sendMessage($data);
                break;
            } elseif ($in_keyboard_4) {
                switch ($text) {
                    case $text === $strings['newpost_3_butt1']:
                        $notes['last_message_id'] = $message->getMessageId();
                        $notes['state']           = 7;
                        $this->conversation->update();
                        return $this->getTelegram()->executeCommand("newpost");
                        break;

                    case $text === $strings['newpost_3_butt2']:
                        $notes['last_message_id'] = $message->getMessageId();
                        $notes['state']           = 3;
                        $this->conversation->update();
                        return $this->getTelegram()->executeCommand("newpost");
                        break;

                    default:
                        # code...
                        break;
                }
            } elseif ($type === 'message') {
                $keys = explode("\n", $text);
                $inline_keys = [];
                for ($i=0; $i <2 ; $i++) {
                    if (isset($keys[2*$i+1])) {
                        $inline_keys []= new InlineKeyboardButton(['text' => $keys[2*$i], 'url' => $keys[2*$i+1]]);
                    }
                }
                $keyboard =[];
                $keyboard[]=$inline_keys;
                //$keyboard[]=$inline_keys;

                $data = [
                    'chat_id'      => $chat_id,
                    'text'         => sprintf($strings['newpost_3_preview'], $line_number),
                    'reply_markup' => new InlineKeyboard(...$keyboard),
                ];


                $result = Request::sendMessage($data);

                if(!$result->isOK()){
                    $data = [
                        'chat_id'      => $chat_id,
                        'text'         => $strings['newpost_3_error'],
                    ];
                    $result = Request::sendMessage($data);
                }
            }
            $notes['has_keyboard']           = 1;
            $notes['keyboard' .$line_number] = $inline_keys;
            $notes['last_message_id']        = $message->getMessageId();
            $notes['state']                  = 5;
            $this->conversation->update();

        case 5:
            $line_number = $notes['state'] - 2;
            if ($type !== 'message' || $notes['last_message_id'] === $message->getMessageId()) {
                $notes['state'] = 5;
                $this->conversation->update();

                $data = [];
                $data['chat_id'] = $chat_id;
                $data['reply_markup'] = new Keyboard([
                        'keyboard'          => [[$strings['newpost_3_butt1']], [$strings['newpost_3_butt2']]],
                        'resize_keyboard'   => true,
                        'one_time_keyboard' => true,
                        'selective'         => true,
                    ]);

                $data['text'] = sprintf($strings['newpost_3_intro'], $line_number);
                if ($notes['last_message_id'] !== $message->getMessageId()) {
                    $data['text'] = sprintf($strings['newpost_3_skey1'], $line_number);
                }
                $result = Request::sendMessage($data);
                break;
            } elseif ($in_keyboard_4) {
                switch ($text) {
                    case $text === $strings['newpost_3_butt1']:
                        $notes['last_message_id'] = $message->getMessageId();
                        $notes['state']           = 7;
                        $this->conversation->update();
                        return $this->getTelegram()->executeCommand("newpost");
                        break;

                    case $text === $strings['newpost_3_butt2']:
                        $notes['last_message_id'] = $message->getMessageId();
                        $notes['state']           = 3;
                        $this->conversation->update();
                        return $this->getTelegram()->executeCommand("newpost");
                        break;

                    default:
                        # code...
                        break;
                }
            } elseif ($type === 'message') {
                $keys = explode("\n", $text);
                $inline_keys = [];
                for ($i=0; $i <2 ; $i++) {
                    if (isset($keys[2*$i+1])) {
                        $inline_keys []= new InlineKeyboardButton(['text' => $keys[2*$i], 'url' => $keys[2*$i+1]]);
                    }
                }
                $keyboard =[];
                $keyboard[]=$inline_keys;
                //$keyboard[]=$inline_keys;

                $data = [
                    'chat_id'      => $chat_id,
                    'text'         => sprintf($strings['newpost_3_preview'], $line_number),
                    'reply_markup' => new InlineKeyboard(...$keyboard),
                ];


                $result = Request::sendMessage($data);


                if(!$result->isOK()){
                    $data = [
                        'chat_id'      => $chat_id,
                        'text'         => $strings['newpost_3_error'],
                    ];
                    $result = Request::sendMessage($data);
                }
            }
            $notes['has_keyboard']           = 1;
            $notes['keyboard' .$line_number] = $inline_keys;
            $notes['last_message_id']        = $message->getMessageId();
            $notes['state']                  = 6;
            $this->conversation->update();

        case 6:
            $line_number = $notes['state'] - 2;
            if ($type !== 'message' || $notes['last_message_id'] === $message->getMessageId()) {
                $notes['state'] = 6;
                $this->conversation->update();

                $data = [];
                $data['chat_id'] = $chat_id;
                $data['reply_markup'] = new Keyboard([
                        'keyboard'          => [[$strings['newpost_3_butt1']], [$strings['newpost_3_butt2']]],
                        'resize_keyboard'   => true,
                        'one_time_keyboard' => true,
                        'selective'         => true,
                    ]);

                $data['text'] = sprintf($strings['newpost_3_intro'], $line_number);
                if ($notes['last_message_id'] !== $message->getMessageId()) {
                    $data['text'] = sprintf($strings['newpost_3_skey1'], $line_number);
                }
                $result = Request::sendMessage($data);
                break;
            } elseif ($in_keyboard_4) {
                switch ($text) {
                    case $text === $strings['newpost_3_butt1']:
                        $notes['last_message_id'] = $message->getMessageId();
                        $notes['state']           = 7;
                        $this->conversation->update();
                        return $this->getTelegram()->executeCommand("newpost");
                        break;

                    case $text === $strings['newpost_3_butt2']:
                        $notes['last_message_id'] = $message->getMessageId();
                        $notes['state']           = 3;
                        $this->conversation->update();
                        return $this->getTelegram()->executeCommand("newpost");
                        break;

                    default:
                        # code...
                        break;
                }
            } elseif ($type === 'message') {
                $keys = explode("\n", $text);
                $inline_keys = [];
                for ($i=0; $i <2 ; $i++) {
                    if (isset($keys[2*$i+1])) {
                        $inline_keys []= new InlineKeyboardButton(['text' => $keys[2*$i], 'url' => $keys[2*$i+1]]);
                    }
                }
                $keyboard =[];
                $keyboard[]=$inline_keys;
                //$keyboard[]=$inline_keys;

                $data = [
                    'chat_id'      => $chat_id,
                    'text'         => sprintf($strings['newpost_3_preview'], $line_number),
                    'reply_markup' => new InlineKeyboard(...$keyboard),
                ];

                $result = Request::sendMessage($data);

                if(!$result->isOK()){
                    $data = [
                        'chat_id'      => $chat_id,
                        'text'         => $strings['newpost_3_error'],
                    ];
                    $result = Request::sendMessage($data);
                }
            }
            $notes['has_keyboard']           = 1;
            $notes['keyboard' .$line_number] = $inline_keys;
            $notes['last_message_id']        = $message->getMessageId();
            $notes['state']                  = 7;
            $this->conversation->update();
        case 7:
            if (!$in_keyboard_5) {
                $notes['state'] = 7;
                $this->conversation->update();


                if ($notes['last_message_id'] === $message->getMessageId()) {
                    $data = [];
                    $data['chat_id'] = $chat_id;
                    //
                    $keyboard =[];
                    $counter = 0;
                    for ($i=1; $i <4 ; $i++) {
                        if (isset($notes['keyboard' .$i])) {
                            $keyboard[]=$notes['keyboard' .$i];
                            $counter++;
                        }
                    }
                    if ($counter>0){
                        $data['reply_markup'] =new InlineKeyboard(...$keyboard);
                    }
                    //
                    if (strlen($notes['caption']) > 250){
                        $data['text'] = '<a href="' .$notes['link'] .'">&#8205;</a>' .PHP_EOL .$notes['caption'];
                        $data['disable_web_page_preview'] = false;
                        $data['parse_mode'] = 'HTML';
                        $result = Request::sendMessage($data);
                    } else {
                        $data['caption'] = $notes['caption'];
                        $data[$notes['type']] = $notes[$notes['type']];
                        switch ($notes['type']) {
                            case 'photo':
                                $result = Request::sendPhoto($data);
                                break;
                            case 'video':
                                $result = Request::sendVideo($data);
                                break;
                            default:

                                break;
                        }
                    }
                }

                $data = [];
                $data['chat_id'] = $chat_id;
                $data['reply_markup'] = new Keyboard([
                        'keyboard'          => [[$strings['newpost_7_butt3']], [$strings['newpost_7_butt2']], [$strings['newpost_7_butt1']]],
                        'resize_keyboard'   => true,
                        'one_time_keyboard' => true,
                        'selective'         => true,
                    ]);

                $data['text'] = sprintf($strings['newpost_7_intro'], $strings['newpost_7_butt3']);
                if ($notes['last_message_id'] !== $message->getMessageId()) {
                    $data['text'] = sprintf($strings['newpost_7_skey1'], $strings['newpost_7_butt3']);
                }
                $result = Request::sendMessage($data);
                break;
            } else {
                switch ($text) {
                    case $text === $strings['newpost_7_butt1']:
                        $notes['last_message_id'] = $message->getMessageId();
                        $notes['state']           = 3;
                        $this->conversation->update();
                        return $this->getTelegram()->executeCommand("newpost");
                        break;

                    case $text === $strings['newpost_7_butt2']:
                        $notes['last_message_id'] = $message->getMessageId();
                        $notes['state']           = 1;
                        $this->conversation->update();
                        return $this->getTelegram()->executeCommand("newpost");
                        break;

                    default:
                        # code...
                        break;
                }
            }

            $notes['last_message_id']        = $message->getMessageId();
            $notes['state']                  = 8;
            $this->conversation->update();

        case 8:
            $data = [];
            $data['chat_id'] = $chat_id;
            $data['text'] = sprintf($strings['newpost_8_intro'], '@' .$this->telegram->getBotUsername() .' ' .base64_encode($chat_id));
            $result = Request::sendMessage($data);


            // $data['reply_markup'] = new InlineKeyboard([
            //     ['text' => 'inline', 'switch_inline_query' => $switch_element],
            //     ['text' => 'inline current chat', 'switch_inline_query_current_chat' => $switch_element],
            // ], [
            //     ['text' => 'callback', 'callback_data' => 'identifier'],
            //     ['text' => 'open url', 'url' => 'https://github.com/akalongman/php-telegram-bot'],
            // ]);
            $data['reply_markup'] = new InlineKeyboard([
                ['text' => $strings['newpost_8_butt1'], 'switch_inline_query' => base64_encode($this->conversation->getID())],
            ]);
            $data['text'] = sprintf($strings['newpost_8_concu'], '@' .$this->telegram->getBotUsername() .' ' .base64_encode($this->conversation->getID()));

            $result = Request::sendMessage($data);
            $this->conversation->stop();



      }
      return $result;

	}


}
