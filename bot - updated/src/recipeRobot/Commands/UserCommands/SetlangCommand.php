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



class SetlangCommand extends UserCommand
{
    protected $name = 'setlang';

	protected $description = 'set language';

	protected $usage = '/setlang';

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




    in_array($type, ['command', 'text'], true) && $type = 'message';

    $selection = ['users' => true, 'groups' => false, 'super_groups' => false];
    $results = MyDB::selectChatsN('normal', $selection, ['chat_id' => $chat_id]);
    if (!empty($results)) {
        $result = reset($results);
        if (is_array($result)) {
            $lang = $result['lang'];
        }
    }
    $strings = MyFun::getStrings($lang);

    $in_keyboard = ($text === $strings['setlang_fa'] || $text === $strings['setlang_en'] || $text === $strings['setlang_sp']);

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
                        'keyboard'          => [[$strings['setlang_fa']], [$strings['setlang_en']], [$strings['setlang_sp']]],
                        'resize_keyboard'   => true,
                        'one_time_keyboard' => true,
                        'selective'         => true,
                    ]
                );

                $data['text'] = $strings['setlang_intro'];
                if ($notes['last_message_id'] !== $message->getMessageId()) {
                    $data['text'] .= PHP_EOL .PHP_EOL .$strings['setlang_skey'];
                }
                $result = Request::sendMessage($data);
                break;
            } else {
                switch ($text) {
                    case $strings['setlang_fa']:
                        $lang_new = 'fa';
                        break;

                    case $strings['setlang_en']:
                        $lang_new = 'en';
                        break;

                    case $strings['setlang_sp']:
                        $lang_new = 'sp';
                        break;

                    default:
                        $lang_new = 'fa';
                        break;
                }
                $result = MyDB::insertChatDetails($this->getMessage()->getChat(), ['lang' => $lang_new]);

                $this->conversation->stop();

                $data['text']         = $strings['setlang_set'];
                $data['reply_markup'] = Keyboard::remove(['selective' => true]);
                $result  = Request::sendMessage($data);

                $this->getTelegram()->executeCommand("menu");
            }
        // no break
      }
      return $result;

	}


}
