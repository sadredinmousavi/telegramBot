<?php


namespace Longman\TelegramBot\Commands\UserCommands;


use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\DB;
use Longman\TelegramBot\Entities\Chat;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\ConversationDB;



use Longman\TelegramBot\MyFuns AS MyFun;
use Longman\TelegramBot\MyDB;



class WalletCommand extends UserCommand
{
    protected $name = 'wallet';

	protected $description = 'get your satoshi';

	protected $usage = '/wallet';

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

    in_array($type, ['command', 'text'], true) && $type = 'message';

    $selection = ['users' => true, 'groups' => false, 'super_groups' => false];
    $results = MyDB::selectChatsN('normal', $selection, ['chat_id' => $chat_id]);
    if (!empty($results)) {
        $result = reset($results);
        if (is_array($result)) {
            $lang              = $result['lang'];
            $last_satoshi_date = $result['last_satoshi_date'];
            $satoshi           = $result['satoshi'];
        }
    }
    $strings = MyFun::getStrings($lang);

    $in_keyboard = ($text === $strings['wallet_withdraw_button'] || $text === $strings['wallet_getfree_button']);
    $in_keyboard_1 = ($text === $strings['wallet_getfree_intro_butt']);

    $data = [
        'chat_id' => $chat_id,
    ];
    // Conversation
    $this->conversation = new Conversation($user_id, $chat_id, $this->getName());

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
            if (!$in_keyboard) {
                $notes['state'] = 0;
                $this->conversation->update();

                // Execute this just with object that allow caption
                $data['reply_markup'] = new Keyboard(
                    [
                        'keyboard'          => [[$strings['wallet_getfree_button']], [$strings['wallet_withdraw_button']]],
                        'resize_keyboard'   => true,
                        'one_time_keyboard' => true,
                        'selective'         => true,
                    ]
                );

                $data['text'] = $strings['wallet_intro'];
                if ($notes['last_message_id'] !== $message->getMessageId()) {
                    $data['text'] = $strings['wallet_intro_skey'];
                }
                $result = Request::sendMessage($data);
                break;
            } else {
                if ($text === $strings['wallet_withdraw_button']){
                    $data['text']         = $strings['wallet_withdraw_intro'];
                    $data['reply_markup'] = Keyboard::remove(['selective' => true]);
                    $result  = Request::sendMessage($data);

                    $this->conversation->stop();

                    $this->getTelegram()->executeCommand("menu");
                    break;
                }
            }
            $notes['last_message_id'] = $message->getMessageId();
            $notes['state']           = 1;
            $this->conversation->update();
        // no break
        case 1:
            if (!$in_keyboard_1) {
                $notes['state'] = 1;
                $this->conversation->update();

                $data['reply_markup'] = new Keyboard([
                        'keyboard'          => [[$strings['wallet_getfree_intro_butt']]],
                        'resize_keyboard'   => true,
                        'one_time_keyboard' => true,
                        'selective'         => true,
                    ]);

                $data['text'] = sprintf($strings['wallet_getfree_intro'], $available_satoshi);
                if ($notes['last_message_id'] !== $message->getMessageId()) {
                    $data['text'] = $strings['wallet_getfree_intro_skey'];
                }
                $result = Request::sendMessage($data);
            } else {

                $data = [
    				'chat_id'      => $channel,
    				'user_id'      => $user_id,
    			];
    			$Res = Request::getChatMember($data);
    			$Result = $Res->getResult();
    			if(!empty($Result)){
    				$user_status = $Result->getStatus();
    			} else {
    				$user_status = 'left';
    			}
    			if ($user_status == 'member' || $user_status == 'creator' || $user_status == 'administrator'){
                    $no_mem = false;
    			} else {
                    $no_mem = true;
    			}

                $data['chat_id']      = $user_id;
                if (null === $last_satoshi_date){
                    $result = MyDB::insertChatDetails($this->getMessage()->getChat(), ['satoshi' => $satoshi+$available_satoshi]);
                    $result = MyDB::insertChatDetails($this->getMessage()->getChat(), ['last_satoshi_date' => 'NOW']);

                    $data['text']         = sprintf($strings['wallet_getfree_success'], $available_satoshi, $satoshi, $satoshi+$available_satoshi, $strings['menu_myscore']);
                    $data['reply_markup'] = Keyboard::remove(['selective' => true]);
                    $result  = Request::sendMessage($data);

                    // echo "<pre>", print_r($data), "</pre>";

                    $this->conversation->stop();

                    $this->getTelegram()->executeCommand("menu");
                } else {
                    if (!$no_mem){
                        if ($last_satoshi_date < date("Y-m-d")){
                            $result = MyDB::insertChatDetails($this->getMessage()->getChat(), ['satoshi' => $satoshi+$available_satoshi]);
                            $result = MyDB::insertChatDetails($this->getMessage()->getChat(), ['last_satoshi_date' => 'NOW']);

                            $data['text']         = sprintf($strings['wallet_getfree_success'], $available_satoshi, $satoshi, $satoshi+$available_satoshi, $strings['menu_myscore']);
                            $data['reply_markup'] = Keyboard::remove(['selective' => true]);
                            $result  = Request::sendMessage($data);

                            $this->conversation->stop();

                            $this->getTelegram()->executeCommand("menu");
                        } else {
                            $data['text']         = $strings['wallet_getfree_gotbefore'];
                            $data['reply_markup'] = Keyboard::remove(['selective' => true]);
                            $result  = Request::sendMessage($data);

                            $this->conversation->stop();

                            $this->getTelegram()->executeCommand("menu");
                        }
                    } else {
                        $data['text']         = sprintf($strings['wallet_getfree_nomember'], $channel);
                        $data['reply_markup'] = Keyboard::remove(['selective' => true]);
                        $result  = Request::sendMessage($data);

                        // $this->conversation->stop();
                        $notes['state'] = 0;
                        $this->conversation->update();

                        $this->getTelegram()->executeCommand("wallet");
                    }
                }
                //$result = MyDB::insertChatDetails($this->getMessage()->getChat(), ['last_satoshi_date' => 'NOW']);
                // $last_satoshi_date

            }
      }
      return $result;

	}


}
