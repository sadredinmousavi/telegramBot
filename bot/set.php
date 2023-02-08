<?php
// Load composer
require __DIR__ . '/vendor/autoload.php';

//$API_KEY = '341235390:AAG4BBbarqUzEXozzQgZ0-Ws7YQwiEmGSgc';
//$BOT_NAME = 'Kafegame_bot';
//$hook_url = 'https://kafegame.com/bot/hook1.php';

//$API_KEY = '307103114:AAFldu4e3YAqY0_x74svxr6RHz6PnGe60Uo';
//$BOT_NAME = 'Kafebazi_bot';
//$hook_url = 'https://kafegame.com/bot/hook2.php';

//$API_KEY = '360341323:AAGE3BLs32C7uhFiJL0TQvm-qGToFAhQ5Xw';
//$BOT_NAME = 'Kafegamebot';
//$hook_url = 'https://kafegame.com/bot/hook3.php';

//$API_KEY = '316394761:AAHQvG-urknTvuCahAZJP068IBBdPiMuBJY';
//$BOT_NAME = 'ShayenewsBot';
//$hook_url = 'https://kafegame.com/bot/hook4.php';

//$API_KEY = '308183956:AAE6UJkI0yE3UywZitXJHVzZpnqsZa3fdsA';
//$BOT_NAME = 'iziChargeBot';
//$hook_url = 'https://kafegame.com/bot/hook5.php';

//$API_KEY = '342277485:AAFmL7edR_RarJ0stLwA3NCkijjjVaf5KOE';
//$BOT_NAME = 'FaceappIRBot';
//$hook_url = 'https://kafegame.com/bot/hook6.php';

$API_KEY = '385935789:AAF5DNAt0K0peJIWPM27s7bv4yNhJ4eODT0';
$BOT_NAME = 'iziChatbot';
$hook_url = 'https://kafegame.com/bot/hook7.php';

$API_KEY = '449186002:AAF8zrpNgCO--m_MAT0Cfc7dtxTUGqGATPQ';
$BOT_NAME = 'RouhYab_bot';
$hook_url = 'https://kafegame.com/bot/hook8.php';

$API_KEY = '426224293:AAHIGRPqNfeodebvXOY9nVwY_mS5VUYoGDQ';
$BOT_NAME = 'UTreg_bot';
$hook_url = 'https://kafegame.com/bot/hook9.php';

//if (isset($_post['username']) && isset($_post['password'])){
//    $valid_user = "ali125";
//    $valid_pass = "ali125";
//    if ($valid_pass == $_post['password'] && $valid_user == isset($_post['username']){
		$Credentials = [[
			'api_key'  => '341235390:AAG4BBbarqUzEXozzQgZ0-Ws7YQwiEmGSgc',
			'bot_name' => 'Kafegame_bot',
			'hook_url' => 'https://kafegame.com/bot/hook12.php',
			],[
			'api_key'  => '307103114:AAFldu4e3YAqY0_x74svxr6RHz6PnGe60Uo',
			'bot_name' => 'Kafebazi_bot',
			'hook_url' => 'https://kafegame.com/bot/hook2.php',
			],[
			'api_key'  => '360341323:AAGE3BLs32C7uhFiJL0TQvm-qGToFAhQ5Xw',
			'bot_name' => 'Kafegamebot',
			'hook_url' => 'https://kafegame.com/bot/hook3.php',
		]];
		$Cred = $Credentials[$_get['botnum']];
		//$API_KEY  = $Cred['api_key'];
		//$BOT_NAME = $Cred['bot_name'];
		//$hook_url = $Cred['bot_name'];
        
        try {
            // Create Telegram API object
            $telegram = new Longman\TelegramBot\Telegram($API_KEY, $BOT_NAME);

            // Set webhook
            $result = $telegram->setWebhook($hook_url);

            // Uncomment to use certificate
            //$result = $telegram->setWebhook($hook_url, ['certificate' => $path_certificate]);

            if ($result->isOk()) {
                echo $result->getDescription();
            }
        } catch (Longman\TelegramBot\Exception\TelegramException $e) {
            echo $e;
        }
//    } else {
//        echo "Wrong credentials.";
//    }
//} else {
//    echo "Please enter your credentials.";
//}

?>