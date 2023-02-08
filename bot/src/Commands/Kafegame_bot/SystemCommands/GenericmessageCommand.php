<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Config;

/**
 * Generic message command
 */
class GenericmessageCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'Genericmessage';

    /**
     * @var string
     */
    protected $description = 'Handle generic message';

    /**
     * @var string
     */
    protected $version = '1.1.0';

    /**
     * @var bool
     */
    protected $need_mysql = true;

    /**
     * Execution if MySQL is required but not available
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     */
    public function executeNoDb()
    {
        //Do nothing
        return Request::emptyResponse();
    }

    /**
     * Execute command
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
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
        $text    = trim($message->getText(true));
		
		if (in_array($type, ['audio', 'document', 'photo', 'video', 'voice'], true)) {
			$doc = call_user_func([$message, 'get' . ucfirst($type)]);
			($type === 'photo') && $doc = $doc[0];
				$file = Request::getFile(['file_id' => $doc->getFileId()]);
				if ($file->isOk()) {
					Request::downloadFile($file->getResult());
			}
		}
		
        //If a conversation is busy, execute the conversation command after handling the message
        $conversation = new Conversation(
            $this->getMessage()->getFrom()->getId(),
            $this->getMessage()->getChat()->getId()
        );
        //Fetch conversation command if it exists and execute it
        if ($conversation->exists() && ($command = $conversation->getCommand())) {
            return $this->telegram->executeCommand($command);
        } else if ($chat_type === 'private'){
			switch ($text){
				case 'لیست بازی ها':
				
					$Configer = new Config;
					$games_raw = $Configer->getRaw();
					$url_web_games = $games_raw['url_web'];
					$url_tg_games = $games_raw['url_tg'];
					$list_games = $games_raw['list'];
					$title_games = $games_raw['title'];
					//
					$Keys = array();
					foreach ($list_games as $key => $value){
						//array_push($Keys, [['text' => $title_games[$key], 'callback_game' => $list_games[$key], 'url' => $url_tg_games[$key]]]);
						array_push($Keys, ['text' => $title_games[$key], 'url' => $url_tg_games[$key]]);
					}
					$result = Request::sendMessage([
						'chat_id' => $chat_id,
						'text' => 'لطفا یکی از بازی های زیر را انتخاب کنید.',
						'reply_markup' => new InlineKeyboard($Keys),
						]);
					break;
				case 'امتیازات من':
					$result = Request::sendMessage([
						'chat_id' => $chat_id,
						'text' => 'ببخشید. هنوز آماده نشده',
						]);
					break;
				case 'پیشنهاد به دوستان':
					$result = Request::sendMessage([
						'chat_id' => $chat_id,
						'text' => 'پست زیر به صورت یکتا برای شما ساخته شده است'.PHP_EOL 
										. 'اگر هر شخصی با لینک این پست اپلیکیشن را دانلود کند بخشی از جایزه آن به شما می رسد',
						]);
					//
					$token = '?token=name~' .$user_Fname .'id~' .$user_id;
					$inline_keyboard = new InlineKeyboard([
						['text' => 'دانلود از سایت کافه گیم', 'url' => 'https://kafegame.com/app/' .$token],
					]);
					$result = Request::sendMessage([
						'chat_id' => $chat_id,
						'text' => 'سلام دوست من'.PHP_EOL 
										. 'من اپلیکیشن کافه گیم رو نصب کردم و کلی حال کردم.' .PHP_EOL 
										. 'به تو هم توصیه می کنم حتما اون رو نصب کنی' .PHP_EOL 
										. 'فقط کافیه رو دکمه زیر کلیک کنی  و فایل نصب رو دانلود کنی' .PHP_EOL 
										. 'مرسی' .PHP_EOL,
						'reply_markup' => $inline_keyboard,
						]);
					break;
				case 'سایت ما':
					$result = Request::sendMessage([
						'chat_id' => $chat_id,
						'text' => 'توی سایت میتونی بازی کنی و با بقیه قابت کنی.' .PHP_EOL 
										. 'یه امکان دیگه که توی سایت هست اینه که میتونی توی لیگ شرکت کنی که بعضی از اونا جایزه نقدی هم داره'
										.PHP_EOL .'آدرس اسیت ما اینه : '
										.PHP_EOL .'kafegame.com',
						]);
					break;
				case 'ساخت اکانت (سراسری)':
					$result = Request::sendMessage([
						'chat_id' => $chat_id,
						'text' => 'ببخشید. هنوز آماده نشده',
						]);
					break;
				case 'دانلود اپلیکیشن':
					$result = Request::sendMessage([
						'chat_id' => $chat_id,
						'text' => 'فعلا فقط نسخه اندروید آماده شده' .PHP_EOL 
										. 'این نسخه در مرحله ازمایشی هست و ممکنه اشکالاتی داشته باشه. اگر مایل به تست و بررسی این نسخه هستین از طریق آدرس زیر فایل نصب رو دانلود کنید'
										.PHP_EOL .'آدرس فایل نصبی : '
										.PHP_EOL .'kafegame.com/app',
						]);
					break;
				case 'کافه گیم چیه؟':
					$result = Request::sendMessage([
						'chat_id' => $chat_id,
						'text' => 'کافه گیم محلی برای لذت از بازی هست'.PHP_EOL 
										. 'اینجا میتونی اکانت بسازری و با همون اکانت توی اندروید، سایت و تلگرام بازی و رقابت کنی',
						]);
					break;
				case 'منوی اصلی':
					$keyboard = new Keyboard(
						[['text' => 'امتیازات من'], ['text' => 'لیست بازی ها']],
						[['text' => 'سایت ما'], ['text' => 'پیشنهاد به دوستان']],
						['ساخت اکانت (سراسری)'],
						['دانلود اپلیکیشن'],
						['کافه گیم چیه؟']
					);
					$keyboard = $keyboard
						->setResizeKeyboard(true)
						->setOneTimeKeyboard(true)
						->setSelective(false);
					$data = [
						'chat_id'      => $chat_id,
						'text'         => 'منوی اصلی :',
						'reply_markup' => $keyboard,
					];
					$result = Request::sendMessage($data);
					break;
				default:
					$result = Request::sendMessage([
						'chat_id' => $chat_id,
						'text' => 'متوجه نشدم یک بار دیگه تکرار کنید لطفا' .PHP_EOL . '(احتمالا دستور را اشتباه وارد کرده باشی) برای این که راحت باشی از منو استفاده کن',
						]);
					$keyboard = new Keyboard(
						[['text' => 'امتیازات من'], ['text' => 'لیست بازی ها']],
						[['text' => 'سایت ما'], ['text' => 'پیشنهاد به دوستان']],
						['ساخت اکانت (سراسری)'],
						['دانلود اپلیکیشن'],
						['کافه گیم چیه؟']
					);
					$keyboard = $keyboard
						->setResizeKeyboard(true)
						->setOneTimeKeyboard(true)
						->setSelective(false);
					$result = Request::sendMessage([
						'chat_id' => $chat_id,
						'text' => ':منوی اصلی',
						'reply_markup' => $keyboard,
						]);
					break;
			}
			
		}

        return Request::emptyResponse();
    }
}
