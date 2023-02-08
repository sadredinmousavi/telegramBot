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
class SpchatCommand extends UserCommand
{
    /**#@+
     * {@inheritdoc}
     */
    protected $name = 'spchat';
    protected $description = 'چت های ویژه';
    protected $usage = '/spchat';
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
		
		$is_started = false;
		$is_ended = true;
		$conversation_1 = new Conversation($chat_id, $chat_id);
		if( $conversation_1->exists() && ($command_1 = $conversation_1->getCommand()) ){
			$nte_1 = $conversation_1->notes;
			if ( $command_1 === $this->getName() ){
				$is_started = true;
				//
				$user_id2 = trim($nte_1['id']);
				$lobby2 = $nte_1['lobby'];
				//
				$conversation_2 = new Conversation($user_id2, $user_id2);
				if ( $conversation_2->exists() && ($command_2 = $conversation_2->getCommand()) ){
					$nte_2 = $conversation_2->notes;
					if ( $command_2 === $this->getName() ){
						$user_id1 = trim($nte_2['id']);
						$lobby1 = $nte_2['lobby'];
						if ( $user_id1 == $chat_id ) {
							$is_ended = false;
						}
					}
				}
			}
		}
		
		if (!$is_started){
			$data1 = [
				'chat_id'      => $chat_id,
				'text'         => 'برای ورود به چت ویژه باید از دستور' .' /chats ' .' و یا از کیبورد زیر استفاده کنید.',
			];			
			$a =  Request::sendMessage($data1);
			return $this->telegram->executeCommand('menu');
		}

		
		
		$KeysEnd = json_decode('"\uD83D\uDD1A"') .' ' .'خاتمه چت';
		$KeysStart = json_decode('"\uD83D\uDD0E"') .' ' .'جست و جو' .' ' .json_decode('"\uD83D\uDD0E"');
		$Keys[0] = json_decode('"\uD83D\uDD19"') .' ' .'بازگشت';
		$Keys[1] = json_decode('"\uD83D\uDE47\uD83C\uDFFB"') .' ' .'مشاهده پروفایل طرف مقابل';
		$Keys[2] = json_decode('"\uD83D\uDC81"') .' ' .'فالو کردن طرف مقابل';
		$Keys[3] = json_decode('"\u26F9"') .' ' .'خاتمه چت';
		$Keys[4] = json_decode('"\uD83D\uDC64"') .' ' .'نظر سنجی';
		

		//Conversation start
        $this->conversation = new Conversation($user_id, $chat_id, $this->getName());

        $notes = &$this->conversation->notes;
        !is_array($notes) && $notes = [];

        //cache data from the tracking session if any
        $state = 0;
		$newcomer = false;
        if (isset($notes['state'])) {
            $state = $notes['state'];
        } else {
			$newcomer = true;
		}
		$key = array_search($text_m, $Keys);
		if (is_numeric($key)){
			$state = $key;
			//$newcomer = true;
		}
		if ($is_started){
			if($is_ended){
				if ($state !== 4){
					$state = 4;
					$newcomer = true;
					$data1 = [
						'chat_id'      => $chat_id,
						'text'         => 'طرف مقابل از چت خارج شده است.',
					];			
					$a =  Request::sendMessage($data1);
				}
			}
		}

        $result = Request::emptyResponse();

