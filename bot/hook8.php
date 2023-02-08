<?php
/**
 * README
 * This configuration file is intended to run the bot with the webhook method.
 * Uncommented parameters must be filled
 *
 * Please note that if you open this file with your browser you'll get the "Input is empty!" Exception.
 * This is a normal behaviour because this address has to be reached only by Telegram server.
 */

// Load composer
require __DIR__ . '/vendor/autoload.php';

// Add you bot's API key and name
$API_KEY = '449186002:AAF8zrpNgCO--m_MAT0Cfc7dtxTUGqGATPQ';
$BOT_NAME = 'RouhYab_bot';

// Define a path for your custom commands
$commands_path = __DIR__ . '/src/Commands/' .$BOT_NAME .'/';
$path = __DIR__ . '/src/Commands';

// Enter your MySQL database credentials
$mysql_credentials = [
   'host'     => 'localhost',
   'user'     => 'kafegame_PhpBot',
   'password' => 'qwertyuiop1234567890',
   'database' => 'kafegame_rouhyab',
];

try {
    // Create Telegram API object
    $telegram = new Longman\TelegramBot\Telegram($API_KEY, $BOT_NAME);

    // Error, Debug and Raw Update logging
    //Longman\TelegramBot\TelegramLog::initialize($your_external_monolog_instance);
    Longman\TelegramBot\TelegramLog::initErrorLog($path . '/' . $BOT_NAME . '_error.log');
    Longman\TelegramBot\TelegramLog::initDebugLog($path . '/' . $BOT_NAME . '_debug.log');
    Longman\TelegramBot\TelegramLog::initUpdateLog($path . '/' . $BOT_NAME . '_update.log');

    // Enable MySQL
    $telegram->enableMySql($mysql_credentials);

    // Enable MySQL with table prefix
    //$telegram->enableMySql($mysql_credentials, $BOT_NAME . '_');

    // Add an additional commands path
    $telegram->addCommandsPath($commands_path);

    // Enable admin user(s)
    $telegram->enableAdmin(63200004);
    //$telegram->enableAdmins([63200004, 62715990]);

    // Add the channel you want to manage
    $telegram->setCommandConfig('sendtochannel', ['your_channel' => '@Shayenews']);

    // Here you can set some command specific parameters,
    // for example, google geocode/timezone api key for /date command:
    //$telegram->setCommandConfig('date', ['google_api_key' => 'your_google_api_key_here']);

    // Set custom Upload and Download path
    $telegram->setDownloadPath('../Download-rouhyab-dummy');
    $telegram->setUploadPath('../Upload-rouhyab-dummy');

    // Botan.io integration
    // Second argument are options
    //$telegram->enableBotan('your_token');
    //$telegram->enableBotan('your_token', ['timeout' => 3]);

    // Requests Limiter (tries to prevent reaching Telegram API limits)
    $telegram->enableLimiter();

    // Handle telegram webhook request
    $telegram->handle();
} catch (Longman\TelegramBot\Exception\TelegramException $e) {
    // Silence is golden!
    //echo $e;
    // Log telegram errors
    Longman\TelegramBot\TelegramLog::error($e);
} catch (Longman\TelegramBot\Exception\TelegramLogException $e) {
    // Silence is golden!
    // Uncomment this to catch log initilization errors
    //echo $e;
}
