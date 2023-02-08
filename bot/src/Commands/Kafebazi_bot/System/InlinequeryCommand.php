<?php
/**
 * Inline Games - Telegram Bot (@inlinegamesbot)
 *
 * Copyright (c) 2016 Jack'lul <https://jacklul.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\InlineQuery\InlineQueryResultArticle;
use Longman\TelegramBot\Entities\InputMessageContent\InputTextMessageContent;
use Longman\TelegramBot\Entities\InlineKeyboard;
//use Longman\TelegramBot\Entities\InlineKeyboardMarkup;
//use Longman\TelegramBot\Entities\InlineKeyboardButton;

class InlinequeryCommand extends SystemCommand
{
    protected $name = 'inlinequery';
    protected $description = 'Reply to inline query';

    private $strings = [
        'press_button_to_start' => 'لطفا دکمه' .' <i>\'شروع\'</i> ' .'را فشار دهید تا بازی ساخته شود',
    ];

    public function execute()
    {
        $update = $this->getUpdate();
        $inline_query = $update->getInlineQuery();
        $query = trim($inline_query->getQuery());
        $data = ['inline_query_id' => $inline_query->getId(), 'cache_time' => 300];

        $articles = [];
		$all = false;
		switch ($query){
			case '':
				$all = true;
				$articles[] = [
					'id' => 'g0',
					'title' => 'لیست بازی ها',
					'description' => 'لیست بازی های موجود در کافه بازی',
					'input_message_content' => new InputTextMessageContent(
						[
							'message_text' => '<b>کافه بازی</b>' ."\n" .'از هر بازی خوشت اومد انتخابش کن تا بازی شروع بشه' . "\n\n" ,
							'parse_mode' => 'HTML',
							'disable_web_page_preview' => true,
						]
					),
					'reply_markup' => new InlineKeyboard([
													['text' => 'بازی دوز کوچک', 'switch_inline_query_current_chat' => 'g1'],
												], [
													['text' => 'بازی دوز بزرگ', 'switch_inline_query_current_chat' => 'g2'],
												], [
													['text' => 'بازی سنگ - کاغذ - قیچی', 'switch_inline_query_current_chat' => 'g4'],
												], [
													['text' => 'بازی رولت روسی', 'switch_inline_query_current_chat' => 'g6'],
												], [
													['text' => 'انتقاد', 'url' => 'https://telegram.me/kafebazi_bot'],
													['text' => 'ارتباط با ما', 'url' => 'https://telegram.me/kafebazi_bot'],
												]),
					'thumb_url' => 'http://i.imgur.com/yU2uexr.png',
				];
			case 'g1':
				$articles[] = [
					'id' => 'g1',
					'title' => ' دوز کوچک',
					'description' => 'بازی دوز در زمین 3 در 3. هر کس که سه مهره را ردیف کند برنده است',
					'input_message_content' => new InputTextMessageContent(
						[
							'message_text' => '<b>دوز کوچک</b>' ."\n" .'X-O' . "\n\n" . $this->strings['press_button_to_start'],
							'parse_mode' => 'HTML',
							'disable_web_page_preview' => true,
						]
					),
					'reply_markup' => $this->createInlineKeyboard('g1'),
					'thumb_url' => 'http://i.imgur.com/yU2uexr.png',
				];
				if(!$all){
					break;
				}
			case 'g2':
				$articles[] = [
					'id' => 'g2',
					'title' => 'دوز بزرگ',
					'description' => 'بازی دوز در زمین 7 در 6. هر کس چهار مهره را ردیف کند برنده است.',
					'input_message_content' => new InputTextMessageContent(
						[
							'message_text' => '<b>دوز</b>' . "\n\n" . $this->strings['press_button_to_start'],
							'parse_mode' => 'HTML',
							'disable_web_page_preview' => true,
						]
					),
					'reply_markup' => $this->createInlineKeyboard('g2'),
					'thumb_url' => 'http://i.imgur.com/KgH8blx.jpg',
				];
				if(!$all){
					break;
				}
			case 'g3':
				/* $articles[] = [
					'id' => 'g3',
					'title' => 'Checkers  (no flying kings, men cannot capture backwards)',
					'description' => 'Checkers is game in which the goal is to capture the other player\'s checkers or make them impossible to move.',
					'input_message_content' => new InputTextMessageContent(
						[
							'message_text' => '<b>Checkers</b>' . "\n\n" . $this->strings['press_button_to_start'],
							'parse_mode' => 'HTML',
							'disable_web_page_preview' => true,
						]
					),
					'reply_markup' => $this->createInlineKeyboard('g3'),
					'thumb_url' => 'https://i.imgur.com/mYuCwKA.jpg',
				]; */
				if(!$all){
					break;
				}
			case 'g4':
				$articles[] = [
					'id' => 'g4',
					'title' => 'سنگ - کاغذ - قیچی',
					'description' => 'این بازی در 5 دور برگذار میشه و برنده نهایی اعلام میشه',
					'input_message_content' => new InputTextMessageContent(
						[
							'message_text' => '<b>سنگ - کاغذ - قیچی</b>' . "\n\n" . $this->strings['press_button_to_start'],
							'parse_mode' => 'HTML',
							'disable_web_page_preview' => true,
						]
					),
					'reply_markup' => $this->createInlineKeyboard('g4'),
					'thumb_url' => 'https://i.imgur.com/1H8HI7n.png',
				];
				if(!$all){
					break;
				}
			case 'g5':
				/* $articles[] = [
					'id' => 'g5',
					'title' => 'Pool Checkers  (flying kings, men can capture backwards)',
					'description' => 'Checkers is game in which the goal is to capture the other player\'s checkers or make them impossible to move.',
					'input_message_content' => new InputTextMessageContent(
						[
							'message_text' => '<b>Pool Checkers</b>' . "\n\n" . $this->strings['press_button_to_start'],
							'parse_mode' => 'HTML',
							'disable_web_page_preview' => true,
						]
					),
					'reply_markup' => $this->createInlineKeyboard('g5'),
					'thumb_url' => 'https://i.imgur.com/mYuCwKA.jpg',
				]; */
				if(!$all){
					break;
				}
			case 'g6':
				$articles[] = [
					'id' => 'g6',
					'title' => 'رولت روسی',
					'description' => 'در این بازی باید به نوبت یک تیر را انتخاب و شلیک کنید. کسی که زنده بماند برنده است',
					'input_message_content' => new InputTextMessageContent(
						[
							'message_text' => '<b>رولت روسی</b>' . "\n\n" . $this->strings['press_button_to_start'],
							'parse_mode' => 'HTML',
							'disable_web_page_preview' => true,
						]
					),
					'reply_markup' => $this->createInlineKeyboard('g6'),
					'thumb_url' => 'https://i.imgur.com/LffxQLK.jpg',
				];
				if(!$all){
					break;
				}
		}
        

        // ----------------------------------------------
        if ($query == 'test') {
            $data['cache_time'] = 10;
            $data['is_personal'] = true;
        }
        
        if ($query == 'dev' && $this->getTelegram()->isAdmin()) {
            $articles[] = [
                'id' => 'g99',
                'title' => 'Dev Test Game',
                'description' => 'Only developer can see this.',
                'input_message_content' => new InputTextMessageContent(
                    [
                        'message_text' => 'Dev',
                    ]
                ),
                'reply_markup' => $this->createInlineKeyboard('g99'),
            ];

            $data['cache_time'] = 10;
            $data['is_personal'] = true;
        }
        // ----------------------------------------------

        $array_article = [];
        foreach ($articles as $article) {
            $array_article[] = new InlineQueryResultArticle($article);
        }
        $data['results'] = '[' . implode(',', $array_article) . ']';

        return Request::answerInlineQuery($data);
    }

    private function createInlineKeyboard($prefix)
    {
        $inline_keyboard = [
        //    [
        //        new InlineKeyboardButton(
                    [
                        'text' => "Start",
                        'callback_data' => $prefix . "_new"
                    ]
        //        )
        //    ]
        ];

        //$inline_keyboard_markup = new InlineKeyboardMarkup(['inline_keyboard' => $inline_keyboard]);

        //return $inline_keyboard_markup;
		
		$inline_keyboard_markup = new InlineKeyboard($inline_keyboard);

        return $inline_keyboard_markup;
		//$keyboard = new InlineKeyboard([
		//						['text' => "Start", 'callback_data' => $prefix . "_new"],
		//					]);
		
		//return $keyboard;
    }
}
