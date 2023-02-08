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
class TestCommand extends UserCommand
{
    /**#@+
     * {@inheritdoc}
     */
    protected $name = 'test';
    protected $description = 'تست های روانشناسی متنوع';
    protected $usage = '/test';
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
		$KeysStart = 'شروع تست';
		$Keys[0] = json_decode('"\uD83D\uDD19"') .' ' .'بازگشت';
		$Keys[1] = json_decode('"\uD83D\uDE47\uD83C\uDFFB"') .' ' .'تست سن عقلی';
		$Keys[2] = json_decode('"\uD83D\uDC81"') .' ' .'تست MBTI';
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
				   .PHP_EOL .'انجام تست های روانشانسی مختلف در اینجا کاملا رایگانه و نتیجه اون فقط به خود شما گفته میشه.' .PHP_EOL
				   .PHP_EOL .'نتیجه این تست ها کاملا مخفی هست ولی اگر خودتون خواستید میتونید اون رو با دوستاتون به اشتراک بذارین'
				   .PHP_EOL .'برای انتخاب و توضیح بیشتر در مورد هر تست تست از کیبورد زیر استفاده کنید';
				   
				$notes['state'] = 0;
				$this->conversation->update();
						
				$keyboard = new Keyboard([
					//['text' => 'ارسال موقعیت', 'request_location' => true],
					['text' => $Keys[1]],
					['text' => $Keys[3]],
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
					$this->conversation->stop();
					return $this->getTelegram()->executeCommand("test1");
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
							.PHP_EOL .'یک سری توضیحات . . .' .PHP_EOL
							.PHP_EOL .'این تست شامل 20 سوال بوده و حدود 10 دقیقه زمان می برد.'
							.PHP_EOL .'اگر مایل به انجام تست هستید دکمه "شروع تست" از کیبورد زیر را بزنید.';
					
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
							.PHP_EOL .'یک سری توضیحات . . .' .PHP_EOL
							.PHP_EOL .'این تست شامل 20 سوال بوده و حدود 10 دقیقه زمان می برد.'
							.PHP_EOL .'اگر مایل به انجام تست هستید دکمه "شروع تست" از کیبورد زیر را بزنید.';
					
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
							.PHP_EOL .'یک سری توضیحات . . .' .PHP_EOL
							.PHP_EOL .'این تست شامل 20 سوال بوده و حدود 10 دقیقه زمان می برد.'
							.PHP_EOL .'اگر مایل به انجام تست هستید دکمه "شروع تست" از کیبورد زیر را بزنید.';
					
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
