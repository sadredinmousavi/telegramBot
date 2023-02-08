#!/usr/bin/env php
<?php
//namespace InstaBot;
// Bash script
// while true; do ./getUpdatesCLI.php; done

// Load composer
//require __DIR__ . '/Mylib/InstagramHelper.php';
//require_once __DIR__ . '/Mylib/User.php';
//require_once __DIR__ . '/Mylib/Media.php';
require __DIR__ . '/core.php';

//use \Mylib\InstagramHelper as Instagram;
use \Mylib\DB;
//use \Mylib\User;
//use \Mylib\Media;

// Enter your MySQL database credentials
$mysql_credentials = [
   'host'     => 'localhost',
   'user'     => 'kafegame_PhpBot',
   'password' => 'qwertyuiop1234567890',
   'database' => 'kafegame_instagram',
];


$instagram = new core();

//$instagram->enableMySql($mysql_credentials);
$instagram->enableMySql();

//$instagram->getUserfromInsta('realshadmehr', true);
//$instagram->searchUserfromInsta('sadegh');
//$instagram->getUserLastMediafromInsta('realshadmehr');

// $a = ['realshadmehr', 'rezagolzar', 'taraneh_alidoosti', 'ghazal.shakeri.official', 'aslooni'];
// foreach($a as $person){
//     $instagram->getUserfromInsta($person);
//     $instagram->getUserLastMediafromInsta($person);
// }


// $a = DB::selectMedia(['videos' => true, 'images' => true, 'carousels' => true], ['not_published_in' => -12123414, 'user_belongs_to_channel' => -12123414, '$scanned' => 1, 'limit_rows' => 1, 'sort_by' => 'likes'], $date_from = strtotime("-1000 day"));
// echo "<pre>";
// print_r($a);
// echo "</pre>";
// DB::insertPublished(-12123414, $a[0]['media_id'], 89);
// //echo $a[0]['likes'];
// DB::insertUsersToChannels(-12123414, ['305851563' => 0, '514001457' => 0, '5697303232' => 0, '123' => 0]);
// $y = DB::deleteUsersToChannels(-12123414, ['305851563' , '123']);
// $x = DB::selectUsersToChannels(-12123414);
// echo "<pre>";
// print_r($y);
// print_r($x);
// echo "</pre>";
// //$b = DB::updateMedia($a[0]['media_id'], ['published' => 1]);//deprec
// $s = DB::selectChannels();
// $d = DB::selectUsers('rezagolzar');
// echo "<pre>";
// print_r($s);
// print_r($d);
// echo "</pre>";
$a = DB::selectMedia(['videos' => true, 'images' => true, 'carousels' => true], ['scanned' => 0, 'special_user_name' => 'realshadmehr']);
//$a = DB::selectMedia(['videos' => true, 'images' => true, 'carousels' => true], ['scanned' => 0, 'special_user_name' => 'saeedmaroof4444']);
//$instagram->getUserLastMediafromInsta('realshadmehr');

//$a = DB::selectPublished();
$instagram->getRelatedMediafromInsta('-1001105299360', 2);
//echo "<pre>";
//print_r($a);
//echo "</pre>";



//$instagram = new Instagram;


//$instagram -> initialize('get_user_info', ['keyword' => 'mj_ghobadi']);
// call action
//$instagram -> setInputKey('keyword');
// get and return ajax response
//$result_raw = $instagram -> setInstagramRequest();
//
//$result = substr($result_raw, strpos($result_raw, ':[{') + 2);
//$result = substr($result, 0, strpos($result, '}]}') + 1);
//$insta_array = json_decode($result, TRUE);
//$insta_array = json_decode($result_raw, TRUE);

//$user = new User($insta_array);
//echo $user->getprofile_pic_url();
//echo PHP_EOL .'asd' .PHP_EOL;
//echo $user->getProperty('profile_pic_url');
//echo PHP_EOL .'asd' .PHP_EOL;
//print_r($insta_array) ;
