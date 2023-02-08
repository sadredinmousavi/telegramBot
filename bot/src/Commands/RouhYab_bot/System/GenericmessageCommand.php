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
			
			return $this->telegram->executeCommand("handle");
			
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
