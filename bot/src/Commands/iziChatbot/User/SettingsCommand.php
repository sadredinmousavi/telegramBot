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
class SettingsCommand extends UserCommand
{
    /**#@+
     * {@inheritdoc}
     */
    protected $name = 'settings';
    protected $description = 'تنظیمات';
    protected $usage = '/settings';
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
		$Keys[0] = json_decode('"\uD83D\uDD19"') .' ' .'بازگشت';
		$Keys[1] = json_decode('"\uD83D\uDCE1"') .' ' .'شعاع چتروم عمومی';
		$Keys[2] = json_decode('"\uD83C\uDF0F"') .' ' .'تغییر موقعیت مکانی';
		$Keys[3] = json_decode('"\uD83D\uDC64"') .' ' .'مشاهده مشخصات مجازی';
		$Keys[4] = json_decode('"\uD83D\uDC64"') .' ' .'تغییر تصویر پروفایل مجازی';
		$Keys[5] = json_decode('"\uD83D\uDC64"') .' ' .'تغییر یوزرنیم مجازی';
		$Keys[6] = json_decode('"\uD83D\uDC64"') .' ' .'مدیریت بلاک، فالو و ریپورت';
		$Keys[7] = 'لیست فالورها';
		$Keys[8] = 'لیست فالویینگ ها';
		$Keys[9] = 'لیست بلاکی ها';
		$Keys[10] = 'لیست ریپرتی ها';
		

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
				   .PHP_EOL .'برای تغییر در تنظیمات می توانید از دستورات مشخص شده یا کیبورد زیر استفاده کنید.' .PHP_EOL
				   .PHP_EOL .'/radius - ' .'تغییر شعاع برای چتروم عمومی'
				   .PHP_EOL .'/loc - ' .'تغییر موقعیت مکانی چتروم عمومی';
				   
				$notes['state'] = 0;
				$this->conversation->update();
						
