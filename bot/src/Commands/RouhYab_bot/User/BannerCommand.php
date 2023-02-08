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
class BannerCommand extends UserCommand
{
    /**#@+
     * {@inheritdoc}
     */
    protected $name = 'banner';
    protected $description = 'دریافت بنر اختصاصی برای اضافه کردن عضو و کسب امتیاز';
    protected $usage = '/banner';
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
			['text' => 'اضافه کردن ربات به گروه', 'url' => 't.me/iziChargeBot?startgroup=' .base64_encode($user_id)],
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

        //$result = Request::sendMessage($data);
		
		$photo_id = [
			['file_id'=>'AgADBAADP6kxG7MOQVD9f611vgorobDUnBkABNAYcdIbI0puutQDAAEC','file_size'=>2390,'width'=>90,'height'=>90],
			['file_id'=>'AgADBAADP6kxG7MOQVD9f611vgorobDUnBkABILyjOTYkyRRudQDAAEC','file_size'=>31706,'width'=>320,'height'=>320],
			['file_id'=>'AgADBAADP6kxG7MOQVD9f611vgorobDUnBkABPmleOOkGUYqu9QDAAEC','file_size'=>137467,'width'=>800,'height'=>800],
			['file_id'=>'AgADBAADP6kxG7MOQVD9f611vgorobDUnBkABFp1Ov0Z2KCkuNQDAAEC','file_size'=>249235,'width'=>1280,'height'=>1280]
		];
		$photo_id = '[{"file_id":"AgADBAADkqkxG5ZTQFAXlo-wAAF_1c6J8ZwZAAQQ3gABQYs1caltzAMAAQI","file_size":1367,"width":90,"height":51}]';
		$text = json_decode('"\u270B"') .'با این بات میتونی بفهمی پیامات خونده میشه یا نه. حتی اگه تلگرام دوستت حالت روح داشته باشه.' .json_decode('"\uD83D\uDC7B"') .json_decode('"\uD83D\uDC7B"').PHP_EOL .PHP_EOL
			.'t.me/RouhYab_bot?start=' .base64_encode($user_id) .PHP_EOL .PHP_EOL;
        $data = [
            'chat_id'      => $chat_id,
            'photo'         => 'AgADBAAD3agxG3XX8FKeA5JKh28ZNhKkuxkABKnMiKgHwpGusdECAAEC',//'AgADBAADVKkxG_Mv6FK77gTHa4oyva7hvBkABGhHkqqMeU80EcgCAAEC',//'AgADBAADX6kxG_Mv4FLo55LQn1TXcSQ1qRkABLVy_-5A95erpTsDAAEC',
			'caption'         => $text,
        ];

        return Request::sendPhoto($data);
		//$data = [
        //    'chat_id'      => $chat_id,
        //    'text'         => $re,
        //];
		//$result = Request::sendMessage($data);
    }
}
