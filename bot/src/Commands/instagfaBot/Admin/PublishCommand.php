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


namespace Longman\TelegramBot\Commands\AdminCommands;


use Longman\TelegramBot\Commands\AdminCommand;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\DBHelper;
use Longman\TelegramBot\DB;
use Longman\TelegramBot\MyDB;
use Longman\TelegramBot\Entities\Chat;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\ConversationDB;

use Longman\TelegramBot\TelegramLog;



//require '/home/kafegame/public_html/instabot/core.php';
//$path = $_SERVER['DOCUMENT_ROOT'] .'/instabot/core.php';
require_once '/home2/kafegame/public_html/instabot/core.php';

use \Mylib\DB AS InstaDB;
use core;



class PublishCommand extends AdminCommand
{
    protected $name = 'publish';

	protected $description = 'Publish a number of related post to all channels';

	protected $usage = '/publish';

    /**
     * @var string
     */
    protected $version = '1.1.0';

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
    $text_m    = trim($message->getText(true));


    // $data2 =[];
    // $data2['message_id'] = '1024';
    // $data2['chat_id'] = '-1001057227310';//'270783544';//'-1001057227310';
    // $data2['text'] = 'دیوید جونز، سازنده بازی GTA، در سال 1990 در آمریکا،پس از سرقت یک تانک ،به خیابان های شهر رفته و چندین ماشین را له کرد تا ایده بازی خود را به صورت واقعی تست کند! '
    //                 .'(ویدئوی زیر)' .PHP_EOL
    //                 .'درنهایت وی توسط پلیس دستگیر میشود و پس از گذراندن دوران محکومیت خود پس از 7 سال آزاد شده و اولین سری GTA، را در سال 1997 به بازار عرضه میکند.'
    //                 .PHP_EOL
    //                 .'<a href="https://t.me/zasxwq/5">&#8205;</a>'
    //                 .PHP_EOL
    //                 .' لینک عضویت:
    //                     https://telegram.me/joinchat/A7z4Vj8EAi4YrFaeEBuoOQ';
    //
    // $data2['disable_web_page_preview'] = false;
    // $data2['disable_notification'] = true;
    // $data2['parse_mode'] = 'HTML';
    // $result = Request::editMessageText($data2);
    // //$result = Request::sendMessage($data2);






    $instagram = new core();
    //$instagram->enableMySql($mysql_credentials);
    $instagram->enableMySql();

    //
    $channels = InstaDB::selectChannels();
    foreach ($channels as $channel) {
        // echo "<pre>";
        // print_r($channel);
        // echo "</pre>";
        if ($channel['videos'] > 0){
            $this->publishposts($channel['channel_id'], 'videos', $channel['videos'], $channel['date_from'], $channel['user_name']);
        }
        if ($channel['images'] > 0){
            $this->publishposts($channel['channel_id'], 'images', $channel['images'], $channel['date_from'], $channel['user_name']);
        }
        if ($channel['carousels'] > 0){
            $this->publishposts($channel['channel_id'], 'carousels', $channel['carousels'], $channel['date_from'], $channel['user_name']);
        }

    }







