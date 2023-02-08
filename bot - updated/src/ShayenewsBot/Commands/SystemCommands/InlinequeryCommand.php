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
use Longman\TelegramBot\Entities\InlineQuery\InlineQueryResultPhoto;
use Longman\TelegramBot\Entities\InlineQuery\InlineQueryResultGif;
use Longman\TelegramBot\Entities\InlineQuery\InlineQueryResultMpeg4Gif;
use Longman\TelegramBot\Entities\InlineQuery\InlineQueryResultVideo;
use Longman\TelegramBot\Entities\InlineQuery\InlineQueryResultAudio;
use Longman\TelegramBot\Entities\InlineQuery\InlineQueryResultVoice;
use Longman\TelegramBot\Entities\InlineQuery\InlineQueryResultDocument;
use Longman\TelegramBot\Entities\InlineQuery\InlineQueryResultLocation;
use Longman\TelegramBot\Entities\InlineQuery\InlineQueryResultVenue;
use Longman\TelegramBot\Entities\InlineQuery\InlineQueryResultContact;
use Longman\TelegramBot\Entities\InlineQuery\InlineQueryResultGame;
use Longman\TelegramBot\Entities\InputMessageContent\InputTextMessageContent;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Conversationdetails;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;

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
        //echo "<pre>", print_r($d), "</pre>";

        $notes = $d->notes;

        $data2 = [];
        $data2['title'] = 'برای ارسال پست کلیک کنید.';
        $data2['hide_url'] = true;
        //
        $keyboard =[];
        $counter = 0;
        for ($i=1; $i <4 ; $i++) {
            if (isset($notes['keyboard' .$i])) {
                $keyboard[]=$notes['keyboard' .$i];
                $counter++;
            }
        }
        if ($counter>0){
            $data2['reply_markup'] =new InlineKeyboard(...$keyboard);
        }
        //
        $results = [];
        if (strlen($notes['caption']) > 250){
            $data2['id']   = '001';
            $data2['type'] = 'article';
            $data2['input_message_content'] = new InputTextMessageContent([
                'message_text'              => '<a href="' .$notes['link'] .'">&#8205;</a>' .PHP_EOL .$notes['caption'],
                'disable_web_page_preview'  => false,
                'parse_mode'                => 'HTML',
            ]);
            $data2['thumb_url'] = trim($notes['link']);
            $results []= new InlineQueryResultArticle($data2);
        } else {
            $data2['id']        = '001';
            $data2['type'] = $notes['type'];
            if (isset($notes['caption'])){
                $data2['caption'] = $notes['caption'];
            }
            $data2[$notes['type'] .'_url'] = trim($notes['link']);
            switch ($notes['type']) {
                case 'photo':
                    $data2['thumb_url'] = trim($notes['link']);
                    $results []= new InlineQueryResultPhoto($data2);
                    break;
                case 'video':
                    $data2['mime_type'] = $notes['doc']['mime_type'];
                    $data2['title']     = $notes['doc']['title'];
                    $data2['thumb_url'] = $notes['doc']['thumb']['link'];
                    $results []= new InlineQueryResultVideo($data2);
                    break;
                case 'audio':
                    $data2['title']     = $notes['doc']['title'];
                    $results []= new InlineQueryResultAudio($data2);
                    break;
                case 'voice':
                    $data2['title']     = 'صدای ضبظ شده';
                    $results []= new InlineQueryResultVoice($data2);
                    break;
                case 'document':
                    //echo "<pre>", print_r($notes['doc']), "</pre>";
                    if ($notes['doc']['mime_type'] !== 'video/mp4'){
                        $data2['mime_type'] = $notes['doc']['mime_type'];
                        $data2['title']     = $notes['doc']['title'];
                        $results []= new InlineQueryResultDocument($data2);
                    } else {
                        unset($data[$notes['type'] .'_url']);
                        $data2['type'] = 'mpeg4_gif';
                        $data2['mpeg4_url'] = trim($notes['link']);
                        $data2['thumb_url'] = $notes['doc']['thumb']['link'];
                        $results []= new InlineQueryResultMpeg4Gif($data2);
                    }
                    break;
                case 'contact':
                    $results []= new InlineQueryResultContact($data2);
                    break;
                default:

                    break;
            }
        }

        $data = [];
        $data['inline_query_id']      = $inline_query->getId();
        if ($query === 1001){
            $data['switch_pm_text']       = 'شروع استفاده از ربات';
            $data['switch_pm_parameter']  = '1002';
        }
        $data['cache_time']           = 10;
        $data['results']              = '[' . implode(',', $results) . ']';;

        //
        $result = Request::answerInlineQuery($data);
        //echo "<pre>", print_r($result), "</pre>";
        return $result;
        //
        // $results = [];
        //
        // if (true) {
        //     $articles = [
        //         [
        //             'id'                    => '001',
        //             'title'                 => 'https://core.telegram.org/bots/api#answerinlinequery',
        //             'description'           => 'you enter: ' . $query,
        //             'input_message_content' => new InputTextMessageContent(['message_text' => ' ' . $query]),
        //         ],
        //         [
        //             'id'                    => '002',
        //             'title'                 => 'https://core.telegram.org/bots/api#answerinlinequery',
        //             'description'           => 'you enter: ' . $query,
        //             'input_message_content' => new InputTextMessageContent(['message_text' => ' ' . $query]),
        //         ],
        //         [
        //             'id'                    => '003',
        //             'title'                 => 'https://core.telegram.org/bots/api#answerinlinequery',
        //             'description'           => 'you enter: ' . $query,
        //             'input_message_content' => new InputTextMessageContent(['message_text' => ' ' . $query]),
        //         ],
        //     ];
        //
        //     foreach ($articles as $article) {
        //         $results[] = new InlineQueryResultArticle($article);
        //     }
        // }
        //
        // $data['results'] = '[' . implode(',', $results) . ']';
        //
        // return Request::answerInlineQuery($data);
    }
}
