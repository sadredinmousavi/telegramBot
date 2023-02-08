<?php

namespace Longman\TelegramBot;

use Longman\TelegramBot\Request;
//use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\DB;
use Longman\TelegramBot\Entities\Message;
use Longman\TelegramBot\Exception\TelegramException;

use \Mylib\DB AS InstaDB;
use core;


class MyFuns
{
    protected $upload_path;

    protected $download_path;

    static protected $mysql_credentials = [];


    //
    //


    public function __construct()
    {

    }



    public static function selectionOptionsCreator($channel_id){
        $channels = InstaDB::selectChannels();
        $channel = $channels[array_search($channel_id, array_column($channels, 'channel_id'))];
        $options =[];
        $options['not_published_in'] = $channel['channel_id'];
        $options['user_belongs_to_channel'] = $channel['channel_id'];
        $options['scanned'] = 1;
        if ($channel['need_check'] == 1){
            $options['checked'] = 1;
        }
        if ($channel['edit_caption'] == 1){
            $options['has_edited_caption'] = 1;
        }
        //'user_not_publisih_x_recent_post' => 5
        return $options;
    }






    public static function publish_update_posts($channel_id, $type = null, $publish_or_update = 'update'){
        $selection = ['videos' => false, 'images' => false, 'carousels' => false];
        $selection[$type] = true;
        //
        $channels = InstaDB::selectChannels();
        $channel = $channels[array_search($channel_id, array_column($channels, 'channel_id'))];
        //
        $options = self::selectionOptionsCreator($channel_id);
        $options['limit_rows'] = $channel[$type];
        //
        if ($publish_or_update === 'publish'){
            $a = InstaDB::selectMedia($selection, $options, $date_from = strtotime("-" .$channel['date_from'] ." day"));
        } elseif($publish_or_update === 'update'){
            $a = InstaDB::selectPublished($channel['channel_id']);
        }

        // echo "<pre>", print_r($a), "</pre>";
        if(empty($a)){
            return true;
            //continue;
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

            if (self::checkCaptionForAds($media, $channel['channel_id'])){
                // echo "<pre>", print_r($media), "</pre>";
                continue;
            }
            //
            //Rules
            if ($channel_id === '-1001332864326'){
                $media['caption']='';
            }
            //

            $media['caption'] = self::caption_preProcess($media);

            if (empty($media['full_name']))
                $media['full_name'] = $media['username'];

            if(!isset($media['link']))
                $media['link'] = 'https://www.instagram.com/p/' .$media['code'];

            // self::download_media($media);
            // echo "<pre>", "Haha", "</pre>";
            //
            //
            //
            //

            if ($publish_or_update === 'update')
                unset($channel);
            // echo "<pre>", print_r($media), "</pre>";
            switch ($channel_id) {
                case '270783544':
                    $post_id = self::publish_update_method_4($media, $channel);
                    break;

                case '-1001269417831':
                    break;

                case '-1001106429323':
                    $post_id = self::publish_update_method_4($media, $channel);
                    break;

                case '-1001105299360':
                    $post_id = self::publish_update_method_3($media, $channel);
                    break;

                default:
                    $post_id = self::publish_update_method_1($media, $channel);
                    break;
            }

        }
    }









    public static function checkCaptionForAds($media, $channel_id){
        $words = InstaDB::selectBlackWords();
        $penalty = 0;
        foreach ($words as $word) {
            if (mb_stripos($media['caption'], $word['word'], 0, $encoding = 'utf-8')>0){
                $penalty += $word['penalty'];
            }
        }
        if ($penalty >= 10){
            echo "<pre>", print_r($media), "</pre>";
            InstaDB::insertPublished($channel_id, $media['media_id'], null, $type, null);
            return true;
        } else {
            return false;
        }
    }







    public static function caption_preProcess($media){
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

        return $media['caption'];
    }







    public static function publish_update_method_1($media, $channel = null){
        if (null === $channel){
            $publish_or_update = 'update';
            $channel_id = $media['channel_id'];
            $ch_username = $media['user_name'];//$posts['channel_username'];
        } else {
            $publish_or_update = 'publish';
            $channel_id = $channel['channel_id'];
            $ch_username = $channel['user_name'];//$posts['channel_username'];
        }


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
                break;
        }

        $standard_caption_length = true;
        $limit = 120;
        if (strlen($media['caption'])>$limit){
            $string = substr($media['caption'], 0 ,strpos($media['caption'], ' ', $limit-10));
            $string = wordwrap($media['caption'], $limit);
            $caption = substr($string, 0, strpos($string, "\n", $limit-10)) .'    ' .'(ادامه کپشن در پست بعدی)';
            $standard_caption_length = false;
        } else {
            $caption = $media['caption'];
        }

