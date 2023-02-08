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

/**
 * User "/inlinekeyboard" command
 */
class SendpageCommand extends UserCommand
{
    /**#@+
     * {@inheritdoc}
     */
    protected $name = 'sendpage';
    protected $description = 'ارسال پیج اینستاگرام برای دانلود پست ها';
    protected $usage = '/sendpage';
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
            'chat_id'      => '@Position',
			'user_id'      => $user_id,
        ];
		$Result = Request::getChatMember($data);
		$Results = $Result->getResult();
		if(!empty($Results)){
			$user_status = $Results->getStatus();
		} else {
			$inline_keyboard = new InlineKeyboard([
				['text' => 'عضویت در کانال ', 'url' => 'https://t.me/joinchat/AAAAAD8ppY04HTib-hBn3A'],
			]);
			$text = 'شما هنوز عضو کانال نشده اید' .PHP_EOL 
				.'برای کار با ربات باید ایتدا در کانال عضو شوید' .PHP_EOL .PHP_EOL .PHP_EOL;
			$data = [
				'chat_id'      => $chat_id,
				'text'         => $text,
				'reply_markup' => $inline_keyboard,
			];
			return Request::sendMessage($data);			
		}
		
		
		
		if ($user_status == 'member' || $user_status == 'creator' || $user_status == 'administrator'){
			//$score = $this->getTelegram()->executeCommand("myscore");
			
		} else if ($user_status == 'left' || $user_status == 'kicked'){
			$inline_keyboard = new InlineKeyboard([
				['text' => 'عضویت در کانال ', 'url' => 'https://t.me/joinchat/AAAAAD8ppY04HTib-hBn3A'],
			]);
			$text = 'شما از کانال خارج شده اید' .PHP_EOL 
				.'برای کار با ربات باید ایتدا در کانال عضو شوید' .PHP_EOL .PHP_EOL .PHP_EOL;
			$data = [
				'chat_id'      => $chat_id,
				'text'         => $text,
				'reply_markup' => $inline_keyboard,
			];
			return Request::sendMessage($data);	
		}
		
		
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
				//if ($message->getPhoto() === null) {
				if (stripos($text_m, 'instagram.com/') === false) {
						$notes['state'] = 1;
						$this->conversation->update();
						
						$data = [
							'chat_id'      => $chat_id,
							'text'         => 'لطفا آدرس پیج مورد نظر را بفرستید' .PHP_EOL .'به عنوان مثال به فرمت زیر باشد' .PHP_EOL .'https://instagram.com/9gag',
						];
						
						$result = Request::sendMessage($data);
						break;
					}

					/** @var PhotoSize $photo */
					//$photo             = $message->getPhoto()[0];
					//$notes['photo_id'] = $photo->getFileId();

				// no break
			case 1:
				//if ($message->getPhoto() === null) {
				if (stripos($text_m, 'instagram.com/') === false) {
						$notes['state'] = 1;
						$this->conversation->update();

						$data = [
							'chat_id'      => $chat_id,
							'text'         => 'متاسفانه پیغام شما حاوی آدرس صحیح یک پیج نیست!',
						];


						$result = Request::sendMessage($data);
						break;
					}

					/** @var PhotoSize $photo */
					//$photo             = $message->getPhoto()[0];
					$notes['link'] = $text_m;

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
					['text' => 'مشاهده پست ها'],
				]);
				$keyboard->setResizeKeyboard(true)
						 ->setOneTimeKeyboard(true)
						 ->setSelective(false);
				$text = 'پست های این پیج به درستی دریافت شده است' .$score .PHP_EOL 
					.'برای مشاهده پست ها دکمه زیر و یا دستور ' .PHP_EOL
					.'/showpage' .PHP_EOL 
					.'رو بزن' .PHP_EOL;
				$data = [
					'chat_id'      => $chat_id,
					'text'         => $text,
					'reply_markup' => $keyboard,
				];

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

        return Request::sendMessage($data);
    }
}
