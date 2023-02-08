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
use Longman\TelegramBot\DB;
use Longman\TelegramBot\MyDB;
use PDO;
use PDOException;
use Longman\TelegramBot\Entities\Chat;
use Longman\TelegramBot\Entities\PhotoSize;
use Longman\TelegramBot\Entities\UserProfilePhotos;
use Longman\TelegramBot\Request;

/**
 * Admin "/whois" command
 */
class RelationCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'relation';

    /**
     * @var string
     */
    protected $description = 'لیستی از عملیات مرتبط با دوستان، ریپورت و فالوورها';

    /**
     * @var string
     */
    protected $usage = '/relation';

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
		
		
		$can_continue = $this->getTelegram()->executeCommand("checker");
		if(!$can_continue){
			$d = ['chat_id' => $this->getMessage()->getChat()->getId(), 'text' => 'موقتا این دستور غیر فعال است.'];
			return Request::sendMessage($d);
		}
		

        $data = ['chat_id' => $chat_id];

        //No point in replying to messages in private chats
        if (!$message->getChat()->isPrivateChat()) {
            $data['reply_to_message_id'] = $message->getMessageId();
        }

        if ($command !== 'relation') {
			if (stripos($command, 'ban') === 0 && stripos($command, 'banlist') !== 0){
				$text = substr($command, 3);
				$command = 'ban';
			} else if (stripos($command, 'unban') === 0){
				$text = substr($command, 5);
				$command = 'unban';
			} else if (stripos($command, 'report') === 0 && stripos($command, 'reportlist') !== 0){
				$text = substr($command, 6);
				$command = 'report';
			} else if (stripos($command, 'unreport') === 0){
				$text = substr($command, 8);
				$command = 'unreport';
			} else if (stripos($command, 'follow') === 0 && stripos($command, 'followings') !== 0 && stripos($command, 'followers') !== 0){
				$text = substr($command, 6);
				$command = 'follow';
			} else if (stripos($command, 'unfollow') === 0){
				$text = substr($command, 8);
				$command = 'unfollow';
			} else if (stripos($command, 'banlist') === 0){
				$text = '';
				$command = 'banlist';
			} else if (stripos($command, 'followings') === 0){
				$text = '';
				$command = 'followings';
			} else if (stripos($command, 'followers') === 0){
				$text = '';
				$command = 'followers';
			}
            

            //We need that '-' now, bring it back
            //if (strpos($text, 'g') === 0) {
            //    $text = str_replace('g', '-', $text);
            //}
        } else {
			
			$data['text'] = 'مدیریت دوستان، ریپورت و بلاک'
					.PHP_EOL .'/banlist - ' .'مشاهده و تغییر افرادی که بلاک کردید'
					.PHP_EOL .'/followers - ' .'مشاهده افرادی که شما را فالو کردند'
					.PHP_EOL .'/followings - ' .'مشاهده و تغییر افرادی که فالو کردید'
					.PHP_EOL .'/reportlist - ' .'مشاهده و تغییر افرادی که ریپورت کردید';
			return Request::sendMessage($data);
		}
		
		if (!DB::isDbConnected()) {
			return false;
		}
			
		$id1 = $chat_id;
		$results = MyDB::selectChatsN('normal', ['users' => true], ['chat_id' => $id1]);
		if (!empty($results)) {
			$result = reset($results);
		}
		if (is_array($result)) {
			$dummy_id1        = $result['dummy_id'];		
		}
		$results = MyDB::selectChatsN('bydummy', ['users' => true], ['chat_id' => $text]);
		if (!empty($results)) {
			$result = reset($results);
		}
		if (is_array($result)) {
			$id2             = $result['id'];							
			$dummy_id2       = $result['dummy_id'];
			$dummy_username  = $result['dummy_username'];
			$dummy_photoid   = $result['dummy_photoid'];
			$location        = $result['location'];
			$personal_type   = $result['personal_type'];
			$host            = $result['host']; 
			$gender          = $result['gender']; 
			$coins           = $result['coins']; 
			$premium         = $result['premium']; 
			$active          = $result['active']; 			
		}
			
			
			
			
		if ($text !== ''){

			$result = MyDB::HandleRelation ($id1, null, $id2, $command);

			if ($result){
				switch ($command){
					case 'ban':
						$text = 'کاربر با آیدی ' .$dummy_id2 .' توسط شما بلاک شد.';
						$text2 = 'یک کاربر شما را بلاک کرد. برای مدیریت روابط از دستور' .' /relation ' .'استفاده کنید.';
						break;
						
					case 'unban':
						$text = 'کاربر با آیدی ' .$dummy_id2 .' از بلاکی خارج شد.';
						$text2 = 'یک کاربر شما را از بلاکی  خارج کرد. برای مدیریت روابط از دستور' .' /relation ' .'استفاده کنید.';
						break;
						
					case 'report':
						$text = 'کاربر با آیدی ' .$dummy_id2 .' توسط شما ریپورت شد.';
						$text2 = 'یک کاربر شما را ریپورت کرد. برای مدیریت روابط از دستور' .' /relation ' .'استفاده کنید.';
						break;
						
					case 'unreport':
						$text = 'کاربر با آیدی ' .$dummy_id2 .' رفع ریپورت شد.';
						$text2 = 'یک کاربر شما را از ریپورتی  خارج کرد. برای مدیریت روابط از دستور' .' /relation ' .'استفاده کنید.';
						break;
						
					case 'follow':
						$text = 'شما کاربر با آیدی ' .$dummy_id2 .' را فالو کردید.';
						$text2 = 'کاربر با آیدی' .' /u' .$dummy_id1 .' فالو کرد. برای کدیریت روابط از دستور ' .' /relation ' .'استفاده کنید.';
						break;
						
					case 'unfollow':
						$text = 'شما کاربر با آیدی ' .$dummy_id2 .' را آنفالو کردید.';
						$text2 = 'کاربر با آیدی' .' /u' .$dummy_id1 .' آنفالو کرد. برای کدیریت روابط از دستور ' .' /relation ' .'استفاده کنید.';
						break;
					default:
				}
				$data2 = [
					'chat_id'  =>  $id2,
					'text'         => $text2,
				];
				$res = Request::sendMessage($data2);
			} else {
				$text = 'خطا در انجام عملیات. لطفا مجددا تلاش نمایید.';
			}
		} else {
			$relations = MyDB::HandleRelation ($id1, $dummy_id1);
			$Ic = 0;
			$Uc = 0;
			switch ($command){
				case 'banlist':
					$Iban = [];
					$Uban = [];
					foreach($relations as $relation){
						if($relation['ban'] === 'ban'){
							if ($relation['id1'] === $chat_id ) {
								$id2 = $relation['id2'];
							} else {
								$id2 = $relation['id1'];
							}
							$results = MyDB::selectChatsN('normal', ['users' => true], ['chat_id' => $id2]);
							if (!empty($results)) {
								$result = reset($results);
							}
							if (is_array($result)) {
								$dummy_id2        = $result['dummy_id'];
								$dummy_username	  = $result['dummy_username'];
							}
							//
							if ($relation['id1'] == $id1){
								$Ic++;
								$Iban []= '/u' .$dummy_id2 .' (' .$dummy_username .') - [/unban' .$dummy_id2 .']';
							} else {
								$Uc++;
								$Uban []= '/u' .$dummy_id2 .' (' .$dummy_username .') - [/ban' .$dummy_id2 .']';
							}
						}
					}
					$text = 'لیست افرادی که شمابلاک کردید :' .' (' .$Ic .' نفر)' .PHP_EOL 
							.implode(PHP_EOL ,$Iban) .PHP_EOL .PHP_EOL
							.'افرادی که شما را بلاک کرده اند :' .' (' .$Uc .' نفر)' .PHP_EOL 
							.implode(PHP_EOL ,$Uban) .PHP_EOL .PHP_EOL;
					break;
						
				case 'followers':
					$Iban = [];
					$Uban = [];
					foreach($relations as $relation){
						if($relation['follow'] === 'follow'){
							if ($relation['id1'] === $chat_id ) {
								$id2 = $relation['id2'];
							} else {
								$id2 = $relation['id1'];
							}
							$results = MyDB::selectChatsN('normal', ['users' => true], ['chat_id' => $id2]);
							if (!empty($results)) {
								$result = reset($results);
							}
							if (is_array($result)) {
								$dummy_id2        = $result['dummy_id'];
								$dummy_username	  = $result['dummy_username'];
							}
							//
							if ($relation['id1'] == $id1){
								$Ic++;
								$Iban []= '/u' .$dummy_id2 .' (' .$dummy_username .') - [/unfollow' .$dummy_id2 .']';
							} else {
								$Uc++;
								$Uban []= '/u' .$dummy_id2 .' (' .$dummy_username .') - [/follow' .$dummy_id2 .']';
							}
						}
					}
					//$text = 'لیست افرادی که فالو کردید :'.' (' .$Ic .' نفر)' .PHP_EOL 
					//		.implode(PHP_EOL ,$Iban) .PHP_EOL .PHP_EOL
					//		.'افرادی که شما را فالو کرده اند :' .' (' .$Uc .' نفر)' .PHP_EOL 
					//		.implode(PHP_EOL ,$Uban) .PHP_EOL .PHP_EOL;
					$text = 'افرادی که شما را فالو کرده اند :' .' (' .$Uc .' نفر)' .PHP_EOL 
							.implode(PHP_EOL ,$Uban) .PHP_EOL .PHP_EOL;
					break;
				case 'followings':
					$Iban = [];
					$Uban = [];
					foreach($relations as $relation){
						if($relation['follow'] === 'follow'){
							if ($relation['id1'] === $chat_id ) {
								$id2 = $relation['id2'];
							} else {
								$id2 = $relation['id1'];
							}
							$results = MyDB::selectChatsN('normal', ['users' => true], ['chat_id' => $id2]);
							if (!empty($results)) {
								$result = reset($results);
							}
							if (is_array($result)) {
								$dummy_id2        = $result['dummy_id'];
								$dummy_username	  = $result['dummy_username'];
							}
							//
							if ($relation['id1'] == $id1){
								$Ic++;
								$Iban []= '/u' .$dummy_id2 .' (' .$dummy_username .') - [/unfollow' .$dummy_id2 .']';
							} else {
								$Uc++;
								$Uban []= '/u' .$dummy_id2 .' (' .$dummy_username .') - [/follow' .$dummy_id2 .']';
							}
						}
					}
					$text = 'لیست افرادی که فالو کردید :' .' (' .$Ic .' نفر)' .PHP_EOL 
							.implode(PHP_EOL ,$Iban) .PHP_EOL .PHP_EOL;
							//.'افرادی که شما را فالو کرده اند :' .' (' .$Uc .' نفر)' .PHP_EOL 
							//.implode(PHP_EOL ,$Uban) .PHP_EOL .PHP_EOL;
					break;
				case 'reportlist':
					$Iban = [];
					$Uban = [];
					foreach($relations as $relation){
						if($relation['report'] === 'report'){
							if ($relation['id1'] === $chat_id ) {
								$id2 = $relation['id2'];
							} else {
								$id2 = $relation['id1'];
							}
							$results = MyDB::selectChatsN('normal', ['users' => true], ['chat_id' => $id2]);
							if (!empty($results)) {
								$result = reset($results);
							}
							if (is_array($result)) {
								$dummy_id2        = $result['dummy_id'];
								$dummy_username	  = $result['dummy_username'];
							}
							//
							if ($relation['id1'] == $id1){
								$Ic++;
								$Iban []= '/u' .$dummy_id2 .' (' .$dummy_username .') - [/unreport' .$dummy_id2 .']';
							} else {
								$Uc++;
								$Uban []= '/u' .$dummy_id2 .' (' .$dummy_username .') - [/follow' .$dummy_id2 .']';
							}
						}
					}
					$text = 'لیست افرادی که ریپورت کردید :' .' (' .$Ic .' نفر)' .PHP_EOL 
							.implode(PHP_EOL ,$Iban) .PHP_EOL .PHP_EOL
							.'افرادی که شما را ریپورت کرده اند :' .' (' .$Uc .' نفر)' .PHP_EOL;
							//.implode(PHP_EOL ,$Uban) .PHP_EOL .PHP_EOL;
					break;
				default:
					$text = 'دستور نا معتبر است.';
			}
		}
		
		
        $text .= PHP_EOL .'برای مدیریت دوستان، ریپورت و بلاک از دستور' .' /relation ' .'استفاده کنید.';

        $data['text'] = $text;// .json_encode($relations);//. implode(' ,',$relations);

        return Request::sendMessage($data);
    }
}