        //
        //
        //
        if($standard_caption_length){
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


            if ($publish_or_update === 'publish'){
                if ($type === 'photo') {
                    $data['photo'] = $media['img_standard_resolution'];
                    $result = Request::sendPhoto($data);
                } elseif ($type === 'video') {
                    $data['video'] = $media['vid_standard_resolution'];
                    $result = Request::sendVideo($data);
                }
            } else if ($publish_or_update === 'update') {
                $data['message_id'] = $media['post_id'];
                if ($type === 'photo') {
                    $data['photo'] = $media['img_standard_resolution'];
                    $result = Request::editMessageCaption($data);
                } elseif ($type === 'video') {
                    $data['video'] = $media['vid_standard_resolution'];
                    $result = Request::editMessageCaption($data);
                }
            }
            if ($result->isOK()){
                InstaDB::insertPublished($channel_id, $media['media_id'], $result->getResult()->getmessage_id(), $type, null);
            } else {
                InstaDB::updateMedia($media['media_id'], ['scanned' => 0]);
                echo new TelegramException('post was not published! Error: ' . $result->getErrorCode() . ' ' . $result->getDescription());

            }

        } else {
            if ($publish_or_update === 'publish'){
                $data = [];
                $data['chat_id'] = '-1001197492471';
                $data['caption'] = json_decode('"\u2764\uFE0F"') .' ' .$media['full_name'] .json_decode('"\u2764\uFE0F"');
                                  //.PHP_EOL .'به ما بپیوندید'
                                  //.PHP_EOL .json_decode('"\uD83D\uDC49"') .' @' .$ch_username;
                $data['disable_notification'] = true;
                //
                if ($type === 'photo') {
                    $data['photo'] = $media['img_standard_resolution'];
                    $result = Request::sendPhoto($data);
                } elseif ($type === 'video') {
                    $data['video'] = $media['vid_standard_resolution'];
                    $result = Request::sendVideo($data);
                }

                // $callback_path     = 'Longman\TelegramBot\Request';
                // $callback_function = 'send' . ucfirst($type);
                // if (!method_exists($callback_path, $callback_function)) {
                //     echo new TelegramException('Methods: ' . $callback_function . ' not found in class Request.');
                // }
                // //
                // $result = $callback_path::$callback_function($data);
                if ($result->isOK()){
                } else {
                    // echo "<pre>";
                    // print_r($data);
                    // echo $callback_path .'  ' .$callback_function .'  ';
                    // print_r($result);
                    // echo "</pre>";
                    InstaDB::updateMedia($media['media_id'], ['scanned' => 0]);
                    echo new TelegramException('post was not published! Error: ' . $result->getErrorCode() . ' ' . $result->getDescription());

                }
                $a = $result;
                //echo "<pre>";
                //print_r($result);
                //echo "</pre>";
                //InstaDB::insertPublished($channel_id, $media['media_id'], $result->getResult()->getmessage_id());
            }

            // echo "<pre> ". $publish_or_update ."\n";
            // print_r($data);
            // echo "</pre>";
            if ($result->isOK() || $publish_or_update === 'update'){
                if ($publish_or_update === 'publish'){
                    $insfa_id = $result->getResult()->getmessage_id();
                } else if ($publish_or_update === 'update') {
                    $insfa_id = $media['insfa_id'];
                    if ($media['insfa_id'] == 0){
                        return true;
                    }
                }

                $media['caption'] = preg_replace("/@([\p{L}\d_.]+)/u", '<a href="https://instagram.com/$1">$1</a>' , $media['caption']);//preg_replace("/@([\p{L}\d_]+)/u", "([at]$1)", $media['caption']);//put atsign into prantheses
                $user_mention = '<a href="' .$media['link'] .'">' .$media['username'] .'</a>';
                //$user_mention_to_tele = '<a href="https://t.me/' . .'/' .$result->getResult()->getmessage_id() .'">' .$media['username'] .'</a>';
                $user_mention_to_tele = '<a href="https://t.me/insfa/' .$insfa_id .'">&#8205;</a>';
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

                if ($publish_or_update === 'publish'){
                    $result = Request::sendMessage($data2);
                } else if ($publish_or_update === 'update') {
                    $data2['message_id'] = $media['post_id'];
                    $result = Request::editMessageText($data2);
                }

                if ($result->isOK()){
                    InstaDB::insertPublished($channel_id, $media['media_id'], $result->getResult()->getmessage_id(), 'message', $a->getResult()->getmessage_id());
                } elseif ($publish_or_update === 'publish') {
                    InstaDB::updateMedia($media['media_id'], ['scanned' => 0]);
                    echo new TelegramException('post was not published! Error: ' . $result->getErrorCode() . ' ' . $result->getDescription());
                }

            }
        }

