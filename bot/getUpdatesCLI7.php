#!/usr/bin/env php
<?php
/**
 * README
 * This configuration file is intented to run the bot with the getUpdates method
 * Uncommented parameters must be filled
 */

// Bash script
// while true; do ./getUpdatesCLI.php; done

// Load composer
require __DIR__ . '/vendor/autoload.php';

// Add you bot's API key and name
$API_KEY = '385935789:AAF5DNAt0K0peJIWPM27s7bv4yNhJ4eODT0';
$BOT_NAME = 'iziChatbot';

// Define a path for your custom commands
$commands_path = __DIR__ . '/src/Commands/' .$BOT_NAME .'/';
$path = __DIR__ . '/src/Commands/' .$BOT_NAME .'/';

// Enter your MySQL database credentials
$mysql_credentials = [
   'host'     => 'localhost',
   'user'     => 'kafegame_PhpBot',
   'password' => 'qwertyuiop1234567890',
   'database' => 'kafegame_iziChat',
];

$real_execution_time_limit = 59; // one minute

if (pcntl_fork()) {
	$num = 60;
	$sleeptime = 60 / $num;
	$a = $num;
	while($a > 1){
		$a = $a - 1;
		try {
			// Create Telegram API object
			$telegram = new Longman\TelegramBot\Telegram($API_KEY, $BOT_NAME);

			// Error, Debug and Raw Update logging
			//Longman\TelegramBot\TelegramLog::initialize($your_external_monolog_instance);
			Longman\TelegramBot\TelegramLog::initErrorLog($path .'_error.log');
			Longman\TelegramBot\TelegramLog::initDebugLog($path .'_debug.log');
			Longman\TelegramBot\TelegramLog::initUpdateLog($path .'_update.log');

			// Enable MySQL
			$telegram->enableMySql($mysql_credentials);

			// Enable MySQL with table prefix
			//$telegram->enableMySql($mysql_credentials, $BOT_NAME . '_');

			// Add an additional commands path
			$telegram->addCommandsPath($commands_path);

			// Enable admin user(s)
			//$telegram->enableAdmin(63200004);
			$telegram->enableAdmins([63200004, 62715990, 270783544]);

			// Add the channel you want to manage
			$telegram->setCommandConfig('sendtochannel', ['your_channel' => '@Shayenews']);

			// Here you can set some command specific parameters,
			// for example, google geocode/timezone api key for /date command:
			//$telegram->setCommandConfig('date', ['google_api_key' => 'your_google_api_key_here']);
			$telegram->setCommandConfig('sendtochannel', ['your_channel' => '@Shayenews']);

			// Set custom Upload and Download path
			$telegram->setDownloadPath('../Download-iziChat');
			$telegram->setUploadPath('../Upload-iziChat');

			// Botan.io integration
			// Second argument are options
			//$telegram->enableBotan('your_token');
			//$telegram->enableBotan('your_token', ['timeout' => 3]);

			// Requests Limiter (tries to prevent reaching Telegram API limits)
			$telegram->enableLimiter();

			// Handle telegram getUpdates request
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
			// Log telegram errors
			Longman\TelegramBot\TelegramLog::error($e);
		} catch (Longman\TelegramBot\Exception\TelegramLogException $e) {
			// Catch log initilization errors
			echo $e;
		}
		sleep($sleeptime);
	}
} else {
    sleep($real_execution_time_limit);
    posix_kill(posix_getppid(), SIGKILL); 
}

