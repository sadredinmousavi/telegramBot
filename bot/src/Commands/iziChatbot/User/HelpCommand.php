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

use Longman\TelegramBot\Commands\Command;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;

/**
 * User "/help" command
 */
class HelpCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'help';

    /**
     * @var string
     */
    protected $description = 'راهنمایی برای کار با ربات و معرفی کلمات کلیدی';

    /**
     * @var string
     */
    protected $usage = '/help or /help <command>';

    /**
     * @var string
     */
    protected $version = '1.1.0';

    /**
     * Command execute method
     *
     * @return mixed
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute()
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();

        $message_id = $message->getMessageId();
        $command    = trim($message->getText(true));

        //Only get enabled Admin and User commands
        /** @var Command[] $command_objs */
        $command_objs = array_filter($this->telegram->getCommandsList(), function ($command_obj) {
            /** @var Command $command_obj */
            return !$command_obj->isSystemCommand() && $command_obj->isEnabled();
        });
		
		
		$can_continue = $this->getTelegram()->executeCommand("checker");
		if(!$can_continue){
			$d = ['chat_id' => $this->getMessage()->getChat()->getId(), 'text' => 'موقتا این دستور غیر فعال است.'];
			return Request::sendMessage($d);
		}

        //If no command parameter is passed, show the list
        if ($command === '' || $command === json_decode('"\u2753"') .' ' .'راهنما') {
            $text = 'این ربات یک چتروم عمومی محلی داره که افراد نزدیک خودت توی اون عضون.' .'(این چت ها به صورت ناشناس و با مشخصات مجازی هست)'
				   .PHP_EOL .'فقط باید موقعیت مکانی و شعاع چتروم رو انتخاب کنید.'
				   .PHP_EOL .'برای تنظیمات از دکمه ' .' "تنظیمات" ' .'در کیبورد و یا دستور'.' /settings ' .'استفاده کنید.' .PHP_EOL
				   .PHP_EOL .'برای چت خصوصی یک به یک و پیدا کردن دوست بر اساس موقعیت مکانی نوع شخصیت و . . . از دکمه' .' "چت ویژه" ' .'در کیبورد و یا دستور' .' /chat ' .'استفاده کنید.' .PHP_EOL
				   .PHP_EOL .'اگر می خواهید چتروم عمومی فعلا غیر فعال شود از دکمه' .' "حالت سکوت" ' .'در کیبورد و یا دستور' .' /deactive ' .'استفاده کنید.' .PHP_EOL
				   .PHP_EOL .'برای مشاهده نحوه ریپورت کردنو یا فالو کردم دیگران و اینکه تا کنون چه افرادی را فالو کردید و . . . از دستور'  .' /relation ' .'استفاده کنید.' .PHP_EOL
				   .PHP_EOL .'برای دریافت سکه رایگان ویا خرید سکه از دکمه ' .' "دریافت سکه" ' .'در کیبورد و یا دستور' .' /store ' .'استفاده کنید.' .PHP_EOL
				   .PHP_EOL .'برای انجام انواع تست های روانشناسی، شخصیت شناسی، سن عقلی و . . .  از دکمه' .' "تست های روانشناسی" ' .'در کیبورد و یا دستور' .' /test ' .'استفاده کنید.' .PHP_EOL
				   .PHP_EOL .'برای مشاهده تمام دستورات موجود و معرفی آن ها از دستور زیر را تایپ کنید.' .PHP_EOL .'/help all';
        } else if ($command === 'all'){
			//$text = sprintf(
            //    '%s v. %s' . PHP_EOL . PHP_EOL . 'لیست کامل دستورات :' . PHP_EOL,
            //    $this->telegram->getBotName(),
            //    $this->telegram->getVersion()
            //);

            foreach ($command_objs as $command) {
                if (!$command->showInHelp() || $command->getDescription() === ' ') {
                    continue;
                }

                $text .= sprintf(
                    '/%s - %s' . PHP_EOL,
                    $command->getName(),
                    $command->getDescription()
                );
            }

            //$text .= PHP_EOL . 'For exact command help type: /help <command>';
		} else {
            //$command = str_replace('/', '', $command);
            //if (isset($command_objs[$command])) {
            //    /** @var Command $command_obj */
            //    $command_obj = $command_objs[$command];
            //    $text = sprintf(
            //        'Command: %s v%s' . PHP_EOL .
            //        'Description: %s' . PHP_EOL .
            //        'Usage: %s',
            //        $command_obj->getName(),
            //        $command_obj->getVersion(),
            //        $command_obj->getDescription(),
            //        $command_obj->getUsage()
            //    );
            //} else {
            //    $text = 'No help available: Command /' . $command . ' not found';
            //} 
			$text = 'لطفا دستور را به شکل صحیح وارد کنید.';
        }

        $data = [
            'chat_id'             => $chat_id,
            'reply_to_message_id' => $message_id,
            'text'                => $text,
        ];
		$data['reply_markup'] = $this->getTelegram()->executeCommand("keyboard");

        return Request::sendMessage($data);
    }
}
