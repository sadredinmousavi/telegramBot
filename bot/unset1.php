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

$API_KEY = '387465466:AAEusLOli1_Rj_u5UmqD26adjJ2dctOEB-E';
$BOT_NAME = 'Viewpvpage_bot';
$hook_url = 'https://kafegame.com/bot/hook-1.php';


//if (isset($_post['username']) && isset($_post['password'])){
//    $valid_user = "ali125";
//    $valid_pass = "ali125";
//    if ($valid_pass == $_post['password'] && $valid_user == $_post['username']){
        //$API_KEY = '341235390:AAG4BBbarqUzEXozzQgZ0-Ws7YQwiEmGSgc';
        //$BOT_NAME = 'Kafegame_bot';
        try {
            // Create Telegram API object
            $telegram = new Longman\TelegramBot\Telegram($API_KEY, $BOT_NAME);

            // Delete webhook
            $result = $telegram->deleteWebHook();

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