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
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\DB;
use Longman\TelegramBot\Entities\Chat;

/**
 * User "/inlinekeyboard" command
 */
class MyscoreCommand extends UserCommand
{
    /**#@+
     * {@inheritdoc}
     */
    protected $name = 'myscore';
    protected $description = 'محاسبه امتیاز شما برای دریافت کد شارژ جایزه';
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
		$chat_type = $message->getChat()->getType();
		$user_id = $message->getFrom()->getId();
		$user_Fname = $message->getFrom()->getFirstName();
        $user_Lname = $message->getFrom()->getLastName();
        $user_Uname = $message->getFrom()->getUsername();
		$type = $message->getType();
        $text_m    = trim($message->getText(true));
		

		$results = DB::selectChatsByHosts(
            true, //Select groups (group chat)
            true, //Select supergroups (super group chat)
            true, //Select users (single chat)
            null, //'yyyy-mm-dd hh:mm:ss' date range from
            null, //'yyyy-mm-dd hh:mm:ss' date range to
            $user_id, //Specific chat_id to select
			null
        );
		
		$user_chats        = 0;
        $group_chats       = 0;
        $super_group_chats = 0;
		
		//$text_back = 'امتیاز شما با توجه به افرادی که اضافه کردین و گروه ها و سوپر گروه هایی که ربات رو به اون اضافه کردین محاسبه می شود.' .PHP_EOL
		$text_back = 'امتیاز شما با توجه به افرادی که به ربات اضافه کردید محاسبه می شود.' .PHP_EOL
			.'عملکرد شما تا الان :' .PHP_EOL;
		
		if (is_array($results)) {
            foreach ($results as $result) {
                //Initialize a chat object
                $result['id'] = $result['chat_id'];
                $chat         = new Chat($result);

                $whois = $chat->getId();
                if ($this->telegram->getCommandObject('whois')) {
                    // We can't use '-' in command because part of it will become unclickable
                    $whois = '/whois' . str_replace('-', 'g', $chat->getId());
                }

                if ($chat->isPrivateChat()) {
                    //if ($text !== '') {
                    //    $text_back .= '- P ' . $chat->tryMention() . ' [' . $whois . ']' . PHP_EOL;
                    //}

                    ++$user_chats;
                } elseif ($chat->isSuperGroup()) {
                    //if ($text !== '') {
                    //    $text_back .= '- S ' . $chat->getTitle() . ' [' . $whois . ']' . PHP_EOL;
                    //}

                    ++$super_group_chats;
                } elseif ($chat->isGroupChat()) {
                    //if ($text !== '') {
                    //    $text_back .= '- G ' . $chat->getTitle() . ' [' . $whois . ']' . PHP_EOL;
                    //}

                    ++$group_chats;
                }
            }
        }
		
		if ($user_chats > 4){
			$user_chats = 4;
		}

        //if (($user_chats + $group_chats + $super_group_chats) === 0) {
        //    $text_back = 'No chats found..';
        //} else {
            $text_back .= PHP_EOL . 'افراد اضافه شده : ' . $user_chats;
            //$text_back .= PHP_EOL . 'اضافه کردن به گروه ها   : ' . $group_chats;
            //$text_back .= PHP_EOL . 'اضافه کردن به سوپرگروه: ' . $super_group_chats;
            //$text_back .= PHP_EOL .PHP_EOL .PHP_EOL .'مجموع امتیاز شما      : ' . ($user_chats*1000 + $group_chats*500 + $super_group_chats*900);

            //if ($text === '') {
            //    $text_back .= PHP_EOL . PHP_EOL . 'List all chats: /' . $this->name . ' *' . PHP_EOL . 'Search for chats: /' . $this->name . ' <search string>';
            //}
        //}
		
		$text_back .= PHP_EOL . PHP_EOL . 'برای فعال شدن امکانات ربات لازم است 5 نفر را به ربات اضافه کنید.';

	
		$data = [
			'chat_id'      => $chat_id,
			'text'         => $text_back,
		];
		$Res = Request::sendMessage($data);			

		return $user_chats;
		
		
		//$text = 'سلام'.PHP_EOL 
		//	.'من ربات شارژ رایگان هستم' .PHP_EOL 
		//	.'با لینک زیر بیا تو ربات و با انجام کاری که بهت میکم شارژ رایگان بگیر' .PHP_EOL .PHP_EOL .PHP_EOL
		//	.'من ربات شارژ رایگان هستم' .PHP_EOL ;
        //$data = [
        //    'chat_id'      => $chat_id,
        //    'text'         => $text,
        //];

        //return Request::sendMessage($data);
    }
}
