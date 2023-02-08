#!/usr/bin/env php
<?php

date_default_timezone_set('Asia/Tehran');
require __DIR__ . '/vendor/autoload.php';
require_once '/home2/kafegame/public_html/instabot/core.php';

$API_KEY = '453489315:AAGIPZTnKRljuAq7jdR6TPLiHgZlH5YejzM';
$BOT_NAME = 'instagfaBot';

$path = __DIR__ . '/src/' .$BOT_NAME .'/';
$commands_path = $path .'/Commands';
$download_path = $path .'/Downloads';
$upload_path = $path .'/Uploads';
$special_functions_path = $path .'/Functions';


$mysql_credentials = [
   'host'     => 'localhost',
   'user'     => 'kafegame_PhpBot',
   'password' => 'qwertyuiop1234567890',
   'database' => 'kafegame_instagfa',
];

$commands = ['/fetchdailyposts'];

foreach ($commands as $comm) {
    try {
        $telegram = new Longman\TelegramBot\Telegram($API_KEY, $BOT_NAME);
        //Longman\TelegramBot\TelegramLog::initialize($your_external_monolog_instance);
        Longman\TelegramBot\TelegramLog::initErrorLog($path .'_error.log');
        //Longman\TelegramBot\TelegramLog::initDebugLog($path .'_debug.log');
        Longman\TelegramBot\TelegramLog::initUpdateLog($path .'_update.log');
        $telegram->enableMySql($mysql_credentials);
        $telegram->addCommandsPath($commands_path);
        $telegram->enableAdmins([63200004, 62715990, 270783544, 65332476]);
        $telegram->setDownloadPath($download_path);
        $telegram->setUploadPath($upload_path);
        if (!file_exists($download_path)) mkdir($download_path, 0777, true);
        if (!file_exists($upload_path))   mkdir($upload_path, 0777, true);
        if (file_exists($special_functions_path)) {
            try {
                //Get all "*.php" files
                $files = new RegexIterator(
                    new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($path)
                    ),
                    '/^.+.php$/'
                );
                foreach ($files as $file) {
                    require_once $file->getPathname();
                }
            } catch (Exception $e) {
                throw new TelegramException('Error getting special functions from path: ' . $path);
            }
        }
        $telegram->enableLimiter();
        //
        $telegram->runCommands($commands);
        //
    } catch (Longman\TelegramBot\Exception\TelegramException $e) {
        Longman\TelegramBot\TelegramLog::error($e);
    } catch (Longman\TelegramBot\Exception\TelegramLogException $e) {
    }
}