    return true;


	}

  protected function publishposts($channel_id, $type, $number, $date_from, $ch_username){
      $selection = ['videos' => false, 'images' => false, 'carousels' => false];
      $selection[$type] = true;
      $a = InstaDB::selectMedia($selection, ['not_published_in' => $channel_id, 'user_belongs_to_channel' => $channel_id, 'scanned' => 1, 'limit_rows' => $number], $date_from = strtotime("-" .$date_from ." day"));//'user_not_publisih_x_recent_post' => 5
      // echo "<pre>";
      // print_r($a);
      // echo "</pre>";


      if(empty($a)){
          return true;
          //continue;
      }

      $words = InstaDB::selectBlackWords();
      $penalty = 0;
      foreach ($words as $word) {
          if (stripos($media['caption'], $word['word'])>0){
              $penalty += $word['penalty'];
          }
      }
      if ($penalty >= 10){
          InstaDB::insertPublished($channel_id, $media['media_id'], null, $type, null);
          return true;
      }




      foreach ($a as $media) {
          switch ($media['type']) {
              case 'image':
                  $type = 'photo';
                  break;
              case 'video':
                  $type = 'video';
                  break;
              case 'carousel':
                  $type = 'photo';
                  break;
              default:
                  $type = 'photo';
                  break;
          }

          //
          //Rules
          if ($channel_id === '-1001332864326'){
              $media['caption']='';
          }
          //



          preg_match_all("/#\S+\s*/i", $media['caption'], $hastags);//extract all hastags
          preg_match_all("/@\S+\s*/i", $media['caption'], $mentions);//extract all mentions
          //$media['caption'] = preg_replace("/#([\p{L}\d_]+)/u", " \' .json_decode(\'\"\u270D\uFE0F\"\') .\'$1,", $media['caption']);//preg_replace("/#([\p{L}\d_]+)/u", "($1)", $media['caption']);//put hashtag into prantheses
          $hashtag = json_decode('"\u0023\u20E3"');
          $media['caption'] = str_replace('#', $hashtag, $media['caption']);
          //
          $media['caption'] = str_replace('<', "\"", $media['caption']);
          $media['caption'] = str_replace('>', "\"", $media['caption']);
          if (empty($media['full_name']))
              $media['full_name'] = $media['username'];
          //$media['caption'] = preg_replace("/@([\p{L}\d_]+)/u", " $1 ", $media['caption']);//preg_replace("/@([\p{L}\d_]+)/u", "([at]$1)", $media['caption']);//put atsign into prantheses
          preg_replace("/([\p{L}\d_]+?)(\n+)([\p{L}\d_]+?)/", "$1\n$3", $media['caption']);//remove duplicate \n s

          $need_reply = false;
          $limit = 120;
          if (strlen($media['caption'])>$limit){
              $string = substr($media['caption'], 0 ,strpos($media['caption'], ' ', $limit-10));
              $string = wordwrap($media['caption'], $limit);
              $caption = substr($string, 0, strpos($string, "\n", $limit-10)) .'    ' .'(ادامه کپشن در پست بعدی)';
              $need_reply = true;
          } else {
              $caption = $media['caption'];
          }
          if(!isset($media['link'])){
              $media['link'] = 'https://www.instagram.com/p/' .$media['code'];
          }
          //
          //
          //
          $posthaye_need_reply_ra_be_komake_channel_insfa_vefrestim = true;
          if(!$posthaye_need_reply_ra_be_komake_channel_insfa_vefrestim || !$need_reply){
              $atsign = json_decode('"\uD83C\uDD94"');
              $caption = str_replace('@', $atsign, $caption);
              //
              $data = [];
              $data['chat_id'] = $channel_id;
              $data['caption'] = //json_decode('"\uD83D\uDC64"') .$media['username'] .json_decode('"\uD83D\uDC64"')
                                //.PHP_EOL .json_decode('"\u2764\uFE0F"') .' ' .$media['likes']
                                json_decode('"\u270D\uFE0F"') .' #' .preg_replace("/([^\p{L}\d_ ]+)/u", "", str_replace(" ", "_", $media['full_name']))
                                .PHP_EOL .$caption
                                .PHP_EOL .PHP_EOL .json_decode('"\uD83D\uDC49"') .' @' .$ch_username;
              $data['disable_notification'] = true;
              //
              if ($type === 'message') {
                  //$data['text'] = $message->getText(true);
                  //$data['text'] = 'asdasd';
              } elseif ($type === 'photo') {
                  //$data['photo'] = $message->getPhoto()[0]->getFileId();
                  $data['photo'] = $media['img_standard_resolution'];
              } elseif ($type === 'video') {
                  $data['video'] = $media['vid_standard_resolution'];
              }

              $callback_path     = 'Longman\TelegramBot\Request';
              $callback_function = 'send' . ucfirst($type);
              if (!method_exists($callback_path, $callback_function)) {
                  throw new TelegramException('Methods: ' . $callback_function . ' not found in class Request.');
              }
              //
              //
              //
              $result = $callback_path::$callback_function($data);
              //echo "<pre>";
              //print_r($data);
              //print_r($result);
              //echo "</pre>";
              if ($result->isOK()){
                  InstaDB::insertPublished($channel_id, $media['media_id'], $result->getResult()->getmessage_id(), $type, null);
              } else {
                  TelegramLog::error($result->getMessage());
                  //throw new TelegramException('Error: ' .$result->getDescription());
                  InstaDB::updateMedia($media['media_id'], ['scanned' => 0]);
              }

              if ($need_reply){
                  $user_mention = '<a href="' .$media['link'] .'">' .$media['username'] .'</a>';
                  //$user_mention_to_tele = '<a href="https://t.me/' . .'/' .$result->getResult()->getmessage_id() .'">' .$media['username'] .'</a>';
                  $user_mention_to_tele = '<a href="https://t.me/' .$ch_username .'/' .$result->getResult()->getmessage_id() .'">&#8205;</a>';
                  $data2 = [];
                  $data2['chat_id'] = $channel_id;
                  $data2['text'] = json_decode('"\uD83D\uDC64"') .$user_mention_to_tele .$user_mention .$user_mention_to_tele .json_decode('"\uD83D\uDC64"')
                                .PHP_EOL .json_decode('"\u2764\uFE0F"') .' ' .$media['likes']
                                .PHP_EOL .PHP_EOL .$media['caption']
                                .PHP_EOL .PHP_EOL .json_decode('"\uD83D\uDC49"') .' @' .$ch_username;

                  $data2['disable_web_page_preview'] = false;
                  $data2['disable_notification'] = true;
                  $data2['parse_mode'] = 'HTML';
                  $data2['reply_to_message_id'] = $result->getResult()->getmessage_id();
                  $result = Request::sendMessage($data2);
              }
          } else {
              $data = [];
              $data['chat_id'] = '-1001197492471';
              $data['caption'] = json_decode('"\u2764\uFE0F"') .' ' .$media['full_name'] .json_decode('"\u2764\uFE0F"');
                                //.PHP_EOL .'به ما بپیوندید'
                                //.PHP_EOL .json_decode('"\uD83D\uDC49"') .' @' .$ch_username;
              $data['disable_notification'] = true;
              //
              if ($type === 'message') {
                  //$data['text'] = $message->getText(true);
                  //$data['text'] = 'asdasd';
              } elseif ($type === 'photo') {
                  //$data['photo'] = $message->getPhoto()[0]->getFileId();
                  $data['photo'] = $media['img_standard_resolution'];
              } elseif ($type === 'video') {
                  $data['video'] = $media['vid_standard_resolution'];
              }

              $callback_path     = 'Longman\TelegramBot\Request';
              $callback_function = 'send' . ucfirst($type);
              echo "<pre>";
              print_r($data);
              echo $callback_path .'  ' .$callback_function .'  ';
              echo "</pre>";
              if (!method_exists($callback_path, $callback_function)) {
                  throw new TelegramException('Methods: ' . $callback_function . ' not found in class Request.');
              }
              //
              //
              //
              $result = $callback_path::$callback_function($data);
              if ($result->isOK()){
              } else {
                  echo "<pre>";
                  print_r($result);
                  echo "</pre>";
                  TelegramLog::error($result->getMessage());
                  //throw new TelegramException('Error: ' .$result->getDescription());
                  InstaDB::updateMedia($media['media_id'], ['scanned' => 0]);
              }
              $a = $result;
              //echo "<pre>";
              //print_r($result);
              //echo "</pre>";
              //InstaDB::insertPublished($channel_id, $media['media_id'], $result->getResult()->getmessage_id());

              if ($need_reply && $result->isOK()){
                  $media['caption'] = preg_replace("/@([\p{L}\d_.]+)/u", '<a href="https://instagram.com/$1">$1</a>' , $media['caption']);//preg_replace("/@([\p{L}\d_]+)/u", "([at]$1)", $media['caption']);//put atsign into prantheses
                  $user_mention = '<a href="' .$media['link'] .'">' .$media['username'] .'</a>';
                  //$user_mention_to_tele = '<a href="https://t.me/' . .'/' .$result->getResult()->getmessage_id() .'">' .$media['username'] .'</a>';
                  $user_mention_to_tele = '<a href="https://t.me/insfa/' .$result->getResult()->getmessage_id() .'">&#8205;</a>';
                  $data2 = [];
                  $data2['chat_id'] = $channel_id;
                  $data2['text'] = //json_decode('"\uD83D\uDC64"') .$user_mention_to_tele .$user_mention .$user_mention_to_tele .json_decode('"\uD83D\uDC64"')
                                //.PHP_EOL .json_decode('"\u2764\uFE0F"') .' ' .$media['likes']
                                json_decode('"\u270D\uFE0F"') .' #' .preg_replace("/([^\p{L}\d_ ]+)/u", "", str_replace(" ", "_", $media['full_name'])) .$user_mention_to_tele
                                .PHP_EOL .PHP_EOL .$media['caption']
                                .PHP_EOL .PHP_EOL .json_decode('"\uD83D\uDC49"') .' @' .$ch_username;

                  $data2['disable_web_page_preview'] = false;
                  $data2['disable_notification'] = true;
                  $data2['parse_mode'] = 'HTML';
                  //$data2['reply_to_message_id'] = $result->getResult()->getmessage_id();
                  $result = Request::sendMessage($data2);
                  if ($result->isOK()){
                      InstaDB::insertPublished($channel_id, $media['media_id'], $result->getResult()->getmessage_id(), 'message', $a->getResult()->getmessage_id());
                  } else {
                      TelegramLog::error($result->getMessage());
                      InstaDB::updateMedia($media['media_id'], ['scanned' => 0]);
                  }

              }
          }
      }

  }

  protected function CaptionCheck($channel_id, $type, $number, $date_from, $ch_username){
      $selection = ['videos' => false, 'images' => false, 'carousels' => false];

  }


}
