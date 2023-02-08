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

/**
 * User "/inlinekeyboard" command
 */
class MygiftCommand extends UserCommand
{
    /**#@+
     * {@inheritdoc}
     */
    protected $name = 'mygift';
    protected $description = 'دریافت کد شارژ جایزه در صورتی که امتیاز لازم را کسب کرده باشید';
    protected $usage = '/mygift';
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
		
		
		$data = [
            'chat_id'      => '@Shayenews',
			'user_id'      => $user_id,
        ];
		$Result = Request::getChatMember($data);
		$Results = $Result->getResult();
		if(!empty($Results)){
			$user_status = $Results->getStatus();
		} else {
			$text = 'شما هنوز عضو کانال نشده اید' .PHP_EOL 
				.'برای دریافت کد شارژ باید در کانال عضو باشید' .PHP_EOL .PHP_EOL .PHP_EOL
				.'@Shayenews' .PHP_EOL ;
			$data = [
				'chat_id'      => $chat_id,
				'text'         => $text,
			];
			return Request::sendMessage($data);			
		}
		
		
		
		if ($user_status == 'member' || $user_status == 'creator' || $user_status == 'administrator'){
			$score = $this->getTelegram()->executeCommand("myscore");
		} else if ($user_status == 'left' || $user_status == 'kicked'){
			$text = 'شما از کانال خارج شده اید' .PHP_EOL 
				.'برای دریافت کد شارژ باید در کانال عضو باشید' .PHP_EOL .PHP_EOL .PHP_EOL
				.'@Shayenews' .PHP_EOL ;
			$data = [
				'chat_id'      => $chat_id,
				'text'         => $text,
			];
			$Res = Request::sendMessage($data);	
		}
		$howmany = 5 - $score;
		$text = 'امتیاز شما کافی نیست'.PHP_EOL 
			.'برای دریافت اولین شارژ لازم است ' .$howmany .' نفر دیگر به ربات اضافه کنید.' .PHP_EOL .PHP_EOL .PHP_EOL
			.'برای دریافت بنر اختصاصی از دستور زیر استفاده کنید.' .PHP_EOL 
			.'/banner' .PHP_EOL;
        $data = [
            'chat_id'      => $chat_id,
            'text'         => $text,
        ];

        return Request::sendMessage($data);
    }
}
