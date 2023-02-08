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

/**
 * User "/inlinekeyboard" command
 */
class ShareCommand extends UserCommand
{
    /**#@+
     * {@inheritdoc}
     */
    protected $name = 'share';
    protected $description = 'توصیه ربات به دوستان و دریافت اعتبار برای شارژ';
    protected $usage = '/share';
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
		
		
		$inline_keyboard = new InlineKeyboard([
			['text' => 'اضافه کردن ربات به گروه', 'url' => 't.me/ShayenewsBot?startgroup=' .base64_encode($user_id)],
		]);
		$text = 'پست زیر به صورت یکتا برای شما ساخته شده است'.PHP_EOL 
			.'هر تعداد کاربر که با این لینک وارد ربات شوند به نام شما ثبت شده و امتیاز آن برای شما مقدور می شود' .PHP_EOL  .PHP_EOL 
			.'همچنین اکر با لینک دکمه زیر ربات را به گروه هایی که در آن حضور دارید اضافه کنید امتیاز آن برای شما حساب می شود' .PHP_EOL .PHP_EOL .PHP_EOL
			.'برای اینکه از امتیاز خودتون مطلع بشین کافیه از منو گزینه امتیاز من یا دستور زیر رو بزنید.' .PHP_EOL
			.'/myscore' .PHP_EOL
			.'امیدوارم امتیاز شما زودتر بالا بره تا سریع تر شارژ رایگان خود را دریافت کنید' .PHP_EOL;
        $data = [
            'chat_id'      => $chat_id,
            'text'         => $text,
			'reply_markup' => $inline_keyboard,
        ];

        $result = Request::sendMessage($data);
		
		
		$text = 'سلام'.PHP_EOL 
			.'من ربات شارژ رایگان هستم' .PHP_EOL 
			.'با لینک زیر بیا تو ربات و با انجام کاری که بهت میکم شارژ رایگان بگیر' .PHP_EOL .PHP_EOL .PHP_EOL
			.'t.me/ShayenewsBot?start=' .base64_encode($user_id) .PHP_EOL .PHP_EOL;
        $data = [
            'chat_id'      => $chat_id,
            'text'         => $text,
        ];

        return Request::sendMessage($data);
    }
}
