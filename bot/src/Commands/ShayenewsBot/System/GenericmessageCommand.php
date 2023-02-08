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
				case 'ساخت لینک اختصاصی ( برای معرفی)':
					return $this->getTelegram()->executeCommand("share");
					break;
				case 'امتیاز من':
					return $this->getTelegram()->executeCommand("myscore");
					break;
				case 'دریافت  کد شارژ':
					return $this->getTelegram()->executeCommand("mygift");
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
				case 'ارتباط با ادمین':
					return $this->getTelegram()->executeCommand("contact");
					break;
				case 'راهنما':
					return $this->getTelegram()->executeCommand("help");
					break;
				default:
					$result = Request::sendMessage([
						'chat_id' => $chat_id,
						'text' => 'دستور نا معتبر' .PHP_EOL . 'لطفا از منو استفاده کن و یا راهنما رو بزن',
						]);
					return $this->getTelegram()->executeCommand("menu");
					break;
			}
			
		} else if ($text == 'شایعه'){
			$text = 'سلام'.PHP_EOL 
				.'هنوز در حال آماده سازیم' .PHP_EOL  .PHP_EOL 
				.'صبر کن.. به زودی سایعه تپل میارم';
			$data = [
				'chat_id'      => $chat_id,
				'text'         => $text,
			];
			$result = Request::sendMessage($data);
		}

        return Request::emptyResponse();
    }
}