        //State machine
        //Entrypoint of the machine state if given by the track
        //Every time a step is achieved the track is updated
        switch ($state) {
            case 0:
				//$text = 'این چت کاملا مخفی است و طرف مقابل تنها قادر است مشخصات مجازی شما را ببیند و یا شما را فالو کند'
				//		.PHP_EOL .PHP_EOL .'برای خاتمه چت، فالو کردن طرف مقابل و مشاهده کشخصات مجازی طرف مقابل تنها از کیبورد زیر استفاده کنید. (سایر دستور ها تا پایان این چت غیر فعال هستند.) ';
				  
				//$notes['lobby'] = 0;
				$notes['state'] = 0;
				$this->conversation->update();
				if ($newcomer || $text_m === $Keys[0] ){
					$keyboard = new Keyboard([
						//['text' => 'ارسال موقعیت', 'request_location' => true],
						['text' => $Keys[1]],
						['text' => $Keys[2]],
					], [
						['text' => $Keys[3]],
					]);
					$keyboard->setResizeKeyboard(true)
							 ->setOneTimeKeyboard(true)
							 ->setSelective(false);

					$data = [
						'chat_id'      => $chat_id,
						//'text'         => $text,
						'text'         => 'منو :',
						'reply_markup' => $keyboard,
					];
							
					$result = Request::sendMessage($data);
				}
				
				if ( $text_m === $Keys[0] ){
					break;
				}
				
				
				if ($text_m === $KeysEnd){
					$notes['state'] = 4;
					$this->conversation->update();
					$nte2 = &$conversation_2->notes;
					$nte2['state'] = 4;
					$conversation_2->update();
					$text1 = 'چت خاتمه یافت. بعد از اینجا مجددا به چت روم عمومی می روید.'
						.PHP_EOL .PHP_EOL .'اگر مایل نظر خود را در مورد کندی سرعت، کیفیت چت و سایر موارد به کارشناسان ما بگویید درون همین چت تایپ کنید.'
						.PHP_EOL .'سپس برای رفتن به چت روم عمومی دکمه " خاتمه " را از کیبورد زیر بزنید.';
					$text2 = 'طرف مقابل شما چت را خاتمه داده است.'
						.PHP_EOL .'بعد از اینجا مجددا به چت روم عمومی می روید.'
						.PHP_EOL .PHP_EOL .'اگر مایل نظر خود را در مورد کندی سرعت، کیفیت چت و سایر موارد به کارشناسان ما بگویید درون همین چت تایپ کنید.'
						.PHP_EOL .'سپس برای رفتن به چت روم عمومی دکمه " خاتمه " را از کیبورد زیر بزنید.';
					
					$keyboard = new Keyboard([
						['text' => $KeysEnd],
					]);
					$keyboard->setResizeKeyboard(true)
							 ->setOneTimeKeyboard(true)
							 ->setSelective(false);
							 
					$r1 = Request::sendMessage(['chat_id' => $user_id1, 'text' => $text1, 'reply_markup' => $keyboard]);
					
					$r2 = Request::sendMessage(['chat_id' => $user_id2, 'text' => $text2, 'reply_markup' => $keyboard]);
							 
					//return $this->getTelegram()->executeCommand($this->getName());
					break;
				}
				
				//
				//فرستادن پیغام به طرف مقابل
				//
				if (in_array($type, ['audio', 'document', 'sticker', 'photo', 'video', 'voice'], true)) {
					$doc = call_user_func([$message, 'get' . ucfirst($type)]);
					($type === 'photo') && $doc = $doc[0];
					$caption = call_user_func([$message, 'getCaption']);
					//$file = Request::getFile(['file_id' => $doc->getFileId()]);
					//if ($file->isOk()) {
					//	Request::downloadFile($file->getResult());
					//}
					$data['chat_id'] = $user_id2;
					if ($caption) {
						$data['caption'] = $caption;
					}
					$file_id = $doc->getFileId();
					switch ($type){
						case 'audio':
							$data['audio'] = $file_id;
							$result = Request::sendAudio($data);
							break;
						case 'sticker':
							$data['sticker'] = $file_id;
							$result = Request::sendSticker($data);
							break;
						case 'photo':
							$data['photo'] = $file_id;
							$result = Request::sendPhoto($data);
							break;
						case 'video':
							$data['video'] = $file_id;
							$result = Request::sendVideo($data);
							break;
						case 'voice':
							$data['voice'] = $file_id;
							$result = Request::sendVoice($data);
							break;
						case 'document':
							$data['document'] = $file_id;
							$result = Request::sendDocument($data);
							break;
					}
				} else {
					$data = [
						'chat_id'  =>  $user_id2,
						'text'         => $text_m, //.PHP_EOL .json_encode($c),//implode(' ,',$chats),
						
					];
					$result        = Request::sendMessage($data);
				}
				//
				//
				
				break;

				// no break
			case 1:
				
				break;

			
			case 2:
				
				break;


			case 3:
				$text = 'اگر مایل به خاتمه چت هستید دکمه " خاتمه چت " از کیبورد زیر را بزنید.'
					.PHP_EOL .'در غیر این صورت دکمه " بازگشت " را بزنید تا چت ادامه پیدا کند.';
				//$notes['state'] = 3;
				//$this->conversation->update();
				$data['chat_id'] = $chat_id;

				$keyboard = new Keyboard([
					//['text' => 'ارسال موقعیت', 'request_location' => true],
					['text' => $KeysEnd],
				], [
					['text' => $Keys[0]],
				]);
				$keyboard->setResizeKeyboard(true)
						 ->setOneTimeKeyboard(true)
						 ->setSelective(false);
						 
					
				$data['reply_markup'] = $keyboard;
					
				$data['text'] = $text;
				$result = Request::sendMessage($data);
				break;

			
			case 4:
				if( $newcomer ){
					$text2 = 'طرف مقابل شما چت را خاتمه داده است.'
						.PHP_EOL .'بعد از اینجا مجددا به چت روم عمومی می روید.'
						.PHP_EOL .PHP_EOL .'اگر مایل نظر خود را در مورد کندی سرعت، کیفیت چت و سایر موارد به کارشناسان ما بگویید درون همین چت تایپ کنید.'
						.PHP_EOL .'سپس برای رفتن به چت روم عمومی دکمه " خاتمه " را از کیبورد زیر بزنید.';
						
					$keyboard = new Keyboard([
						['text' => $KeysEnd],
					]);
					$keyboard->setResizeKeyboard(true)
							 ->setOneTimeKeyboard(true)
							 ->setSelective(false);
							 
					$r2 = Request::sendMessage(['chat_id' => $chat_id, 'text' => $text2, 'reply_markup' => $keyboard]);
				} else {
					$keyboard = new Keyboard([
						['text' => $KeysEnd],
					]);
					$keyboard->setResizeKeyboard(true)
							 ->setOneTimeKeyboard(true)
							 ->setSelective(false);
							 
					$r2 = Request::sendMessage(['chat_id' => $chat_id, 'text' => ' ', 'reply_markup' => $keyboard]);
				}
				$notes['state'] = 4;
				$this->conversation->update();
				$data['chat_id'] = $chat_id;
				if ($text_m === $KeysEnd){
					$this->conversation->stop();
					$text2 = 'از اعتماد شما متشکریم.';						
					$keyboard = $this->getTelegram()->executeCommand("keyboard");
					
					$r2 = Request::sendMessage(['chat_id' => $chat_id, 'text' => $text2, 'reply_markup' => $keyboard]);
					$r3 = $this->telegram->executeCommand('menu');
					
					break;
				} else {
					//ثبت نظر مردم در اینجا
					break;
				}
		}	

        return $result;
	}
    
}
