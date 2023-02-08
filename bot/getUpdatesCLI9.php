#!/usr/bin/env php
<?php

date_default_timezone_set('Asia/Tehran');
// Bash script
// while true; do ./getUpdatesCLI.php; done

// Load composer
require __DIR__ . '/vendor/autoload.php';

$API_KEY = '509685283:AAHWF67LP_E8MSQG0ekdxMovy-aVJFxbSNI';
$BOT_NAME = 'recipeRobot';

$path = __DIR__ . '/src/' .$BOT_NAME .'/';
$commands_path = $path .'/Commands';
$download_path = $path .'/Downloads';
$upload_path = $path .'/Uploads';
$special_functions_path = $path .'/Functions';


$mysql_credentials = [
   'host'     => 'localhost',
   'user'     => 'kafegame_PhpBot',
   'password' => 'qwertyuiop1234567890',
   'database' => 'kafegame_recipeRobot',
];

$how_long_in_minute = 1;
$real_execution_time_limit = $how_long_in_minute * 60 - 1; // one minute

if (pcntl_fork()) {
	$number_per_minute = 60;
	$sleeptime = 60 / $number_per_minute;
    $sleeptime_milisecond = round((60*1000000) / $number_per_minute);
	$a = $number_per_minute * $how_long_in_minute;
	while($a > 1){
		$a = $a - 1;
		try {

            echo "process";
			$telegram = new Longman\TelegramBot\Telegram($API_KEY, $BOT_NAME);

			//Longman\TelegramBot\TelegramLog::initialize($your_external_monolog_instance);
			Longman\TelegramBot\TelegramLog::initErrorLog($path .'_error.log');
			//Longman\TelegramBot\TelegramLog::initDebugLog($path .'_debug.log');
			Longman\TelegramBot\TelegramLog::initUpdateLog($path .'_update.log');

			$telegram->enableMySql($mysql_credentials);
			// Enable MySQL with table prefix
			//$telegram->enableMySql($mysql_credentials, $BOT_NAME . '_');

			$telegram->addCommandsPath($commands_path);

			//$telegram->enableAdmin(63200004);
			$telegram->enableAdmins([63200004, 62715990, 270783544, 65332476]);
			//$telegram->setCommandConfig('sendtochannel', ['your_channel' => '@instagfa']);
			//$telegram->setCommandConfig('date', ['google_api_key' => 'your_google_api_key_here']);
			//$telegram->setCommandConfig('sendtochannel', ['your_channel' => '@instagfa']);
			$telegram->setDownloadPath($download_path);
			$telegram->setUploadPath($upload_path);
            if (!file_exists($download_path)) {
                mkdir($download_path, 0777, true);
            }
            if (!file_exists($upload_path)) {
                mkdir($upload_path, 0777, true);
            }

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
            echo " starts ";
			$serverResponse = $telegram->handleGetUpdates();

			if ($serverResponse->isOk()) {
				$updateCount = count($serverResponse->getResult());
				echo date('Y-m-d H:i:s', time()) . ' - Processed ' . $updateCount . ' updates';
			} else {
				echo date('Y-m-d H:i:s', time()) . ' - Failed to fetch updates' . PHP_EOL;
				echo $serverResponse->printError();
			}
		} catch (Longman\TelegramBot\Exception\TelegramException $e) {
			echo $e;
			Longman\TelegramBot\TelegramLog::error($e);
		} catch (Longman\TelegramBot\Exception\TelegramLogException $e) {
			echo $e;
		}
		//sleep($sleeptime);
        usleep($sleeptime_milisecond);
	}
} else {
    sleep($real_execution_time_limit);
    posix_kill(posix_getppid(), SIGKILL);
}
