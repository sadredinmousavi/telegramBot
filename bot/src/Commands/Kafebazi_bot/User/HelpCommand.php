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


        //If no command parameter is passed, show the list
        
        $text = sprintf(
            '%s v. %s' . PHP_EOL . PHP_EOL . 'لیست دستورات :' . PHP_EOL,
            $this->telegram->getBotName(),
            $this->telegram->getVersion()
        );



        $text .= PHP_EOL .'/menu  ' .'مشاهده منو';
		$text .= PHP_EOL .'/list  ' .'لیست بازی ها' .PHP_EOL .PHP_EOL;


        $text .= PHP_EOL . 'در صورتی که هر گونه نظر، پیشنهاد و انتقادی دارید با آیدی زیر مطرح نمایید.';
		$text .= PHP_EOL . 'با تشکر';
		$text .= PHP_EOL . '@kafebazi';
        

        $data = [
            'chat_id'             => $chat_id,
            'reply_to_message_id' => $message_id,
            'text'                => $text,
        ];

        return Request::sendMessage($data);
    }
}
