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
use Longman\TelegramBot\MyDB;
use Longman\TelegramBot\Entities\Chat;

/**
 * User "/inlinekeyboard" command
 */
class HandleCommand extends UserCommand
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
		
		$selection = ['users' => true, 'groups' => false, 'super_groups' => false];
		$results = MyDB::selectChatsN('normal', $selection, ['chat_id' => $chat_id]);
		if (!empty($results)) {
            $result = reset($results);
        }
		
		if (is_array($result)) {
            $result['id'] = $result['chat_id'];
            $chat         = new Chat($result);

            $user_id    = $result['id'];
            $created_at = $result['chat_created_at'];
            $updated_at = $result['chat_updated_at'];
            $old_id     = $result['old_id'];
			$host       = $result['host']; 
			$usage      = $result['radius']; 
        }
		
		
		if ($usage > 5){
			$data = [
				'chat_id'      => '@Shayenews',
			];
			$Results = Request::getChatMembersCount($data);
			$members_count = $Results->getResult();
			//
			$Result = Request::getChatMember($data);
			$Results = $Result->getResult();
			if(!empty($Results)){
				$user_status = $Results->getStatus();
			} else {
				$user_status = 'left';
			}
			if ($user_status == 'member' || $user_status == 'creator' || $user_status == 'administrator'){
				
			} else {
				$inline_keyboard = new InlineKeyboard([
					['text' => 'عضویت در کانال ', 'url' => 'https://telegram.me/joinchat/A7z4Vj8EAi4YrFaeEBuoOQ'],
				]);
				$text = 'شما تا کنون ' .$usage .' بار از امکانات ربات استفاده کرده اید.' .PHP_EOL 
					.'برای ادامه کار با ربات تنها کافی است در کانال زیر عضو شوید' .PHP_EOL 
					.'    ' .PHP_EOL ;
					//.'تا الان این کانال' .' ' .$members_count .' ' .'عضو داره. ممنون میشم تو هم اضافه بشی';
					
				$data = [
					'chat_id'      => $chat_id,
					'text'         => $text,
					'reply_markup' => $inline_keyboard,
				];

				return Request::sendMessage($data);
			}
		}
		
		if ($usage > 20){
			$score = $this->getTelegram()->executeCommand("myscore");
			$aa = 3;
			if ($score < $aa){
				$text = 'شما تا کنون ' .$usage .' بار از امکانات ربات استفاده کرده اید.' .$aa .' نفر رو به ربات معرفی کنی.' .PHP_EOL 
					.'برای دریافت بنر اختصاصی خوداز دستور' .' /banner' .'استفاده کنید.' .PHP_EOL 
					.'    ' .PHP_EOL
					.'امتیاز شما تا این لحظه' .' ' .$score .' ' .'هست.';
					
				$data = [
					'chat_id'      => $chat_id,
					'text'         => $text,
				];

				return Request::sendMessage($data);
			}
			
		}
		
		$newusage = $usage + 1;
		$R = MyDB::insertChatDetails($chat, ['radius' => $newusage]);
		
		$private_channel_id = -1001120138890;
		
		$data = [
					'chat_id'      => $private_channel_id,
					'from_chat_id' => $chat_id,
					'message_id'   => $message->getmessage_id(),
				];
				
		$new_message = Request::forwardMessage($data);
		
		$data = [
					'chat_id'      => $chat_id,
					'from_chat_id' => $private_channel_id,
					'message_id'   => $new_message->getResult()->getmessage_id(),
				];
				
		$last_message = Request::forwardMessage($data);
		
		$data = [
					'chat_id'      => $private_channel_id,
					'message_id'   => $new_message->getResult()->getmessage_id(),
				];
			
		$del_message = Request::deleteMessage($data);

		//$a = Request::sendMessage(['chat_id' => $chat_id, 'text' => $new_message->getResult()->getmessage_id(),]);
		//$a = Request::sendMessage(['chat_id' => $chat_id, 'text' => $last_message,]);
		//$a = Request::sendMessage(['chat_id' => $chat_id, 'text' => $del_message,]);
		

        return Request::emptyResponse();
    }
}
