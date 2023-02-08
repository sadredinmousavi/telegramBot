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
use Longman\TelegramBot\Entities\InputMessageContent\InputTextMessageContent;
use Longman\TelegramBot\Request;

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
    protected $version = '1.0.0';

    /**
     * Command execute method
     *
     * @return mixed
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute()
    {
        $inline_query = $this->getUpdate()->getInlineQuery();
        $user_id      = $inline_query->getFrom()->getId();
        $query        = $inline_query->getQuery();
        //
        if ($query === ''){
			$query = 1001;
		} else {
			$query = base64_decode($query);
		}
        $d = new Conversationdetails($query, $chat_id, 'load_by_id');
        echo "<pre>", print_r($d), "</pre>";

        $notes = $d->notes;

        $data    = ['inline_query_id' => $inline_query->getId()];
        $results = [];

        if ($query !== '') {
            $articles = [
                [
                    'id'                    => '001',
                    'title'                 => 'https://core.telegram.org/bots/api#answerinlinequery',
                    'description'           => 'you enter: ' . $query,
                    'input_message_content' => new InputTextMessageContent(['message_text' => ' ' . $query]),
                ],
                [
                    'id'                    => '002',
                    'title'                 => 'https://core.telegram.org/bots/api#answerinlinequery',
                    'description'           => 'you enter: ' . $query,
                    'input_message_content' => new InputTextMessageContent(['message_text' => ' ' . $query]),
                ],
                [
                    'id'                    => '003',
                    'title'                 => 'https://core.telegram.org/bots/api#answerinlinequery',
                    'description'           => 'you enter: ' . $query,
                    'input_message_content' => new InputTextMessageContent(['message_text' => ' ' . $query]),
                ],
            ];

            foreach ($articles as $article) {
                $results[] = new InlineQueryResultArticle($article);
            }
        }

        $data['results'] = '[' . implode(',', $results) . ']';

        return Request::answerInlineQuery($data);
    }
}