				$keyboard = new Keyboard([
					//['text' => 'ارسال موقعیت', 'request_location' => true],
					['text' => $Keys[1]],
					['text' => $Keys[2]],
					['text' => $Keys[3]],
				], [
					['text' => $Keys[6]],
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
				$notes['selected_button'] = $text_m;
				$this->conversation->update();
				$this->conversation->stop();
				return $this->getTelegram()->executeCommand("radius");
				break;
			
			case 2:
				$notes['selected_button'] = $text_m;
				$this->conversation->update();
				$this->conversation->stop();
				return $this->getTelegram()->executeCommand("loc");
				break;

			case 3:
				$notes['state'] = 3;
				$this->conversation->update();
				
				$results = MyDB::selectChatsN('normal', ['users' => true], ['chat_id' => $chat_id]);
				if (!empty($results)) {
					$result = reset($results);
				}
				if (is_array($result)) {
					$result['id'] = $result['chat_id'];
					$chat         = new Chat($result);

					$user_id      = $result['id'];
					$created_at   = $result['chat_created_at'];
					$updated_at   = $result['chat_updated_at'];
					$old_id       = $result['old_id'];
					
					
					$dummy_id        = $result['dummy_id'];
					$dummy_username  = $result['dummy_username'];
					$dummy_photoid   = $result['dummy_photoid'];
					$lat             = $result['lat'];
					$lng			 = $result['lng'];
					$personal_type   = $result['personal_type'];
					$host            = $result['host']; 
					$gender          = $result['gender']; 
					$coins           = $result['coins']; 
					$premium         = $result['premium']; 
					$active          = $result['active']; 
							
				}
				$data['chat_id'] = $chat_id;
				if ($chat !== null) {
					if ($chat->isPrivateChat()) {
						$text = 'User ID: ' . $dummy_id . PHP_EOL;
						$text .= 'User Name: ' . $dummy_username  . PHP_EOL;

						
						$text .= 'Location: ' .'lat : '.$lat .' , lng : ' .$lng . PHP_EOL;
						$text .= 'Personality Type: ' . $personal_type . PHP_EOL;
						$text .= 'Gender: ' . $gender . PHP_EOL;
						

						//$text .= 'First time seen: ' . $created_at . PHP_EOL;
						//$text .= 'Last activity: ' . $updated_at . PHP_EOL;


						if (null !== $dummy_photoid) {
							/** @var UserProfilePhotos $user_profile_photos */
							$data['photo']   = $dummy_photoid;
							$data['caption'] = $text;

							$r = Request::sendPhoto($data);
						} else {
							$data['text'] = $text;

							$r = Request::sendMessage($data);
						}
					}
				}
				
				
				$keyboard = new Keyboard([
					//['text' => 'ارسال موقعیت', 'request_location' => true],
					['text' => $Keys[4]],
					['text' => $Keys[5]],
				], [
					['text' => $KeysEnd],
				]);
				$keyboard->setResizeKeyboard(true)
						 ->setOneTimeKeyboard(true)
					     ->setSelective(false);

				$text = 'تنظیمات مشخصات مجازی :'
				   .PHP_EOL .'برای تغییر عکس مجازی پروفایل و نام کاربری مجازی از کیبورد زیر استفاده کنید.';
				   
				$data = [
					'chat_id'      => $chat_id,
					'text'         => $text,
					'reply_markup' => $keyboard,
				];
						
				$result = Request::sendMessage($data);
				break;
			case 4:
				if ($message->getPhoto() === null) {
					$notes['state'] = 4;
					$this->conversation->update();
					if($newcomer){
						$data = [
							'chat_id'      => $chat_id,
							'text'         => 'لطفا عکس مورد نظر خود را بفرستید.',
						];
						//$result = Request::sendMessage($data);
					} else {
						$data = [
							'chat_id'      => $chat_id,
							'text'         => 'متاسفانه پیغام شما حاوی عکس نیست!',
						];
						//$result = Request::sendMessage($data);
					}
					
					$result = Request::sendMessage($data);
					break;
				} else {
					/** @var PhotoSize $photo */
					$notes['state']    = 3;
					$photo             = $message->getPhoto()[0];
					$notes['photo_id'] = $photo->getFileId();
					$this->conversation->update();
					$R = MyDB::insertChatDetails($chat, ['dummy_photoid' => $photo->getFileId()]);
					$data = [
						'chat_id'      => $chat_id,
						'text'         => 'عکس شما به درستی ذخیره شد.',
					];
					$result = Request::sendMessage($data);
                    return $this->getTelegram()->executeCommand("settings");
					break;
				}
			
			case 5:
				if (strlen($text_m) < 1 || strlen($text_m) > 10) {
					$notes['state'] = 5;
					$this->conversation->update();
					if($newcomer){
						$data = [
							'chat_id'      => $chat_id,
							'text'         => 'لطفا یوزرنیم خود را بفرستید.(باید بین 1 تا 10 حرف باشد).',
						];
						//$result = Request::sendMessage($data);
					} else {
						$data = [
							'chat_id'      => $chat_id,
							'text'         => 'لطفا یوزرنیم خود را بین 1 تا 10 حرف انتخاب کنید.',
						];
						//$result = Request::sendMessage($data);
					}
					
					$result = Request::sendMessage($data);
					break;
				} else {
					$notes['state']    = 3;
					$notes['username'] = $text_m;
					$this->conversation->update();
					$R = MyDB::insertChatDetails($chat, ['dummy_username' => $text_m]);
					$data = [
						'chat_id'      => $chat_id,
						'text'         => 'یوزرنیم شما به درستی ذخیره شد.',
					];
					$result = Request::sendMessage($data);
                    return $this->getTelegram()->executeCommand("settings");
                    break;
				}
			case 6:
				$text = $Keys[6] .' : '
					.PHP_EOL .'برای تغییر در تنظیمات می توانید از دستورات مشخص شده یا کیبورد زیر استفاده کنید.' .PHP_EOL
					.PHP_EOL .'/banlist - ' .'مشاهده و تغییر افرادی که بلاک کردید'
					.PHP_EOL .'/followers - ' .'مشاهده افرادی که شما را فالو کردند'
					.PHP_EOL .'/followings - ' .'مشاهده و تغییر افرادی که فالو کردید'
					.PHP_EOL .'/reportlist - ' .'مشاهده و تغییر افرادی که ریپورت کردید';
				   
				$notes['state'] = 6;
				$this->conversation->update();
						
				$keyboard = new Keyboard([
					//['text' => 'ارسال موقعیت', 'request_location' => true],
					['text' => $Keys[7]],
					['text' => $Keys[8]],
				], [
					['text' => $Keys[9]],
					['text' => $Keys[10]],
				], [
					['text' => $Keys[0]],
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
				
			case 7:
				return $this->getTelegram()->executeCommand("followers");
				break;
				
			case 8:
				return $this->getTelegram()->executeCommand("followings");
				break;
				
			case 9:
				return $this->getTelegram()->executeCommand("banlist");
				break;
				
			case 10:
				return $this->getTelegram()->executeCommand("u", 121);
				break;
					
        }

        return $result;
			
			
		
		$howmany = 5 - $score;
		$text = 'تعداد کاربرانی که شما دعوت کرده اید ' .$score .PHP_EOL 
			.' نفر است. برای استفاده از امکانات ربات باید ' .$howmany .'نفر دیگر به ربات اضافه کنید.' .PHP_EOL .PHP_EOL .PHP_EOL
			.'برای دریافت بنر اختصاصی از دستور زیر استفاده کنید.' .PHP_EOL 
			.'/banner' .PHP_EOL;
        $data = [
            'chat_id'      => $chat_id,
            'text'         => $text,
        ];

		$data['reply_markup'] = $this->getTelegram()->executeCommand("keyboard");
		
        return Request::sendMessage($data);
    }
}
