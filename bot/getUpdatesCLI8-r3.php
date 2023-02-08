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
$API_KEY = '453489315:AAGIPZTnKRljuAq7jdR6TPLiHgZlH5YejzM';
$BOT_NAME = 'instagfaBot';

// Define a path for your custom commands
$commands_path = __DIR__ . '/src/Commands/' .$BOT_NAME .'/';
$path = __DIR__ . '/src/Commands/' .$BOT_NAME .'/';

// Enter your MySQL database credentials
$mysql_credentials = [
   'host'     => 'localhost',
   'user'     => 'kafegame_PhpBot',
   'password' => 'qwertyuiop1234567890',
   'database' => 'kafegame_instagfa',
];

//$commands = ['/fetchrecentposts','/updatelatestposts'];
$commands = ['/fetchdailyposts'];

		try {
      foreach ($commands as $comm) {
          $command = [$comm];
          $telegram = new Longman\TelegramBot\Telegram($API_KEY, $BOT_NAME);
          //
          Longman\TelegramBot\TelegramLog::initErrorLog($path .'_error.log');
          //Longman\TelegramBot\TelegramLog::initDebugLog($path .'_debug.log');
          Longman\TelegramBot\TelegramLog::initUpdateLog($path .'_update.log');
          $telegram->enableMySql($mysql_credentials);
          $telegram->addCommandsPath($commands_path);
          $telegram->enableAdmins([63200004, 62715990, 270783544, 65332476]);
          $telegram->setCommandConfig('sendtochannel', ['your_channel' => '@instagfa']);
          $telegram->setDownloadPath('../Download-instagfa');
          $telegram->setUploadPath('../Upload-instagfa');
          $telegram->enableLimiter();
          //
          $telegram->runCommands($command);
      }

      //


		} catch (Longman\TelegramBot\Exception\TelegramException $e) {
			echo $e;
			// Log telegram errors
			Longman\TelegramBot\TelegramLog::error($e);
		} catch (Longman\TelegramBot\Exception\TelegramLogException $e) {
			// Catch log initilization errors
			echo $e;
		}
