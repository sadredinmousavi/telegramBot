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


$Crawler = new core();

//$Crawler->enableMySql($mysql_credentials);
$Crawler->enableMySql();

//$Crawler->getCategoryFoodList('rice');
$Crawler->ChiBepazamFullCrawl();
// $a = DB::selectMedia(['videos' => true, 'images' => true, 'carousels' => true], ['not_published_in' => -12123414, 'user_belongs_to_channel' => -12123414, '$scanned' => 1, 'limit_rows' => 1, 'sort_by' => 'likes'], $date_from = strtotime("-1000 day"));
// echo "<pre>";
// print_r($a);
// echo "</pre>";
