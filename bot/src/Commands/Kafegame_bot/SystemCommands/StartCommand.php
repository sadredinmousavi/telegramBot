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

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Request;

/**
 * Start command
 */
class StartCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'start';

    /**
     * @var string
     */
    protected $description = 'Start command';

    /**
     * @var string
     */
    protected $usage = '/start';

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
		
		$switch_element = '';
        
		$inline_keyboard = new InlineKeyboard([
            ['text' => 'بازی در گروه', 'switch_inline_query' => $switch_element],
            ['text' => 'انتخاب بازی', 'switch_inline_query_current_chat' => $switch_element],
        ], [
            ['text' => 'ساخت اکانت'],
            ['text' => 'دانلود اپلیکیشن', 'url' => 'https://kafegame.com/app'],
        ], [
            ['text' => 'سایت کافه گیم', 'url' => 'https://kafegame.com'],
        ]);
		
		$result = Request::sendMessage([
			'chat_id' => $chat_id,
			'text' => 'سلام. من ربات کافه گیم هستم. ' .PHP_EOL . 'اینجا هم میتونی بازی کنی و هم با دوستات رقابت کنی',
			'reply_markup' => $inline_keyboard,
			]);
			
		//
		//
		//
		//
        
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

        return Request::sendMessage($data);
    }
}
