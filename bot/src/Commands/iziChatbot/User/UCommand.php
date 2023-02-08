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
use Longman\TelegramBot\Entities\Chat;
use Longman\TelegramBot\Entities\PhotoSize;
use Longman\TelegramBot\Entities\UserProfilePhotos;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\InlineKeyboard;

/**
 * Admin "/whois" command
 */
class UCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'u';

    /**
     * @var string
     */
    protected $description = 'مشاهده اطلاعات مجازی کاربران چت';

    /**
     * @var string
     */
    protected $usage = '/u <id> or /u<id>';

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
        $text_m    = trim($message->getText(true));

		
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

        if ($command !== 'u') {
            $text_m = substr($command, 1);

            //We need that '-' now, bring it back
            //if (strpos($text, 'g') === 0) {
            //    $text = str_replace('g', '-', $text);
            //}
        }

		//$data2 = [
        //    'chat_id'      => $chat_id,
        //    'text'         => $text,
        //];
        //$Res = Request::sendMessage($data2);
		
        if ($text_m === '') {
            $text_m = 'حتما باید یک ایدی هم ذکر کنید :' .' /u<id>';
        } else {
            $user_id    = $text_m;
            $chat       = null;
            $created_at = null;
            $updated_at = null;
            $result     = null;

            if (is_numeric($text_m)) {
                $results = DB::selectChatsByDummy(
                    true, //Select groups (group chat)
                    true, //Select supergroups (super group chat)
                    true, //Select users (single chat)
                    null, //'yyyy-mm-dd hh:mm:ss' date range from
                    null, //'yyyy-mm-dd hh:mm:ss' date range to
                    $user_id //Specific chat_id to select
                );

                if (!empty($results)) {
                    $result = reset($results);
                }
            } else {
                $results = DB::selectChatsByDummy(
                    true, //Select groups (group chat)
                    true, //Select supergroups (super group chat)
                    true, //Select users (single chat)
                    null, //'yyyy-mm-dd hh:mm:ss' date range from
                    null, //'yyyy-mm-dd hh:mm:ss' date range to
                    null, //Specific chat_id to select
                    $text_m //Text to search in user/group name
                );

                if (is_array($results) && count($results) === 1) {
                    $result = reset($results);
                }
            }

            if (is_array($result)) {
                $result['id'] = $result['chat_id'];
                $chat         = new Chat($result);

                $user_id      = $result['id'];
                $created_at   = $result['chat_created_at'];
                $updated_at   = $result['chat_updated_at'];
                $old_id       = $result['old_id'];
				
				
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

            if ($chat !== null) {
                if ($chat->isPrivateChat()) {
                    $text = 'User ID: ' . $dummy_id . PHP_EOL;
                    $text .= 'User Name: ' . $dummy_username  . PHP_EOL;

                    //$username = $chat->getUsername();
                    //if ($username !== null && $username !== '') {
                    //    $text .= 'Username: @' . $username . PHP_EOL;
                    //}
					
					$text .= 'Location: ' .'lat : '.$lat .' , lng : ' .$lng . PHP_EOL;
					$text .= 'Personality Type: ' . $personal_type . PHP_EOL;
					$text .= 'Gender: ' . $gender . PHP_EOL;
					
					
					$inline_keyboard = new InlineKeyboard([
						['text' => 'بلاک', 'callback_data' => 'ban' . $dummy_id],
						['text' => 'ریپورت', 'callback_data' => 'report' . $dummy_id],
						['text' => 'فالو', 'callback_data' => 'follow' . $dummy_id],
					]);
					$data['reply_markup'] = $inline_keyboard;

                    //$text .= 'First time seen: ' . $created_at . PHP_EOL;
                    //$text .= 'Last activity: ' . $updated_at . PHP_EOL;

                    //Code from Whoami command
                    $limit    = 10;
                    $offset   = null;
                    $response = Request::getUserProfilePhotos(
                        [
                            'user_id' => $user_id,
                            'limit'   => $limit,
                            'offset'  => $offset,
                        ]
                    );

                    //if ($response->isOk()) {
					if (null !== $dummy_photoid) {
                        /** @var UserProfilePhotos $user_profile_photos */
                        //$user_profile_photos = $response->getResult();

                        //if ($user_profile_photos->getTotalCount() > 0) {
                            //$photos = $user_profile_photos->getPhotos();

                            /** @var PhotoSize $photo */
                            //$photo   = $photos[0][2];
                            //$file_id = $photo->getFileId();

                            //$data['photo']   = $file_id;
							$data['photo']   = $dummy_photoid;
                            $data['caption'] = $text;

                            return Request::sendPhoto($data);
                        //}
                    }
                } elseif ($chat->isGroupChat()) {
                    $text = 'Chat ID: ' . $user_id . (!empty($old_id) ? ' (previously: ' . $old_id . ')' : '') . PHP_EOL;
                    $text .= 'Type: ' . ucfirst($chat->getType()) . PHP_EOL;
                    $text .= 'Title: ' . $chat->getTitle() . PHP_EOL;
                    $text .= 'First time added to group: ' . $created_at . PHP_EOL;
                    $text .= 'Last activity: ' . $updated_at . PHP_EOL;
                }
            } elseif (is_array($results) && count($results) > 1) {
                $text = 'چندین کاربر با این مشخصات وجود دارند';
            } else {
                $text = 'چنین کاربری وجود ندارد';
            }
        }

        $data['text'] = $text .PHP_EOL ;//.json_encode($message);
		

        return Request::sendMessage($data);
    }
}
