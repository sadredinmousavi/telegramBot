<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\Chat;
use Longman\TelegramBot\Request;

use Longman\TelegramBot\MyFuns AS MyFun;
use Longman\TelegramBot\MyDB;

/**
 * User "/inlinekeyboard" command
 */
class MyscoreCommand extends UserCommand
{
    /**#@+
     * {@inheritdoc}
     */
    protected $name = 'myscore';
    protected $description = 'show your score';
    protected $usage = '/myscore';
    protected $version = '0.1.0';
    /**#@-*/

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $message = $this->getMessage();
    	$chat_id = $message->getChat()->getId();
        $type    = $message->getType();
        $text    = trim($message->getText(true));

        $satoshi = 0;

        $created_at = time();

        $selection = ['users' => true, 'groups' => false, 'super_groups' => false];
        $results = MyDB::selectChatsN('normal', $selection, ['chat_id' => $chat_id]);
        if (!empty($results)) {
            $result = reset($results);
            if (is_array($result)) {
                $lang       = $result['lang'];
                $satoshi    = $result['satoshi'];
                $created_at = $result['created_at'];
            }
        }
        $strings = MyFun::getStrings($lang);



        $results = MyDB::selectChatsN('byhost', $selection, ['host_id' => $chat_id]);

        $user_chats        = 0;
        $group_chats       = 0;
        $super_group_chats = 0;

        if (is_array($results)) {
            foreach ($results as $result) {
                $result['id'] = $result['chat_id'];
                $chat         = new Chat($result);
                if ($chat->isPrivateChat()) {
                    ++$user_chats;
                } elseif ($chat->isSuperGroup()) {
                    ++$super_group_chats;
                } elseif ($chat->isGroupChat()) {
                    ++$group_chats;
                }
            }
        }
        $days = floor((time() - strtotime($created_at)) / (60 * 60 * 24));
        $percent = 5000*$days*$user_chats/6;
        $text = sprintf($strings['myscore_intro'], $satoshi, $user_chats, $percent);

        $data = [
            'chat_id'      => $chat_id,
            'text'         => $text,
        ];

        return Request::sendMessage($data);
    }
}
