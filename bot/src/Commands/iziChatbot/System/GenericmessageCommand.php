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

use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Config;
use Longman\TelegramBot\DB;
use Longman\TelegramBot\MyDB;
use Longman\TelegramBot\Entities\Chat;

/**
 * Generic message command
 */
class GenericmessageCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'Genericmessage';

    /**
     * @var string
     */
    protected $description = 'Handle generic message';

    /**
     * @var string
     */
    protected $version = '1.1.0';

    /**
     * @var bool
     */
    protected $need_mysql = true;

    /**
     * Execution if MySQL is required but not available
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     */
    public function executeNoDb()
    {
        //Do nothing
        return Request::emptyResponse();
    }

    /**
     * Execute command
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
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
        $text    = trim($message->getText(true));
		
		//$text = 'شما چت عمومی را غیر فعال کرده اید'.PHP_EOL 
		//	.'برای ارسال پیام به چت روم عمومی باید چت را از "حالت سکوت" خارج کنید' .PHP_EOL  .PHP_EOL 
		//	.'برای این کار از کیبورد زیر استفاده کنید';
		//$data = [
		//	'chat_id'      => $chat_id,
		//	'text'         => $text,
		//];
		//return Request::sendMessage($data);
		
		if (in_array($type, ['audio', 'document', 'photo', 'video', 'voice'], true)) {
			$doc = call_user_func([$message, 'get' . ucfirst($type)]);
			($type === 'photo') && $doc = $doc[0];
				$file = Request::getFile(['file_id' => $doc->getFileId()]);
				if ($file->isOk()) {
					Request::downloadFile($file->getResult());
			}
		}
		
        //If a conversation is busy, execute the conversation command after handling the message
        $conversation = new Conversation(
            $this->getMessage()->getFrom()->getId(),
            $this->getMessage()->getChat()->getId()
        );
        //Fetch conversation command if it exists and execute it
        if ($conversation->exists() && ($command = $conversation->getCommand())) {
			if ($text === json_decode('"\uD83D\uDD1A"') .' ' .'منوی اصلی'){
				return $this->getTelegram()->executeCommand("cancel");
			} else {
				return $this->telegram->executeCommand($command);
			}
        } else if ($chat_type === 'private'){
			switch ($text){
				case json_decode('"\uD83D\uDCCD"'):
					return $this->getTelegram()->executeCommand("loc");
					break;
				case json_decode('"\uD83D\uDCE1"'):
					return $this->getTelegram()->executeCommand("radius");
					break;
				case json_decode('"\uD83D\uDD07"') .' ' .'سکوت':
					return $this->getTelegram()->executeCommand("deactive");
					break;
				case json_decode('"\uD83D\uDD0A"') .' ' .'فعال کردن':
					return $this->getTelegram()->executeCommand("active");
					break;
				case json_decode('"\u2699"') .' ' .'تنظیمات':
					return $this->getTelegram()->executeCommand("settings");
					break;
				case json_decode('"\u2753"') .' ' .'راهنما':
					return $this->getTelegram()->executeCommand("help");
					break;
				case json_decode('"\uD83D\uDC68\u200D\uD83D\uDC69\u200D\uD83D\uDC67\u200D\uD83D\uDC67"') .' ' .'تست های روانشناسی':
					return $this->getTelegram()->executeCommand("test");
					break;
				case json_decode('"\uD83D\uDCB0"') .' ' .'دریافت سکه':
					return $this->getTelegram()->executeCommand("store");
					break;
				case json_decode('"\uD83D\uDC69\u200D\u2764\uFE0F\u200D\uD83D\uDC68"') .' ' .'چت ویژه':
					return $this->getTelegram()->executeCommand("chat");
					break;	
				default:
				
					//$results = DB::selectChats(
					//	false, //Select groups (group chat)
					//	false, //Select supergroups (super group chat)
					//	true, //Select users (single chat)
					//	null, //'yyyy-mm-dd hh:mm:ss' date range from
					//	null, //'yyyy-mm-dd hh:mm:ss' date range to
					//	$chat_id //Specific chat_id to select
					//);
					$results = MyDB::selectChatsN('normal', ['users' => true], ['chat_id' => $chat_id]);
					if (!empty($results)) {
						$result = reset($results);
					}
					
					if (is_array($result)) {
						$result['id'] = $result['chat_id'];
						$chat         = new Chat($result);

						$dummy_id        = $result['dummy_id'];
						$dummy_username  = $result['dummy_username'];
						$dummy_photoid   = $result['dummy_photoid'];
						$lat             = $result['lat'];
						$lng             = $result['lng'];
						$personal_type   = $result['personal_type'];
						$host            = $result['host']; 
						$gender          = $result['gender']; 
						$coins           = $result['coins']; 
						$premium         = $result['premium']; 
						$active           = $result['active']; 
					}
					
					//if ($active === 0){
					//	$text = 'شما چت عمومی را غیر فعال کرده اید'.PHP_EOL 
					//		.'برای ارسال پیام به چت روم عمومی باید چت را از "حالت سکوت" خارج کنید' .PHP_EOL  .PHP_EOL 
					//		.'برای این کار از کیبورد زیر استفاده کنید';
					//	$data = [
					//		'chat_id'      => $chat_id,
					//		'text'         => $text,
					//	];
					//	return Request::sendMessage($data);
					//}
				
					//$chats = DB::selectChatsByLocation(null, null, true, null, null, null, null, $chat_id, $location);
					$chats = MyDB::selectChatsN('bylocation', ['users' => true, 'groups' => true, 'super_groups' => true], ['sender_id' => $chat_id, 'sender_lat' => $lat, 'sender_lng' => $lng]);

					$results = [];
					$a=0;
					if (is_array($chats)) {
						foreach ($chats as $row) {
							$a = $a +1;
							
							$convers = new Conversation($row['chat_id'],$row['chat_id']);
							if ($convers -> exists()){
								continue;
							}
							
							if (in_array($type, ['audio', 'document', 'sticker', 'photo', 'video', 'voice'], true)) {
								$doc = call_user_func([$message, 'get' . ucfirst($type)]);
								($type === 'photo') && $doc = $doc[0];
								$caption = call_user_func([$message, 'getCaption']);
								//$file = Request::getFile(['file_id' => $doc->getFileId()]);
								//if ($file->isOk()) {
								//	Request::downloadFile($file->getResult());
								//}
								$data['chat_id'] = $row['chat_id'];
								if ($caption) {
									$data['caption'] = $caption;
								}
								$data2['chat_id'] = $row['chat_id'];
								$file_id = $doc->getFileId();
								switch ($type){
									case 'audio':
										$tp = 'فایل صوتی';
										$data['audio'] = $file_id;
										$data2['text'] = '/u' .$dummy_id .'(' .$dummy_username .')' .' : ' .'یک ' .$tp .' ارسال کرد' .$resu;
										$r = Request::sendMessage($data2);
										$results[] = Request::sendAudio($data);
										break;
									case 'sticker':
										$tp = 'استیکر';
										$data['sticker'] = $file_id;
										$data2['text'] = '/u' .$dummy_id .'(' .$dummy_username .')' .' : ' .'یک ' .$tp .' ارسال کرد' .$resu;
										$r = Request::sendMessage($data2);
										$results[] = Request::sendSticker($data);
										break;
									case 'photo':
										$tp = 'عکس';
										$data['photo'] = $file_id;
										$data2['text'] = '/u' .$dummy_id .'(' .$dummy_username .')' .' : ' .'یک ' .$tp .' ارسال کرد' .$resu;
										$r = Request::sendMessage($data2);
										$results[] = Request::sendPhoto($data);
										break;
									case 'video':
										$tp = 'فایل ویدیویی';
										$data['video'] = $file_id;
										$data2['text'] = '/u' .$dummy_id .'(' .$dummy_username .')' .' : ' .'یک ' .$tp .' ارسال کرد' .$resu;
										$r = Request::sendMessage($data2);
										$results[] = Request::sendVideo($data);
										break;
									case 'voice':
										$tp = 'فایل صوتی';
										$data['voice'] = $file_id;
										$data2['text'] = '/u' .$dummy_id .'(' .$dummy_username .')' .' : ' .'یک ' .$tp .' ارسال کرد' .$resu;
										$r = Request::sendMessage($data2);
										$results[] = Request::sendVoice($data);
										break;
									case 'document':
										$tp = 'فایل';
										$data['document'] = $file_id;
										$data2['text'] = '/u' .$dummy_id .'(' .$dummy_username .')' .' : ' .'یک ' .$tp .' ارسال کرد' .$resu;
										$r = Request::sendMessage($data2);
										$results[] = Request::sendDocument($data);
										break;
								}
							} else {
								$data = [
									'chat_id'  =>  $row['chat_id'],
									'text'         => '/u' .$dummy_id .'(' .$dummy_username .') : ' .$text, //.PHP_EOL .json_encode($c),//implode(' ,',$chats),
									
								];
								$results[]       = Request::sendMessage($data);
							}
						}
					}

					//return $this->getTelegram()->executeCommand("menu");
					break;
			}
			
		}// else if ($text == 'شایعه'){
		//	$text = 'سلام'.PHP_EOL 
		//		.$a;
		//		.'هنوز در حال آماده سازیم' .PHP_EOL  .PHP_EOL 
		//		.'صبر کن.. به زودی سایعه تپل میارم';
		//	$data = [
		//		'chat_id'      => $chat_id,
		//		'text'         => $text,
		//	];
		//	$result = Request::sendMessage($data);
		//}

        return Request::emptyResponse();
    }
}
