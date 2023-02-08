<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Written by Jack'lul <jacklul@jacklul.com>
 */

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\MyDB;
use Longman\TelegramBot\Entities\Chat;
use Longman\TelegramBot\Entities\PhotoSize;
use Longman\TelegramBot\Entities\UserProfilePhotos;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;


/**
 * Admin "/whois" command
 */
class KeyboardCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = ' ';

    /**
     * @var string
     */
    protected $description = ' ';

    /**
     * @var string
     */
    protected $usage = ' ';

    /**
     * @var string
     */
    protected $version = '1.2.0';

    /**
     * @var bool
     */
    protected $need_mysql = true;

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
        $command = $message->getCommand();
        $text    = trim($message->getText(true));

        //$data = ['chat_id' => $chat_id];


        //if ($command !== 'u') {
            //$text = substr($command, 1);

            //We need that '-' now, bring it back
            //if (strpos($text, 'g') === 0) {
            //    $text = str_replace('g', '-', $text);
            //}
        //}

		//$data2 = [
        //    'chat_id'      => $chat_id,
        //    'text'         => $text,
        //];
        //$Res = Request::sendMessage($data2);
		

		//$results = DB::selectChats($group_search, $supergroup_search, $private_search, null, null, $chat_id);
		$selection = ['users' => true, 'groups' => true, 'super_groups' => true];
		$results = MyDB::selectChatsN('normal', $selection, ['chat_id' => $chat_id]);
        if (!empty($results)) {
            $result = reset($results);
        }
        if (is_array($result)) {
            $result['id'] = $result['chat_id'];
			$chat         = new Chat($result);

			$user_id    = $result['id'];
			$created_at = $result['chat_created_at'];
			$updated_at = $result['chat_updated_at'];
			$old_id     = $result['old_id'];
				
				
			$dummy_id        = $result['dummy_id'];
			$dummy_username  = $result['dummy_username'];
			$dummy_photoid   = $result['dummy_photoid'];
			$lat             = $result['lat'];
			$lng			 = $result['lng'];
			$personal_type   = $result['personal_type'];
			$host            = $result['host']; 
			$gender          = $result['gender']; 
			$coins           = $result['coins']; 
			$premium         = $result['premium']; 
			$active          = $result['active']; 			
        }

        //$data['text'] = $text;

        //return Request::sendMessage($data);
		
		$keyboard = new Keyboard([
			['text' => json_decode('"\uD83D\uDCCD"')],//pin"\uD83D\uDCCD"//flag"\uD83D\uDEA9"
			['text' => json_decode('"\uD83D\uDCE1"')],
			['text' => json_decode('"\uD83D\uDD0E"')],
			['text' => json_decode('"\uD83D\uDCF1"')],
			['text' => json_decode('"\u2699"')],
			['text' => json_decode('"\uD83D\uDD07"')],//spk-off"\uD83D\uDD07"//spk-3wv"\uD83D\uDD0A"//spk-1wv"\uD83D\uDD09"
		]);
		if ($active === '1'){
			$t = json_decode('"\uD83D\uDD07"') .' ' .'سکوت';
		} else {
			$t = json_decode('"\uD83D\uDD0A"') .' ' .'فعال کردن';
		}
		
		$keyboard = new Keyboard([
			['text' => json_decode('"\u2753"') .' ' .'راهنما'],
			['text' => json_decode('"\uD83D\uDC69\u200D\u2764\uFE0F\u200D\uD83D\uDC68"') .' ' .'چت ویژه'],//"\uD83D\uDD0E"
			['text' => $t],
		], [
			['text' => json_decode('"\u2699"') .' ' .'تنظیمات'],
			['text' => json_decode('"\uD83D\uDC68\u200D\uD83D\uDC69\u200D\uD83D\uDC67\u200D\uD83D\uDC67"') .' ' .'تست های روانشناسی'],//
			['text' => json_decode('"\uD83D\uDCB0"') .' ' .'دریافت سکه'],
		]);
		
		$keyboard->setResizeKeyboard(true)
				 ->setOneTimeKeyboard(true)
				 ->setSelective(false);
				 
		return $keyboard;
    }
}
