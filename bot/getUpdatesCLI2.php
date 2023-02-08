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
$API_KEY = '316394761:AAHQvG-urknTvuCahAZJP068IBBdPiMuBJY';
$BOT_NAME = 'ShayenewsBot';

// Define a path for your custom commands
$commands_path = __DIR__ . '/commands/' .$BOT_NAME;

// Enter your MySQL database credentials
$mysql_credentials = [
   'host'     => 'localhost',
   'user'     => 'kafegame_PhpBot',
   'password' => 'qwertyuiop1234567890',
   'database' => 'kafegame_shayenews',
];

try {
    // Create Telegram API object
    $telegram = new Longman\TelegramBot\Telegram($API_KEY, $BOT_NAME);

    // Error, Debug and Raw Update logging
    //Longman\TelegramBot\TelegramLog::initialize($your_external_monolog_instance);
    //Longman\TelegramBot\TelegramLog::initErrorLog($path . '/' . $BOT_NAME . '_error.log');
    //Longman\TelegramBot\TelegramLog::initDebugLog($path . '/' . $BOT_NAME . '_debug.log');
    //Longman\TelegramBot\TelegramLog::initUpdateLog($path . '/' . $BOT_NAME . '_update.log');

    // Enable MySQL
    $telegram->enableMySql($mysql_credentials);

    // Enable MySQL with table prefix
    //$telegram->enableMySql($mysql_credentials, $BOT_NAME . '_');

    // Add an additional commands path
    $telegram->addCommandsPath($commands_path);

    // Enable admin user(s)
    //$telegram->enableAdmin(63200004);
    $telegram->enableAdmins([63200004, 62715990]);

    // Add the channel you want to manage
    $telegram->setCommandConfig('sendtochannel', ['your_channel' => '@Shayenews']);

    // Here you can set some command specific parameters,
    // for example, google geocode/timezone api key for /date command:
    //$telegram->setCommandConfig('date', ['google_api_key' => 'your_google_api_key_here']);

    // Set custom Upload and Download path
    //$telegram->setDownloadPath('../Download');
    //$telegram->setUploadPath('../Upload');

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