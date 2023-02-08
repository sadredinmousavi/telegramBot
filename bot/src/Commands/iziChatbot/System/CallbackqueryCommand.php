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
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Config;
use Longman\TelegramBot\MyDB;
use Longman\TelegramBot\DB;
use PDO;
use PDOException;
use Longman\TelegramBot\Entities\Chat;
use Longman\TelegramBot\Entities\PhotoSize;
use Longman\TelegramBot\Entities\UserProfilePhotos;


/**
 * Callback query command
 */
class CallbackqueryCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'callbackquery';

    /**
     * @var string
     */
    protected $description = 'Reply to callback query';

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
        $update                       = $this->getUpdate();
        $callback_query               = $update->getCallbackQuery();
		$callback_query_sender_id     = $callback_query->getFrom()->getId();
        $callback_query_id            = $callback_query->getId();
        $callback_data                = $callback_query->getData();
		//
		$callback_game_name           = $callback_query->getGameShortName();
		$callback_message             = $callback_query->getMessage();
		$callback_from                = $callback_query->getFrom();
		$callback_inline_message_id   = $callback_query->getInlineMessageId();
		$callback_chat_instance       = $callback_query->getChatInstance();
		//
		$Configer = new Config;
		
		//if (!empty($callback_game_name)){
		//	$url = $Configer->getUrlWeb($callback_game_name);
		//	if($url !== 'false'){
		//		$data = [
		//			'callback_query_id' => $callback_query_id,
		//			'url'               => $url,
		//		];
		//	} else {
		//		$data = [
		//			'callback_query_id' => $callback_query_id,
		//			'text'              => 'بازی ' .$callback_game_name .' موجود نمی باشد',
		//			'show_alert'        => $callback_data === 'thumb up',
		//			'cache_time'        => 5,
		//		];
		//	}
		//} else {
		//	$data = [
		//		'callback_query_id' => $callback_query_id,
		//		'text'              => 'Hello World! ' .$callback_game_name .'  ' .$callback_from,
		//		'show_alert'        => $callback_data === 'thumb up',
		//		'cache_time'        => 5,
		//	];
		//}
		
		if (stripos($callback_data, 'ban') === 0 && stripos($callback_data, 'banlist') !== 0){
			$text = substr($callback_data, 3);
			$command = 'ban';
		} else if (stripos($callback_data, 'unban') === 0){
			$text = substr($callback_data, 5);
			$command = 'unban';
		} else if (stripos($callback_data, 'report') === 0 && stripos($callback_data, 'reportlist') !== 0){
			$text = substr($callback_data, 6);
			$command = 'report';
		} else if (stripos($callback_data, 'unreport') === 0){
			$text = substr($callback_data, 8);
			$command = 'unreport';
		} else if (stripos($callback_data, 'follow') === 0 && stripos($callback_data, 'followings') !== 0 && stripos($callback_data, 'followers') !== 0){
			$text = substr($callback_data, 6);
			$command = 'follow';
		} else if (stripos($callback_data, 'unfollow') === 0){
			$text = substr($callback_data, 8);
			$command = 'unfollow';
		} else if (stripos($callback_data, 'banlist') === 0){
			$text = '';
			$command = 'banlist';
		} else if (stripos($callback_data, 'followings') === 0){
			$text = '';
			$command = 'followings';
		} else if (stripos($callback_data, 'followers') === 0){
			$text = '';
			$command = 'followers';
		}
		
		
			
			
			
			
		if ($text !== ''){

		
			//$id1 = $chat_id;
			$id1 = $callback_query_sender_id;
			$results = MyDB::selectChatsN('normal', ['users' => true], ['chat_id' => $id1]);
			if (!empty($results)) {
				$result = reset($results);
			}
			if (is_array($result)) {
				$dummy_id1        = $result['dummy_id'];		
			}
			$results = MyDB::selectChatsN('bydummy', ['users' => true], ['dummy_id' => $text]);
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
		
		
			$result = MyDB::HandleRelation ($id1, null, $id2, $command);

			if ($result){
				switch ($command){
					case 'ban':
						$text1 = 'کاربر با آیدی ' .$dummy_id2 .' توسط شما بلاک شد.';
						$text2 = 'یک کاربر شما را بلاک کرد. برای مدیریت روابط از دستور' .' /relation ' .'استفاده کنید.';
						break;
						
					case 'unban':
						$text1 = 'کاربر با آیدی ' .$dummy_id2 .' از بلاکی خارج شد.';
						$text2 = 'یک کاربر شما را از بلاکی  خارج کرد. برای مدیریت روابط از دستور' .' /relation ' .'استفاده کنید.';
						break;
						
					case 'report':
						$text1 = 'کاربر با آیدی ' .$dummy_id2 .' توسط شما ریپورت شد.';
						$text2 = 'یک کاربر شما را ریپورت کرد. برای مدیریت روابط از دستور' .' /relation ' .'استفاده کنید.';
						break;
						
					case 'unreport':
						$text1 = 'کاربر با آیدی ' .$dummy_id2 .' رفع ریپورت شد.';
						$text2 = 'یک کاربر شما را از ریپورتی  خارج کرد. برای مدیریت روابط از دستور' .' /relation ' .'استفاده کنید.';
						break;
						
					case 'follow':
						$text1 = 'شما کاربر با آیدی ' .$dummy_id2 .' را فالو کردید.';
						$text2 = 'کاربر با آیدی' .' /u' .$dummy_id1 .' شما را فالو کرد. برای مدیریت روابط از دستور ' .' /relation ' .'استفاده کنید.';
						break;
						
					case 'unfollow':
						$text1 = 'شما کاربر با آیدی ' .$dummy_id2 .' را آنفالو کردید.';
						$text2 = 'کاربر با آیدی' .' /u' .$dummy_id1 .' شما را آنفالو کرد. برای مدیریت روابط از دستور ' .' /relation ' .'استفاده کنید.';
						break;
					default:
				}
				//$data1 = [
				//	'chat_id'  =>  $id1,
				//	'text'         => $text1,
				//];
				//$res = Request::sendMessage($data1);
				
				$data2 = [
					'chat_id'  =>  $id2,
					'text'         => $text2,
				];
				$res = Request::sendMessage($data2);
			} else {
				$text1 = 'خطا در انجام عملیات. لطفا مجددا تلاش نمایید.';
			}
		}
	
		$data = [
			'callback_query_id' => $callback_query_id,
			'text'              => $text1,
			'show_alert'        => true,
			'cache_time'        => 5,
		];
		

        return Request::answerCallbackQuery($data);
    }
}
