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
class ContactCommand extends UserCommand
{
    /**#@+
     * {@inheritdoc}
     */
    protected $name = 'contact';
    protected $description = 'راهکار ارتباط با ادمین - برای صحبت، پیشنهاد و انقاد';
    protected $usage = '/contact';
    protected $version = '0.1.0';
    /**#@-*/

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $chat_id = $this->getMessage()->getChat()->getId();
		$inline_keyboard = new InlineKeyboard([
			['text' => 'چت خصوصی با من', 'url' => 't.me/shayeeParakan'],
			['text' => 'کانال دوستم', 'url' => 't.me/Shayenews'],
		]);
		$text = 'سلام دوست من'.PHP_EOL 
			.'ما جمعی از بچه های شریفیم که به کد نویسی علاقه داریم' .PHP_EOL 
			.'این پروژه رو به صورت آزمایشی درست کردیم' .PHP_EOL 
			.'امیدواریم که خوشت بیاد و  با شارژ کارت راه بیوفته' .PHP_EOL .PHP_EOL .PHP_EOL
			.'اگه صحبت، پیشنهاد، انفاد، شکایت و یا تشویقی داری میتونی به روابط عمومی ما بگی' .PHP_EOL 
			.'خیلی ممنون' .PHP_EOL
			.'@shayeeParakan' .PHP_EOL;
        $data = [
            'chat_id'      => $chat_id,
            'text'         => $text,
			'reply_markup' => $inline_keyboard,
        ];

        return Request::sendMessage($data);
    }
}
