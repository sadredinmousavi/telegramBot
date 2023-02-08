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
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Entities\PhotoSize;
use Longman\TelegramBot\Entities\Chat;
use Longman\TelegramBot\MyDB;

/**
 * User "/inlinekeyboard" command
 */
class ChatCommand extends UserCommand
{
    /**#@+
     * {@inheritdoc}
     */
    protected $name = 'chat';
    protected $description = 'چت های ویژه';
    protected $usage = '/chat';
    protected $version = '0.1.0';
    /**#@-*/

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
		
		
		
        $message = $this->getMessage();
		$chat = $message->getChat();
        $chat_id = $message->getChat()->getId();
		$chat_type = $message->getChat()->getType();
		$user_id = $message->getFrom()->getId();
		$user_Fname = $message->getFrom()->getFirstName();
        $user_Lname = $message->getFrom()->getLastName();
        $user_Uname = $message->getFrom()->getUsername();
		$type = $message->getType();
        $text_m    = trim($message->getText(true));
		
		
		$can_continue = $this->getTelegram()->executeCommand("checker");
		if(!$can_continue){
			$d = ['chat_id' => $this->getMessage()->getChat()->getId(), 'text' => 'موقتا این دستور غیر فعال است.'];
			return Request::sendMessage($d);
		}
		
		$data = [
            'chat_id'      => '@Shayenews',
			'user_id'      => $user_id,
        ];
		
		$KeysEnd = json_decode('"\uD83D\uDD1A"') .' ' .'منوی اصلی';
		$KeysStart = json_decode('"\uD83D\uDD0E"') .' ' .'جست و جو' .' ' .json_decode('"\uD83D\uDD0E"');
		$Keys[0] = json_decode('"\uD83D\uDD19"') .' ' .'بازگشت';
		$Keys[1] = json_decode('"\uD83D\uDE47\uD83C\uDFFB"') .' ' .'چت با ناشناس';
		$Keys[2] = json_decode('"\uD83D\uDC81"') .' ' .'چت با مشابه خودم (از نظر شخصیت)';
		$Keys[3] = json_decode('"\u26F9"') .' ' .'تست هوش هیجانی';
		$Keys[4] = json_decode('"\uD83D\uDC64"') .' ' .'تغییر تصویر پروفایل مجازی';
		$Keys[5] = json_decode('"\uD83D\uDC64"') .' ' .'تغییر یوزرنیم مجازی';
		

		//Conversation start
        $this->conversation = new Conversation($user_id, $chat_id, $this->getName());

        $notes = &$this->conversation->notes;
        !is_array($notes) && $notes = [];

        //cache data from the tracking session if any
        $state = 0;
		$newcomer = false;
        if (isset($notes['state'])) {
            $state = $notes['state'];
        }
		$key = array_search($text_m, $Keys);
		if (is_numeric($key)){
			$state = $key;
			$newcomer = true;
		}

        $result = Request::emptyResponse();

