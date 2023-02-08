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
use Longman\TelegramBot\Entities\InlineKeyboard;

/**
 * User "/help" command
 */
class ListCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'list';

    /**
     * @var string
     */
    protected $description = 'راهنمایی برای کار با ربات و معرفی کلمات کلیدی';

    /**
     * @var string
     */
    protected $usage = '/list';

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


       

		$text  = 'هر بازی رو که خوشت اومد انتهاب کن.';
		$text .= PHP_EOL .'بعد صفحه انختاب چت باز میشه';
		$text .= PHP_EOL .'هر چتی رو انتخاب کردی بازی توی اون چت ساخته میشه' .PHP_EOL .PHP_EOL;
		
        $data = [
            'chat_id'             => $chat_id,
            'text'                => $text,
			'reply_markup' => new InlineKeyboard([
													['text' => 'بازی دوز کوچک', 'switch_inline_query' => 'g1'],
												], [
													['text' => 'بازی دوز بزرگ', 'switch_inline_query' => 'g2'],
												], [
													['text' => 'بازی سنگ - کاغذ - قیچی', 'switch_inline_query' => 'g4'],
												], [
													['text' => 'بازی رولت روسی', 'switch_inline_query' => 'g6'],
												]),
        ];

        return Request::sendMessage($data);
    }
}
