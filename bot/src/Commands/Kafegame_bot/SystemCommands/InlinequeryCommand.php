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
use Longman\TelegramBot\Entities\InlineQuery\InlineQueryResultArticle;
use Longman\TelegramBot\Entities\InlineQuery\InlineQueryResultGame;
use Longman\TelegramBot\Entities\InputMessageContent\InputTextMessageContent;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Config;

/**
 * Inline query command
 */
class InlinequeryCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'inlinequery';

    /**
     * @var string
     */
    protected $description = 'Reply to inline query';

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
        $update       = $this->getUpdate();
        $inline_query = $update->getInlineQuery();
        $query        = $inline_query->getQuery();
		$from         = $inline_query->getFrom();

        $data    = ['inline_query_id' => $inline_query->getId()];
        $results = [];

        if ($query !== '') {
			$Configer = new Config;
			$game_raw = $Configer->getRawQuery($query);
			$game_list = $game_raw['list'];
			$game_url_tg = $game_raw['url_tg'];
			//
			//$games = array();
			foreach ($game_list as $key => $value){
				$game = [
							'id' => strval($key), 
							'game_short_name' => $value, 
							'reply_markup' => new InlineKeyboard([
								['text' => 'اجرای بازی', 'url' => $game_url_tg[$key]],
								['text' => 'مشاهده همه', 'url' => 'https://kafegame.com/app/'],
							]),
						];
				array_push($games, $game);
				$results[] = new InlineQueryResultGame($game);
			}
            //$game1 =
            //    [
            //        'id'                    => '021',
            //        'game_short_name'       => 'A2048a',
			//		'reply_markup'          => new InlineKeyboard([
			//										['text' => 'اجرای بازی', 'url' => 'https://kafegame.com/app/' .$token],
			//										['text' => 'مشاهده همه', 'url' => 'https://kafegame.com/app/'],
			//									]),
            //    ];
			//$results[] = new InlineQueryResultGame($game1);
            //array_push($games, $game1);

            //foreach ($games as $game) {
            //    $results[] = new InlineQueryResultGame($game);
            //}
        } else {
			$articles = [
                [
                    'id'                    => '001',
                    'title'                 => 'لیست بازی ها',
                    'description'           => 'برای مشاهده لیست روی این مورد کلیک کنید',
                    'input_message_content' => new InputTextMessageContent(['message_text' => '/gamelist']),
                ],
            ];
			foreach ($articles as $article) {
                $results[] = new InlineQueryResultArticle($article);
            }
			//
			//
			$Configer = new Config;
			$game_raw = $Configer->getRaw();
			$game_list = $game_raw['list'];
			$game_url_tg = $game_raw['url_tg'];
			foreach ($game_list as $key => $value){
				$game = [
							'id' => strval($key), 
							'game_short_name' => $value, 
							'reply_markup'          => new InlineKeyboard([
													['text' => 'اجرای بازی', 'url' => 'https://kafegame.com/app/' .$token],
													['text' => 'مشاهده همه', 'url' => 'https://kafegame.com/app/'],
												]),

						];
				array_push($games, $game);
				$results[] = new InlineQueryResultGame($game);
			}
			
		}
		$data['results'] = '[' . implode(',', $results) . ']';
       
        return Request::answerInlineQuery($data);
    }
}
