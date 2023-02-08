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
		


        //If no command parameter is passed, show the list
        if ($command === '' || $command === json_decode('"\u2753"') .' ' .'راهنما') {
            $text = 'این ربات پیامای شما رو تغییر میده که میتونین تعداد بازدیدهاش رو ببینین'
				   .PHP_EOL .'حالا اگه اون رو برای دوستتون بفرستین حتما میفهمین اون رو خونده یا نه حتی اگه تلگرامش حالت روح داشته باشه.'
				   .PHP_EOL .'فقط و فقط کافیه که پیام رو برای ربات تایپ کنین یا بفرستین' .PHP_EOL
				   .PHP_EOL .'پیامتون میتونه متن، فیلم ، عکس  یا استیکر و صوت باشه' .PHP_EOL
				   .PHP_EOL .PHP_EOL .'اگر پیامتون 2 تا بازدید داشته باشه یعنی فقط خودتون و ربات اونو دیدین'
				   .PHP_EOL .'اگه بازدید بشه 3 تا یعنی دوستتون هم پیام رو خونده.' .json_decode('"\uD83E\uDD13"')
				   .PHP_EOL .'اگه خوشتون اومد و خواستین ما رو معرفی کنین از دستور' .' /banner ' .'استفاده کنید.' .json_decode('"\uD83C\uDF39"') .json_decode('"\uD83C\uDF39"').PHP_EOL;
				   
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

        return Request::sendMessage($data);
    }
}