        if ($result->isOK()){
            return $result->getResult()->getmessage_id();
        } else {
            return false;
        }
    }



    public static function publish_update_method_2($media, $channel = null){
        if (null !== $channel){
            $publish_or_update = 'update';
            $channel_id = $media['channel_id'];
            $ch_username = $media['user_name'];//$posts['channel_username'];
        } else {
            $publish_or_update = 'publish';
            $channel_id = $channel['channel_id'];
            $ch_username = $channel['user_name'];//$posts['channel_username'];
        }


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

        $standard_caption_length = true;
        $limit = 120;
        if (strlen($media['caption'])>$limit){
            $string = substr($media['caption'], 0 ,strpos($media['caption'], ' ', $limit-10));
            $string = wordwrap($media['caption'], $limit);
            $caption = substr($string, 0, strpos($string, "\n", $limit-10)) .'    ' .'(ادامه کپشن در پست بعدی)';
            $standard_caption_length = false;
        } else {
            $caption = $media['caption'];
        }

        //
        //
        //
        if(true){
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
            if ($publish_or_update === 'publish'){
                if ($type === 'photo') {
                    $data['photo'] = $media['img_standard_resolution'];
                    $result = Request::sendPhoto($data);
                } elseif ($type === 'video') {
                    $data['video'] = $media['vid_standard_resolution'];
                    $result = Request::sendVideo($data);
                }
            } else if ($publish_or_update === 'update') {
                $data['message_id'] = $media['post_id'];
                if ($type === 'photo') {
                    $data['photo'] = $media['img_standard_resolution'];
                    $result = Request::editMessageCaption($data);
                } elseif ($type === 'video') {
                    $data['video'] = $media['vid_standard_resolution'];
                    $result = Request::editMessageCaption($data);
                }
            }


            //echo "<pre>";
            //print_r($data);
            //print_r($result);
            //echo "</pre>";
            if ($result->isOK()){
                InstaDB::insertPublished($channel_id, $media['media_id'], $result->getResult()->getmessage_id(), $type, null);
            } else if ($publish_or_update === 'publish'){
                InstaDB::updateMedia($media['media_id'], ['scanned' => 0]);
                echo new TelegramException('post was not published! Error: ' . $result->getErrorCode() . ' ' . $result->getDescription());
            }

            if (!$standard_caption_length){
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

                if ($publish_or_update === 'publish'){
                    $data2['reply_to_message_id'] = $result->getResult()->getmessage_id();
                    $result = Request::sendMessage($data2);
                } else if ($publish_or_update === 'update') {
                    $data2['reply_to_message_id'] = $media['post_id'];
                    $data2['message_id'] = $media['post_id'] + 1;
                    $result = Request::editMessageText($data2);
                }
            }
        }

        if ($result->isOK()){
            return $result->getResult()->getmessage_id();
        } else {
            return false;
        }
    }





    public static function publish_update_method_3($media, $channel = null){
        if (null === $channel){
            $publish_or_update = 'update';
            $channel_id = $media['channel_id'];
            $ch_username = $media['user_name'];//$posts['channel_username'];
        } else {
            $publish_or_update = 'publish';
            $channel_id = $channel['channel_id'];
            $ch_username = $channel['user_name'];//$posts['channel_username'];
        }

        $channels = InstaDB::selectChannels();
        $channel = $channels[array_search('-1001269417831', array_column($channels, 'channel_id'))];
        $post_id = self::publish_update_method_1($media, $channel);

        if ($post_id === false){
            return false;
        }


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
                break;
        }
        //
        //
        //
        $img_url = self::edit_image_method_2($media, $ch_username);
        if ($img_url !== false){
            $media['img_standard_resolution'] = $img_url;
        }


        //
        //

        $standard_caption_length = true;
        $limit = 120;
        if (strlen($media['caption'])>$limit){
            $string = substr($media['caption'], 0 ,strpos($media['caption'], ' ', $limit-10));
            $string = wordwrap($media['caption'], $limit);
            $caption = substr($string, 0, strpos($string, "\n", $limit-10)) .'    ' .'(' .'ادامه در' .' T.me/instagfa_D/' .$post_id .' )';
            $standard_caption_length = false;
        } else {
            $caption = $media['caption'];
        }

        //
        //
        //
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


        if ($publish_or_update === 'publish'){
            if ($type === 'photo') {
                $data['photo'] = $media['img_standard_resolution'];
                $result = Request::sendPhoto($data);
            } elseif ($type === 'video') {
                $data['video'] = $media['vid_standard_resolution'];
                $result = Request::sendVideo($data);
            }
        } else if ($publish_or_update === 'update') {
            $data['message_id'] = $media['post_id'];
            if ($type === 'photo') {
                $data['photo'] = $media['img_standard_resolution'];
                $result = Request::editMessageCaption($data);
            } elseif ($type === 'video') {
                $data['video'] = $media['vid_standard_resolution'];
                $result = Request::editMessageCaption($data);
            }
        }
        if ($result->isOK()){
            InstaDB::insertPublished($channel_id, $media['media_id'], $result->getResult()->getmessage_id(), $type, null);
        } else {
            InstaDB::updateMedia($media['media_id'], ['scanned' => 0]);
            echo new TelegramException('post was not published! Error: ' . $result->getErrorCode() . ' ' . $result->getDescription());

        }

        if ($result->isOK()){
            return true;
        } else {
            return false;
        }
    }









    public static function publish_update_method_4($media, $channel = null){
        if (null === $channel){
            $publish_or_update = 'update';
            $channel_id = $media['channel_id'];
            $ch_username = $media['user_name'];//$posts['channel_username'];
        } else {
            $publish_or_update = 'publish';
            $channel_id = $channel['channel_id'];
            $ch_username = $channel['user_name'];//$posts['channel_username'];
        }


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
                break;
        }
        //
        //
        //
        $img_url = self::edit_image_method_3($media, $ch_username);
        if ($img_url !== false){
            $media['img_standard_resolution'] = $img_url;
        }


        //
        //

        $standard_caption_length = true;
        // $limit = 120;
        // if (strlen($media['caption'])>$limit){
        //     $string = substr($media['caption'], 0 ,strpos($media['caption'], ' ', $limit-10));
        //     $string = wordwrap($media['caption'], $limit);
        //     $caption = substr($string, 0, strpos($string, "\n", $limit-10)) .'    ' .'(' .'ادامه در' .' T.me/instagfa_D/' .$post_id .' )';
        //     $standard_caption_length = false;
        // } else {
            $caption = $media['caption'];
        // }
        if ($media['has_edited_caption']){
            $caption = $media['edited_caption'];
        }

        //
        $atsign = json_decode('"\uD83C\uDD94"');
        $caption = str_replace('@', $atsign, $caption);
        //
        $data = [];
        $data['chat_id'] = $channel_id;
        $data['caption'] = //json_decode('"\u270D\uFE0F"') .' #' .preg_replace("/([^\p{L}\d_ ]+)/u", "", str_replace(" ", "_", $media['full_name']))
                          //.PHP_EOL .$caption
                          $caption
                          .PHP_EOL .PHP_EOL .json_decode('"\uD83D\uDC49"') .' @' .$ch_username;
        $data['disable_notification'] = true;
        //


        if ($publish_or_update === 'publish'){
            if ($type === 'photo') {
                $data['photo'] = $media['img_standard_resolution'];
                $result = Request::sendPhoto($data);
            } elseif ($type === 'video') {
                $data['video'] = $media['vid_standard_resolution'];
                $result = Request::sendVideo($data);
            }
        } else if ($publish_or_update === 'update') {
            $data['message_id'] = $media['post_id'];
            if ($type === 'photo') {
                $data['photo'] = $media['img_standard_resolution'];
                $result = Request::editMessageCaption($data);
            } elseif ($type === 'video') {
                $data['video'] = $media['vid_standard_resolution'];
                $result = Request::editMessageCaption($data);
            }
        }
        if ($result->isOK()){
            InstaDB::insertPublished($channel_id, $media['media_id'], $result->getResult()->getmessage_id(), $type, null);
        } else {
            InstaDB::updateMedia($media['media_id'], ['scanned' => 0]);
            echo new TelegramException('post was not published! Error: ' . $result->getErrorCode() . ' ' . $result->getDescription());

        }

        if ($result->isOK()){
            return true;
        } else {
            return false;
        }
    }











    public static function download_media($media){
        switch ($media['type']) {
            case 'image':
                $file_url  = $media['img_standard_resolution'];
                $file_name = $media['media_id'] .'.jpg';
                $file_path = '/images/' .$file_name;
                break;
            case 'video':
                $file_url  = $media['vid_standard_resolution'];
                $file_name = $media['media_id'] .'.mp4';
                $file_path = '/videos/' .$file_name;
                break;
            case 'carousel':
                $type = 'photo';
                break;
            default:
                break;
        }
        // if (empty(Request::$telegram->getDownloadPath())) {
        //     echo new TelegramException('Download path not set!');
        // }
        // $file_path = Request::$telegram->getDownloadPath() .$file_path;
        $upOne = realpath(__DIR__ . '/..');
        $file_path = $upOne .'/Downloads' .$file_path;
        $file_dir = dirname($file_path);
        $file_dir_upload = dirname($upOne .'/Uploads' .$file_path);
        //For safety reasons, first try to create the directory, then check that it exists.
        //This is in case some other process has created the folder in the meantime.
        if (!@mkdir($file_dir, 0755, true) && !is_dir($file_dir)) {
            echo new TelegramException('Directory ' . $file_dir . ' can\'t be created');
        }

        if (!@mkdir($file_dir_upload, 0755, true) && !is_dir($file_dir_upload)) {
            echo new TelegramException('upload Directory ' . $file_dir_upload . ' can\'t be created');
        }

        if (file_exists($file_path)){
            return true;
        }



        // check allow_url_fopen settings
        if (ini_get('allow_url_fopen') && extension_loaded('openssl')) {
                file_put_contents($file_path, file_get_contents($file_url));
        }
        // make sure cURL enable
        elseif (function_exists('curl_version')) {
            $ch = curl_init($file_url);
            $fp = fopen($file_path, 'wb');
            //
            $dynamic_ip = "" . mt_rand(0, 255) . "." . mt_rand(0, 255) . "." . mt_rand(0, 255) . "." . mt_rand(0, 255);
            $curl_option = array(
                CURLOPT_FILE           => $fp,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER         => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_ENCODING       => "",
                CURLOPT_AUTOREFERER    => true,
                CURLOPT_CONNECTTIMEOUT => 120,
                CURLOPT_TIMEOUT        => 120,
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13',
                CURLOPT_HTTPHEADER     => array("REMOTE_ADDR: $dynamic_ip", "HTTP_X_FORWARDED_FOR: $dynamic_ip"),
            );
            curl_setopt_array($ch, $curl_option);
            curl_exec($ch);
            curl_close($ch);
            fclose($fp);
        } else {
            echo "download error, cUrl or file_put";
        }

        return true;
    }




    public static function download_profile($media){

        $file_url  = $media['profile_pic_url_hd'];
        $file_name = $media['user_id'] .'.jpg';
        $file_path = '/profile_pic/' .$file_name;


        // if (empty(Request::$telegram->getDownloadPath())) {
        //     echo new TelegramException('Download path not set!');
        // }
        // $file_path = Request::$telegram->getDownloadPath() .$file_path;
        $upOne = realpath(__DIR__ . '/..');
        $file_path = $upOne .'/Downloads' .$file_path;
        $file_dir = dirname($file_path);
        //For safety reasons, first try to create the directory, then check that it exists.
        //This is in case some other process has created the folder in the meantime.
        if (!@mkdir($file_dir, 0755, true) && !is_dir($file_dir)) {
            echo new TelegramException('Directory ' . $file_dir . ' can\'t be created');
        }

        // if (file_exists($file_path)){
        //     return true;
        // }


        // check allow_url_fopen settings
        if (ini_get('allow_url_fopen') && extension_loaded('openssl')) {
            file_put_contents($file_path, file_get_contents($file_url));
        }
        // make sure cURL enable
        elseif (function_exists('curl_version')) {
            $ch = curl_init($file_url);
            $fp = fopen($file_path, 'wb');
            //
            $dynamic_ip = "" . mt_rand(0, 255) . "." . mt_rand(0, 255) . "." . mt_rand(0, 255) . "." . mt_rand(0, 255);
            $curl_option = array(
                CURLOPT_FILE           => $fp,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER         => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_ENCODING       => "",
                CURLOPT_AUTOREFERER    => true,
                CURLOPT_CONNECTTIMEOUT => 120,
                CURLOPT_TIMEOUT        => 120,
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13',
                CURLOPT_HTTPHEADER     => array("REMOTE_ADDR: $dynamic_ip", "HTTP_X_FORWARDED_FOR: $dynamic_ip"),
            );
            curl_setopt_array($ch, $curl_option);
            curl_exec($ch);
            curl_close($ch);
            fclose($fp);
        } else {
            echo "download error, cUrl or file_put";
        }

        return true;
    }



    public static function edit_image_method_1($media, $ch_username){

        if ($media['type'] !== 'image'){
            return false;
        }
        self::download_media($media);
        self::download_profile($media);
        $dir_media = realpath(__DIR__ . '/..') .'/Downloads/images/' .$media['media_id'] .'.jpg';
        $dir_prof = realpath(__DIR__ . '/..') .'/Downloads/profile_pic/' .$media['user_id'] .'.jpg';
        $dir_output = realpath(__DIR__ . '/..') .'/Uploads/images/' .$media['media_id'] .'.jpg';
        $base_url = 'https://www.kafegame.com/bot1/src/instagfaBot/Uploads/images/';
        $dir_font_title = realpath(__DIR__ . '/..') .'/Src/fonts/Roboto-Regular.ttf';

        //
        $image_media = imagecreatefromjpeg($dir_media);
        $image_prof_pic = imagecreatefromjpeg($dir_prof);
        if($image_media === false || $image_prof_pic === false){
            InstaDB::updateMedia($media['media_id'], ['scanned' => 0]);
            return false;
        }

        //
        $x_media = imagesx($image_media);
        $y_media = imagesy($image_media);
        // $res_media = imageresolution($image_media);
        $x_pic = imagesx($image_prof_pic);
        $y_pic = imagesy($image_prof_pic);
        // $res_pic = imageresolution($image_prof_pic);
        $y_header = $y_media/17; //100;
        $y_footer = $y_header;
        $x = $x_media;
        $y = $y_media + $y_header + $y_footer;
        $prof_pic_ratio = 0.8;
        $prof_pic_offset = 20;
        $header_title_offset = 15;
        $header_font_size_1 = round($y_header/2.5); //round($x/35);
        $header_font_size_2 = $header_font_size_1 + 2;

        // making template
        $image_output = imagecreatetruecolor($x, $y);
        // imageresolution($image_output, $res_media[0], round($res_media[1]*$y/$y_media));
        $bg = imagecolorallocate($image_output, 255, 255, 255); // white background
        imagefill($image_output, 0, 0, $bg);

        // header
        $image_header = imagecreatetruecolor($x, $y_header);
        $bg = imagecolorallocate($image_header, 255, 255, 255); // white background
        imagefill($image_header, 0, 0, $bg);

        $image_mask = imagecreatetruecolor($x_pic, $y_pic);
        $bg = imagecolorallocate($image_mask, 255, 255, 255); // white background
        imagefill($image_mask, 0, 0, $bg);
        $e = imagecolorallocate($image_mask, 20, 20, 20); // black mask color
        imagefilledellipse($image_mask, $x_pic/2, $y_pic/2, 0.96*$x_pic, 0.96*$y_pic, $e);
        imagecolortransparent($image_mask, $e);

        imagecopymerge($image_prof_pic, $image_mask, 0, 0, 0, 0, imagesx($image_mask), imagesy($image_mask), 100);
        $e1 = imagecolorallocate($image_prof_pic, 0, 0, 0);
        imagesetthickness($image_prof_pic, 2);
        // imageellipse($image_prof_pic, $x_pic/2, $y_pic/2, 0.99*$x_pic, 0.99*$y_pic, $e);
        imagearc($image_prof_pic, $x_pic/2, $y_pic/2, 0.99*$x_pic, 0.99*$y_pic, 0, 360, $e1);

        imagecopyresized($image_header, $image_prof_pic, $prof_pic_offset, ($y_header*(1-$prof_pic_ratio))/2, 0, 0, $prof_pic_ratio*$y_header,$prof_pic_ratio*$y_header, $x_pic, $y_pic);
        $black = imagecolorallocate($image_header, 0, 0, 0);
        $blue = imagecolorallocate($image_header, 30, 144, 255);
        $header_font_size_1 = $header_font_size_1 + 1;
        $header_font_size_2 = $header_font_size_2 + 1;
        do {
            $header_font_size_1 = $header_font_size_1 - 1;
            $header_font_size_2 = $header_font_size_2 - 1;
            $box1 = @imageTTFBbox($header_font_size_1, 0, $dir_font_title, $media['username']);
            $width1 = abs($box1[4] - $box1[0]);
            $height1 = abs($box1[5] - $box1[1]);
            $box2 = @imageTTFBbox($header_font_size_2, 0, $dir_font_title, '@' .$ch_username);
            $width2 = abs($box2[4] - $box2[0]);
            $height2 = abs($box2[5] - $box2[1]);
        } while ($width1+$width2+4*$header_title_offset+$prof_pic_ratio*$y_header + $prof_pic_offset >= $x);
        imagettftext($image_header, $header_font_size_1, 0, $prof_pic_ratio*$y_header + $prof_pic_offset + $header_title_offset, ($y_header+$height1-0.1*$header_font_size_1)/2, $black, $dir_font_title, $media['username']);
        imagettftext($image_header, $header_font_size_2, 0, $prof_pic_ratio*$y_header + $prof_pic_offset + 3*$header_title_offset + $width1, ($y_header+$height2-0.1*$header_font_size_2)/2, $blue, $dir_font_title, '@' .$ch_username);

        imagecopymerge($image_output, $image_header, 0, 0, 0, 0, $x, $y_header, 100);
        imagedestroy($image_mask);
        imagedestroy($image_prof_pic);
        imagedestroy($image_header);

        //image
        imagecopymerge($image_output, $image_media, 0, $y_header, 0, 0, $x_media, $y_media, 100);
        imagedestroy($image_media);
        // footer
        // $image_footer

        // footer 2

        // caption

        // output
        imagejpeg($image_output, $dir_output);
        imagedestroy($image_output);
        $img_Add = $base_url .$media['media_id'] .'.jpg';
        if (file_exists($dir_output)){
            return $img_Add;
        } else {
            return false;
        }
    }


    public static function edit_image_method_2($media, $ch_username){

        if ($media['type'] !== 'image'){
            return false;
        }

        self::download_media($media);
        self::download_profile($media);
        $dir_media = realpath(__DIR__ . '/..') .'/Downloads/images/' .$media['media_id'] .'.jpg';
        $dir_prof = realpath(__DIR__ . '/..') .'/Downloads/profile_pic/' .$media['user_id'] .'.jpg';
        $dir_output = realpath(__DIR__ . '/..') .'/Uploads/images/' .$media['media_id'] .'.jpg';
        $base_url = 'https://www.kafegame.com/bot1/src/instagfaBot/Uploads/images/';
        $dir_font_title = realpath(__DIR__ . '/..') .'/Src/fonts/Roboto-Regular.ttf';
        $dir_img_header = realpath(__DIR__ . '/..') .'/Src/imgs/Lay-1.jpg';
        $dir_img_footer = realpath(__DIR__ . '/..') .'/Src/imgs/Lay-2.jpg';

        //
        $image_media = imagecreatefromjpeg($dir_media);
        $image_prof_pic = imagecreatefromjpeg($dir_prof);
        if($image_media === false || $image_prof_pic === false){
            InstaDB::updateMedia($media['media_id'], ['scanned' => 0]);
            return false;
        }
        $image_header = imagecreatefromjpeg($dir_img_header);
        $image_footer = imagecreatefromjpeg($dir_img_footer);
        $x = imagesx($image_header);
        $y_header = imagesy($image_header);
        $y_footer = imagesy($image_footer);
        //
        $x_media = imagesx($image_media);
        $y_media = imagesy($image_media);
        $y_media_new = ($x / $x_media) * $y_media;
        // $res_media = imageresolution($image_media);
        $x_pic = imagesx($image_prof_pic);
        $y_pic = imagesy($image_prof_pic);
        // $res_pic = imageresolution($image_prof_pic);

        $y = $y_media_new + $y_header + $y_footer;
        $prof_pic_ratio = 0.65;
        $prof_pic_x = 23;
        $prof_pic_y = 22;
        $header_title_offset = 15;
        $header_font_size_1 = 25;
        $header_font_size_2 = $header_font_size_1 + 4;

        // making template
        $image_output = imagecreatetruecolor($x, $y);
        // imageresolution($image_output, $res_media[0], round($res_media[1]*$y/$y_media));
        $bg = imagecolorallocate($image_output, 255, 255, 255); // white background
        imagefill($image_output, 0, 0, $bg);

        // header
        $image_mask = imagecreatetruecolor($x_pic, $y_pic);
        $bg = imagecolorallocate($image_mask, 255, 255, 255); // white background
        imagefill($image_mask, 0, 0, $bg);
        $e = imagecolorallocate($image_mask, 20, 20, 20); // black mask color
        imagefilledellipse($image_mask, $x_pic/2, $y_pic/2, 0.98*$x_pic, 0.98*$y_pic, $e);
        imagecolortransparent($image_mask, $e);

        imagecopymerge($image_prof_pic, $image_mask, 0, 0, 0, 0, imagesx($image_mask), imagesy($image_mask), 100);
        $e1 = imagecolorallocate($image_prof_pic, 0, 0, 0);
        imagesetthickness($image_prof_pic, 2);
        imagearc($image_prof_pic, $x_pic/2, $y_pic/2, 0.99*$x_pic, 0.99*$y_pic, 0, 360, $e1);

        imagecopyresized($image_header, $image_prof_pic, $prof_pic_x, $prof_pic_y, 0, 0, $prof_pic_ratio*$y_header,$prof_pic_ratio*$y_header, $x_pic, $y_pic);
        $black = imagecolorallocate($image_header, 0, 0, 0);
        $blue = imagecolorallocate($image_header, 30, 144, 255);
        $header_font_size_1 = $header_font_size_1 + 1;
        $header_font_size_2 = $header_font_size_2 + 1;
        do {
            $header_font_size_1 = $header_font_size_1 - 1;
            $header_font_size_2 = $header_font_size_2 - 1;
            $box1 = @imageTTFBbox($header_font_size_1, 0, $dir_font_title, $media['username']);
            $width1 = abs($box1[4] - $box1[0]);
            $height1 = abs($box1[5] - $box1[1]);
            $box2 = @imageTTFBbox($header_font_size_2, 0, $dir_font_title, '@' .$ch_username);
            $width2 = abs($box2[4] - $box2[0]);
            $height2 = abs($box2[5] - $box2[1]);
        } while ($width1+$width2+4*$header_title_offset+$prof_pic_ratio*$y_header + $prof_pic_x >= $x);
        imagettftext($image_header, $header_font_size_1, 0, $prof_pic_ratio*$y_header + $prof_pic_x + $header_title_offset, ($y_header+$height1-0.65*$header_font_size_1)/2, $black, $dir_font_title, $media['username']);
        imagettftext($image_header, $header_font_size_2, 0, $prof_pic_ratio*$y_header + $prof_pic_x + 3*$header_title_offset + $width1, ($y_header+$height2-0.75*$header_font_size_2)/2, $blue, $dir_font_title, '@' .$ch_username);

        imagecopymerge($image_output, $image_header, 0, 0, 0, 0, $x, $y_header, 100);
        imagedestroy($image_mask);
        imagedestroy($image_prof_pic);
        imagedestroy($image_header);

        //image
        // imagecopymerge($image_output, $image_media, 0, $y_header, 0, 0, $x_media, $y_media, 100);
        imagecopyresized($image_output, $image_media, 0, $y_header, 0, 0, $x, $y_media_new, $x_media, $y_media);
        imagedestroy($image_media);
        // footer
        // $image_footer
        imagecopymerge($image_output, $image_footer, 0, $y_header+$y_media_new, 0, 0, $x, $y_footer, 100);
        // footer 2

        // caption

        // output
        imagejpeg($image_output, $dir_output);
        imagedestroy($image_output);


        $img_Add = $base_url .$media['media_id'] .'.jpg';
        if (file_exists($dir_output)){
            return $img_Add;
        } else {
            return false;
        }
    }







    public static function edit_image_method_3($media, $ch_username = null, $isTest = false){

        if ($media['type'] !== 'image'){
            return false;
        }
        if ($media['has_edited_caption']){
            $channels = InstaDB::selectChannels();
            $channel = $channels[array_search($ch_username, array_column($channels, 'user_name'))];
            $ch_id = $channel['channel_id'];
            $admin = InstaDB::selectAdminsChannels(['admin_id' => $media['edited_by'], 'channel_id' => $ch_id]);
            //
            $media['username'] = $admin[0]['nick_name'];
            $media['profile_pic_url_hd'] = $admin[0]['avatarurl'];
            $media['user_id'] = $admin[0]['admin_id'];
        } else {
            $media['username'] = 'Test username';
            $media['profile_pic_url_hd'] = 'https://www.kafegame.com/avatars2/man-1.jpg';
            $media['user_id'] = '1212';
        }
        // echo "<pre>", print_r($channel), print_r($admin), $media['username'], $media['profile_pic_url_hd'], $media['user_id'],"</pre>";
        if ($isTest){

        }

        self::download_media($media);
        self::download_profile($media);
        $end_url = time();
        $dir_media = realpath(__DIR__ . '/..') .'/Downloads/images/' .$media['media_id'] .'.jpg';
        $dir_prof = realpath(__DIR__ . '/..') .'/Downloads/profile_pic/' .$media['user_id'] .'.jpg';
        $dir_output = realpath(__DIR__ . '/..') .'/Uploads/images/' .$media['media_id'] .$end_url .'.jpg';
        $base_url = 'https://www.kafegame.com/bot1/src/instagfaBot/Uploads/images/';
        $dir_font_title = realpath(__DIR__ . '/..') .'/Src/fonts/Roboto-Regular.ttf';
        $dir_img_header = realpath(__DIR__ . '/..') .'/Src/imgs/Lay-1.jpg';
        $dir_img_footer = realpath(__DIR__ . '/..') .'/Src/imgs/Lay-2.jpg';

        //
        $image_media = imagecreatefromjpeg($dir_media);
        $image_prof_pic = imagecreatefromjpeg($dir_prof);
        if($image_media === false || $image_prof_pic === false){
            InstaDB::updateMedia($media['media_id'], ['scanned' => 0]);
            return false;
        }
        $image_header = imagecreatefromjpeg($dir_img_header);
        $image_footer = imagecreatefromjpeg($dir_img_footer);
        $x = imagesx($image_header);
        $y_header = imagesy($image_header);
        $y_footer = imagesy($image_footer);
        //
        $x_media = imagesx($image_media);
        $y_media = imagesy($image_media);
        $y_media_new = ($x / $x_media) * $y_media;
        // $res_media = imageresolution($image_media);
        $x_pic = imagesx($image_prof_pic);
        $y_pic = imagesy($image_prof_pic);
        // $res_pic = imageresolution($image_prof_pic);

        $y = $y_media_new + $y_header + $y_footer;
        $prof_pic_ratio = 0.65;
        $prof_pic_x = 23;
        $prof_pic_y = 22;
        $header_title_offset = 15;
        $header_font_size_1 = 25;
        $header_font_size_2 = $header_font_size_1 + 4;

        // making template
        $image_output = imagecreatetruecolor($x, $y);
        // imageresolution($image_output, $res_media[0], round($res_media[1]*$y/$y_media));
        $bg = imagecolorallocate($image_output, 255, 255, 255); // white background
        imagefill($image_output, 0, 0, $bg);

        // header
        $image_mask = imagecreatetruecolor($x_pic, $y_pic);
        $bg = imagecolorallocate($image_mask, 255, 255, 255); // white background
        imagefill($image_mask, 0, 0, $bg);
        $e = imagecolorallocate($image_mask, 20, 20, 20); // black mask color
        imagefilledellipse($image_mask, $x_pic/2, $y_pic/2, 0.98*$x_pic, 0.98*$y_pic, $e);
        imagecolortransparent($image_mask, $e);

        imagecopymerge($image_prof_pic, $image_mask, 0, 0, 0, 0, imagesx($image_mask), imagesy($image_mask), 100);
        $e1 = imagecolorallocate($image_prof_pic, 0, 0, 0);
        imagesetthickness($image_prof_pic, 2);
        imagearc($image_prof_pic, $x_pic/2, $y_pic/2, 0.99*$x_pic, 0.99*$y_pic, 0, 360, $e1);

        imagecopyresized($image_header, $image_prof_pic, $prof_pic_x, $prof_pic_y, 0, 0, $prof_pic_ratio*$y_header,$prof_pic_ratio*$y_header, $x_pic, $y_pic);
        $black = imagecolorallocate($image_header, 0, 0, 0);
        $blue = imagecolorallocate($image_header, 30, 144, 255);
        $header_font_size_1 = $header_font_size_1 + 1;
        $header_font_size_2 = $header_font_size_2 + 1;
        do {
            $header_font_size_1 = $header_font_size_1 - 1;
            $header_font_size_2 = $header_font_size_2 - 1;
            $box1 = @imageTTFBbox($header_font_size_1, 0, $dir_font_title, $media['username']);
            $width1 = abs($box1[4] - $box1[0]);
            $height1 = abs($box1[5] - $box1[1]);
            $box2 = @imageTTFBbox($header_font_size_2, 0, $dir_font_title, '@' .$ch_username);
            $width2 = abs($box2[4] - $box2[0]);
            $height2 = abs($box2[5] - $box2[1]);
        } while ($width1+$width2+4*$header_title_offset+$prof_pic_ratio*$y_header + $prof_pic_x >= $x);
        imagettftext($image_header, $header_font_size_1, 0, $prof_pic_ratio*$y_header + $prof_pic_x + $header_title_offset, ($y_header+$height1-0.65*$header_font_size_1)/2, $black, $dir_font_title, $media['username']);
        imagettftext($image_header, $header_font_size_2, 0, $prof_pic_ratio*$y_header + $prof_pic_x + 3*$header_title_offset + $width1, ($y_header+$height2-0.75*$header_font_size_2)/2, $blue, $dir_font_title, '@' .$ch_username);


        imagecopymerge($image_output, $image_header, 0, 0, 0, 0, $x, $y_header, 100);
        imagedestroy($image_mask);
        imagedestroy($image_prof_pic);
        imagedestroy($image_header);

        //image
        // imagecopymerge($image_output, $image_media, 0, $y_header, 0, 0, $x_media, $y_media, 100);
        imagecopyresized($image_output, $image_media, 0, $y_header, 0, 0, $x, $y_media_new, $x_media, $y_media);
        imagedestroy($image_media);
        if ($isTest){
            imagettftext($image_output, 40, 30, $y_header, $y_header+100, $blue, $dir_font_title, 'Test');
            imagettftext($image_output, 40, 30, 2*$y_header, 2*$y_header+100, $blue, $dir_font_title, 'Test');
            imagettftext($image_output, 40, 30, 2*$y_header, 3*$y_header+100, $blue, $dir_font_title, 'Test');
            imagettftext($image_output, 40, 30, 3*$y_header, 2*$y_header+100, $blue, $dir_font_title, 'Test');
            imagettftext($image_output, 40, 30, 4*$y_header, 2*$y_header+100, $blue, $dir_font_title, 'Test');
            imagettftext($image_output, 40, 30, 2*$y_header, 4*$y_header+100, $blue, $dir_font_title, 'Test');
            imagettftext($image_output, 40, 30, 4*$y_header, 3*$y_header+100, $blue, $dir_font_title, 'Test');
            imagettftext($image_output, 40, 30, 3*$y_header, 4*$y_header+100, $blue, $dir_font_title, 'Test');
            imagettftext($image_output, 40, 30, 1*$y_header, 4*$y_header+100, $blue, $dir_font_title, 'Test');
            imagettftext($image_output, 40, 30, 4*$y_header, 1*$y_header+100, $blue, $dir_font_title, 'Test');
            imagettftext($image_output, 40, 30, 1*$y_header, 5*$y_header+100, $blue, $dir_font_title, 'Test');
            imagettftext($image_output, 40, 30, 5*$y_header, 1*$y_header+100, $blue, $dir_font_title, 'Test');
            imagettftext($image_output, 40, 30, 3*$y_header, 5*$y_header+100, $blue, $dir_font_title, 'Test');
            imagettftext($image_output, 40, 30, 5*$y_header, 3*$y_header+100, $blue, $dir_font_title, 'Test');
            imagettftext($image_output, 40, 30, 4*$y_header, 4*$y_header+100, $blue, $dir_font_title, 'Test');
            imagettftext($image_output, 40, 30, 3*$y_header, 3*$y_header+100, $blue, $dir_font_title, 'Test');
        }
        // footer
        // $image_footer
        imagecopymerge($image_output, $image_footer, 0, $y_header+$y_media_new, 0, 0, $x, $y_footer, 100);
        // footer 2

        // caption

        // output
        imagejpeg($image_output, $dir_output);
        imagedestroy($image_output);


        $img_Add = $base_url .$media['media_id'] .$end_url .'.jpg';
        if (file_exists($dir_output)){
            return $img_Add;
        } else {
            return false;
        }
    }







    public function getVersion()
    {
        return $this->version;
    }


}
