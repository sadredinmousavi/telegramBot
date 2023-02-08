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
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Request;

/**
 * User "/inlinekeyboard" command
 */
class MenuCommand extends UserCommand
{
    /**#@+
     * {@inheritdoc}
     */
    protected $name = 'menu';
    protected $description = 'نشان دادن منوی اصلی';
    protected $usage = '/menu';
    protected $version = '0.1.0';
    /**#@-*/

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $chat_id = $this->getMessage()->getChat()->getId();


        $keyboard = new Keyboard([
            ['text' => 'ساخت لینک اختصاصی ( برای معرفی)'],
        ], [
			['text' => 'امتیاز من'],
			['text' => 'ارسال لینک پیج'],
        //], [
        //    ['text' => 'ارتباط با ادمین'],
        ], [
            ['text' => 'راهنما'],
        ]);
		$keyboard->setResizeKeyboard(true)
				 ->setOneTimeKeyboard(true)
                 ->setSelective(false);

        $data = [
            'chat_id'      => $chat_id,
            'text'         => 'منو : ',
            'reply_markup' => $keyboard,
        ];

        return Request::sendMessage($data);
    }
}