        //State machine
        //Entrypoint of the machine state if given by the track
        //Every time a step is achieved the track is updated
        switch ($state) {
            case 0:
				$text = 'تنظیمات :'
				   .PHP_EOL .'توضیحات . . . ' .PHP_EOL
				   .PHP_EOL .'توضیحات . . . ';
				  
				$notes['lobby'] = 0;
				$notes['state'] = 0;
				$this->conversation->update();
						
				$keyboard = new Keyboard([
					//['text' => 'ارسال موقعیت', 'request_location' => true],
					['text' => $Keys[1]],
				], [
					['text' => $Keys[2]],
				], [
					['text' => $KeysEnd],
				]);
				$keyboard->setResizeKeyboard(true)
						 ->setOneTimeKeyboard(true)
					     ->setSelective(false);

				$data = [
					'chat_id'      => $chat_id,
					'text'         => $text,
					'reply_markup' => $keyboard,
				];
						
				$result = Request::sendMessage($data);
				break;

				// no break
			case 1:
				$notes['state'] = 1;
				$this->conversation->update();
				$data['chat_id'] = $chat_id;
				if ($text_m === $KeysStart){
					$notes['lobby'] = 1;
					$this->conversation->update();
					//
					$date_from = date('Y-m-d H:i:s', time() - 45);
					$chats = MyDB::selectConversationN(['command' => 'chat', 'date_from' => $date_from, 'my_id' => $chat_id]);
					$ch = MyDB::selectConversationN(['command' => 'spchat']);
					$spchat_total = count($ch);
					$lobby_count = 0;
					$lobby_total = 0;
					$lobby_ids = [];
					foreach ($chats as $ch){
						$fields = json_decode($ch['notes'], true);
						$lobby_id = $fields['lobby'];
						if ($lobby_id === $notes['state']){
							$lobby_count++;
							$lobby_ids[] = $ch['user_id'];
						}
						$lobby_total++;
					}
					$text = 'شما در لابی ' .$Keys[1] .' قرار گرفتید'
							.PHP_EOL .'لطفا حداکثر تا 30 ثانیه منظر بمانید تا متصل شوید'
							.PHP_EOL .PHP_EOL .'اگر دکمه "بازگشت" را بزنید از لابی خارج می شوید.' .PHP_EOL
							.PHP_EOL .'تعداد افراد حاضر در این لابی : ' .$lobby_count .' نفر '
							.PHP_EOL .'تعداد افراد حاضر در تمام لابی ها : ' .$lobby_total .' نفر '
							.PHP_EOL .'تعداد افراد در حال چت در تمام لابی ها : ' .$spchat_total .' نفر '
							.PHP_EOL .PHP_EOL .'اگر موفق به اتصال نشدید می توانید از چتروم عمومی فردی را به این لابی دعوت کنید ' .PHP_EOL;
					$data = [
						'chat_id'      => $chat_id,
						'text'         => $text,
					];
								
					$result = Request::sendMessage($data);
					
					//
					
					if($lobby_count > 0){
						foreach ($lobby_ids as $l_id){
							$conv = new Conversation($l_id, $l_id);
							//Fetch conversation command if it exists
							if ($conv->exists()) {
								$command = $conv->getCommand();
								$nte = $conv->notes;
								if ($nte['lobby'] === $notes['lobby']){
									$this->conversation->stop();
									$conv->stop();
									//
									$conversation_1 = new Conversation($l_id, $l_id, 'spchat');
									$nte1 = &$conversation_1->notes;
									$nte1['lobby'] = $notes['lobby'];
									$nte1['id'] = $chat_id;
									$conversation_1->update();
									
									$conversation_2 = new Conversation($chat_id, $chat_id, 'spchat');
									$nte2 = &$conversation_2->notes;
									$nte2['lobby'] = $notes['lobby'];
									$nte2['id'] = $l_id;
									$conversation_2->update();
									//
									$data1 = [
										'chat_id'      => $l_id,
										'text'         => 'شما وارد چت شدید',
									];			
									$result = Request::sendMessage($data1);
									$data2 = [
										'chat_id'      => $chat_id,
										'text'         => 'شما وارد چت شدید',
									];			
									$result = Request::sendMessage($data2);
									//
									$text = 'این چت کاملا مخفی است و طرف مقابل تنها قادر است مشخصات مجازی شما را ببیند و یا شما را فالو کند'
										.PHP_EOL .PHP_EOL .'برای خاتمه چت، فالو کردن طرف مقابل و مشاهده کشخصات مجازی طرف مقابل تنها از کیبورد زیر استفاده کنید. (سایر دستور ها تا پایان این چت غیر فعال هستند.) ';
									$result = Request::sendMessage(['chat_id'  => $l_id, 'text' => $text]);
									$result = Request::sendMessage(['chat_id'  => $chat_id, 'text' => $text]);
								}
								break;
							}
						}
					}
					//$this->conversation->stop();
					//return $this->getTelegram()->executeCommand("test1");
					break;
				} else {
					$keyboard = new Keyboard([
						//['text' => 'ارسال موقعیت', 'request_location' => true],
						['text' => $KeysStart],
					], [
						['text' => $Keys[0]],
					]);
					$keyboard->setResizeKeyboard(true)
							 ->setOneTimeKeyboard(true)
							 ->setSelective(false);
							 
					$text = $Keys[1] .PHP_EOL
							.PHP_EOL .'با فشردن دکمه "جست و جو" شما در لابی قرار می گیرید و به صورت خودکار به یک فرد ناشناس  متصل خواهید شد.'
							.PHP_EOL .'دکمه جست و جو را بزنید.';
					
					$data['reply_markup'] = $keyboard;
					
					/////
					//$data['photo']   = $dummy_photoid;
					//$data['caption'] = $text;
					//$result = Request::sendPhoto($data);
					/////
					$data['text'] = $text;
					$result = Request::sendMessage($data);
					break;
				}
				
			
			case 2:
				$notes['state'] = 2;
				$this->conversation->update();
				$data['chat_id'] = $chat_id;
				if ($text_m === $KeysStart){
					$this->conversation->stop();
					return $this->getTelegram()->executeCommand("test2");
					break;
				} else {
					$keyboard = new Keyboard([
						//['text' => 'ارسال موقعیت', 'request_location' => true],
						['text' => $KeysStart],
					], [
						['text' => $Keys[0]],
					]);
					$keyboard->setResizeKeyboard(true)
							 ->setOneTimeKeyboard(true)
							 ->setSelective(false);
							 
					$text = $Keys[2] .PHP_EOL
							.PHP_EOL .'یک سری توضیحات . . .' .PHP_EOL;
					
					$data['reply_markup'] = $keyboard;
					
					/////
					//$data['photo']   = $dummy_photoid;
					//$data['caption'] = $text;
					//$result = Request::sendPhoto($data);
					/////
					$data['text'] = $text;
					$result = Request::sendMessage($data);
					break;
				}

			case 3:
				$notes['state'] = 3;
				$this->conversation->update();
				$data['chat_id'] = $chat_id;
				if ($text_m === $KeysStart){
					$this->conversation->stop();
					return $this->getTelegram()->executeCommand("test3");
					break;
				} else {
					$keyboard = new Keyboard([
						//['text' => 'ارسال موقعیت', 'request_location' => true],
						['text' => $KeysStart],
					], [
						['text' => $Keys[0]],
					]);
					$keyboard->setResizeKeyboard(true)
							 ->setOneTimeKeyboard(true)
							 ->setSelective(false);
							 
					$text = $Keys[3] .PHP_EOL
							.PHP_EOL .'یک سری توضیحات . . .' .PHP_EOL;
					
					$data['reply_markup'] = $keyboard;
					
					/////
					//$data['photo']   = $dummy_photoid;
					//$data['caption'] = $text;
					//$result = Request::sendPhoto($data);
					/////
					$data['text'] = $text;
					$result = Request::sendMessage($data);
					break;
				}
		}	

        return $result;
	}
    
}
