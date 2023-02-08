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
use Longman\TelegramBot\DB;

/**
 * User "/inlinekeyboard" command
 */
class RadiusCommand extends UserCommand
{
    /**#@+
     * {@inheritdoc}
     */
    protected $name = 'radius';
    protected $description = 'تعیین شعاع دریافت پیغام ها';
    protected $usage = '/radius';
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
		//$Result = Request::getChatMember($data);
		//$Results = $Result->getResult();
		//if(!empty($Results)){
		//	$user_status = $Results->getStatus();
		//} else {
		//	$inline_keyboard = new InlineKeyboard([
		//		['text' => 'عضویت در کانال ', 'url' => 'https://telegram.me/joinchat/A7z4Vj8EAi4YrFaeEBuoOQ'],
		//	]);
		//	$text = 'شما هنوز عضو کانال نشده اید' .PHP_EOL 
		//		.'برای کار با ربات باید ایتدا در کانال عضو شوید' .PHP_EOL .PHP_EOL .PHP_EOL;
		//	$data = [
		//		'chat_id'      => $chat_id,
		//		'text'         => $text,
		//		'reply_markup' => $inline_keyboard,
		//	];
		//	return Request::sendMessage($data);			
		//}
		
		
		
		//if ($user_status == 'member' || $user_status == 'creator' || $user_status == 'administrator'){
		//	//$score = $this->getTelegram()->executeCommand("myscore");
		//	
		//} else if ($user_status == 'left' || $user_status == 'kicked'){
		//	$inline_keyboard = new InlineKeyboard([
		//		['text' => 'عضویت در کانال ', 'url' => 'https://telegram.me/joinchat/A7z4Vj8EAi4YrFaeEBuoOQ'],
		//	]);
		//	$text = 'شما از کانال خارج شده اید' .PHP_EOL 
		//		.'برای کار با ربات باید ایتدا در کانال عضو شوید' .PHP_EOL .PHP_EOL .PHP_EOL;
		//	$data = [
		//		'chat_id'      => $chat_id,
		//		'text'         => $text,
		//		'reply_markup' => $inline_keyboard,
		//	];
		//	return Request::sendMessage($data);	
		//}
		
		
		//Conversation start
        $this->conversation = new Conversation($user_id, $chat_id, $this->getName());

        $notes = &$this->conversation->notes;
        !is_array($notes) && $notes = [];

        //cache data from the tracking session if any
        $state = 0;
        if (isset($notes['state'])) {
            $state = $notes['state'];
        }

        $result = Request::emptyResponse();

        //State machine
        //Entrypoint of the machine state if given by the track
        //Every time a step is achieved the track is updated
        switch ($state) {
            case 0:
				if (!is_numeric($text_m) || ($text_m>2500 || $text_m<1)) {
						$notes['state'] = 1;
						$this->conversation->update();
						
						$keyboard = new Keyboard([
							['text' => 'ارسال موقعیت', 'request_location' => true],
						]);
						$keyboard->setResizeKeyboard(true)
								 ->setOneTimeKeyboard(true)
								 ->setSelective(false);

						$curr_radius = 1200;
						$speakers = 50;
						$audience = 100;
						$data = [
							'chat_id'      => $chat_id,
							'text'         => 'لطفا یک عدد بین 1 تا 2500 به عنوان شعاع  دریافت پیغام بفرستید.' .'(واحد کیلومتر است)'
												.PHP_EOL .'شعاع فعلی ' .$curr_radius . ' می باشد و تعداد ' .$speakers .' نفر قادر ارسال پیغام به شما هستند.'
												.PHP_EOL .'برای خروج بدون ذخیره کردن دستور '  .' /cancel '.' را بزنید.',
							//'reply_markup' => $keyboard,
						];
						
						$result = Request::sendMessage($data);
						break;
					}

					/** @var PhotoSize $photo */
					//$photo             = $message->getPhoto()[0];
					//$notes['photo_id'] = $photo->getFileId();

				// no break
			case 1:
				if (!is_numeric($text_m) || ($text_m>2500 || $text_m<1)) {
						$notes['state'] = 1;
						$this->conversation->update();

						$keyboard = new Keyboard([
							['text' => 'ارسال موقعیت', 'request_location' => true],
						]);
						$keyboard->setResizeKeyboard(true)
								 ->setOneTimeKeyboard(true)
								 ->setSelective(false);

						$data = [
							'chat_id'      => $chat_id,
							'text'         => 'لطفا فقط عدد شعاع را در بازه 1 تا 2500 بفرستید.' .PHP_EOL .'اگر مایل به لغو این پرسش هستید دستور ' .' /cancel ' .'را بدهید.',
							//'reply_markup' => $keyboard,
						];


						$result = Request::sendMessage($data);
						break;
					}

					/** @var PhotoSize $photo */
					$radius          = $text_m;
					//$notes['location'] = implode(",", $location);
					$notes['radius'] = $radius;

				// no break
			case 2:
                $this->conversation->update();
                //$out_text = '/Survey result:' . PHP_EOL;
                unset($notes['state']);
                //foreach ($notes as $k => $v) {
                //    $out_text .= PHP_EOL . ucfirst($k) . ': ' . $v;
                //}

                //$data['photo']        = $notes['photo_id'];
                //$data['reply_markup'] = Keyboard::remove(['selective' => true]);
                //$data['caption']      = $out_text;
                $this->conversation->stop();

                //$result = Request::sendPhoto($data);
				
				//return $this->getTelegram()->executeCommand("getpic");
				
				$keyboard = new Keyboard([
					['text' => 'دانلود فایل'],
				]);
				$keyboard->setResizeKeyboard(true)
						 ->setOneTimeKeyboard(true)
						 ->setSelective(false);
						 
				//$curr_radius = $notes['radius'];
				$curr_radius = $radius;
				$speakers = 50;
				$audience = 100;
						
				$R = DB::insertChatDetails($chat, ['radius' => $radius]);
				
				$text = 'شعاع جدید به درستی ذخیره شد.' .PHP_EOL
						.PHP_EOL .'شعاع فعلی ' .$curr_radius . ' می باشد و تعداد ' .$speakers .' نفر قادر ارسال پیغام به شما هستند.' .PHP_EOL
						.'همین طور ' .$audience .' نفر قادر به دریافت پیام شما هستند.' .PHP_EOL;
				$data = [
					'chat_id'      => $chat_id,
					'text'         => $text,
					//'reply_markup' => $keyboard,
				];
				$data['reply_markup'] = $this->getTelegram()->executeCommand("keyboard");
				
				return Request::sendMessage($data);
				
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
