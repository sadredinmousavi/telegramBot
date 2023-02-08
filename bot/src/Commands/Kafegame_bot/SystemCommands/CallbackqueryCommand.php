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
        $update            = $this->getUpdate();
        $callback_query    = $update->getCallbackQuery();
        $callback_query_id = $callback_query->getId();
        $callback_data     = $callback_query->getData();
		//
		$callback_game_name           = $callback_query->getGameShortName();
		$callback_message             = $callback_query->getMessage();
		$callback_from                = $callback_query->getFrom();
		$callback_inline_message_id   = $callback_query->getInlineMessageId();
		$callback_chat_instance       = $callback_query->getChatInstance();
		//
		$Configer = new Config;
		
		if (!empty($callback_game_name)){
			$url = $Configer->getUrlWeb($callback_game_name);
			if($url !== 'false'){
				$data = [
					'callback_query_id' => $callback_query_id,
					'url'               => $url,
				];
			} else {
				$data = [
					'callback_query_id' => $callback_query_id,
					'text'              => 'بازی ' .$callback_game_name .' موجود نمی باشد',
					'show_alert'        => $callback_data === 'thumb up',
					'cache_time'        => 5,
				];
			}
		} else {
			$data = [
				'callback_query_id' => $callback_query_id,
				'text'              => 'Hello World! ' .$callback_game_name .'  ' .$callback_from,
				'show_alert'        => $callback_data === 'thumb up',
				'cache_time'        => 5,
			];
		}

        return Request::answerCallbackQuery($data);
    }
}
