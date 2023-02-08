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

use Longman\TelegramBot\MyFuns AS MyFun;
use Longman\TelegramBot\MyDB;

/**
 * Start command
 */
class StartCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'start';

    /**
     * @var string
     */
    protected $description = 'Start command';

    /**
     * @var string
     */
    protected $usage = '/start';

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
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();
        $text_m    = trim($message->getText(true));

		if ($text_m === ''){
			$host_id = 1001;
		} else {
			$host_id = base64_decode($text_m);
		}


        $chat       = null;
        $created_at = null;
        $updated_at = null;
        $result     = null;

		//$results = DB::selectChats($group_search, $supergroup_search, $private_search, null, null, $query_string);
		$selection = ['users' => true, 'groups' => false, 'super_groups' => false];
		$results = MyDB::selectChatsN('normal', $selection, ['chat_id' => $chat_id]);
		if (!empty($results)) {
            $result = reset($results);
            if (is_array($result)) {
    			$host       = $result['host'];
            }
        }
		if (empty($host)){
			$chat               = $this->getMessage()->getChat();
			$date               = date('Y-m-d H:i:s', time());

			$text = sprintf($strings['welcome'], $this->telegram->getBotUserName());

			$data = [
				'chat_id'      => $chat_id,
				'text'         => $text,
			];

			$result = Request::sendMessage($data);

            $result = MyDB::insertChatDetails($this->getMessage()->getChat(), ['host_id' => $host_id, 'lang' => 'en', 'satoshi' => 200, 'ads_seen' => 0]);
		} else {
            $result = MyDB::insertChatDetails($this->getMessage()->getChat(), ['host_id' => $host_id, 'lang' => 'en']);
		}

        $this->getTelegram()->executeCommand("setlang");

        return $result;
    }
}
