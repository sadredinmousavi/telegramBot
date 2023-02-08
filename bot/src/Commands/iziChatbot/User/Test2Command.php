<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\ConversationDB;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Entities\PhotoSize;
use Longman\TelegramBot\Request;

/**
 * User "/survery" command
 */
class Test2Command extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'test2';

    /**
     * @var string
     */
    protected $description = 'تست MBTI';

    /**
     * @var string
     */
    protected $usage = '/test2';

    /**
     * @var string
     */
    protected $version = '0.3.0';

    /**
     * @var bool
     */
    protected $need_mysql = true;

    /**
     * Conversation Object
     *
     * @var \Longman\TelegramBot\Conversation
     */
    protected $conversation;

    /**
     * Command execute method
     *
     * @return mixed
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute()
    {
        $message = $this->getMessage();

        $chat = $message->getChat();
        $user = $message->getFrom();
        $text = trim($message->getText(true));
        $chat_id = $chat->getId();
        $user_id = $user->getId();

        //Preparing Response
        $data = [
            'chat_id' => $chat_id,
        ];

        if ($chat->isGroupChat() || $chat->isSuperGroup()) {
            //reply to message id is applied by default
            //Force reply is applied by default so it can work with privacy on
            $data['reply_markup'] = Keyboard::forceReply(['selective' => true]);
        }
		
		$can_continue = $this->getTelegram()->executeCommand("checker");
		if(!$can_continue){
			$d = ['chat_id' => $this->getMessage()->getChat()->getId(), 'text' => 'موقتا این دستور غیر فعال است.'];
			return Request::sendMessage($d);
		}	
			
		$conversations = ConversationDB::findStoppedConversation($user_id, $chat_id, 1, $this->getName());
		if ($conversations){
			$data['text'] = 'شما قبلا این تست را انجام داده اید!' .PHP_EOL .'برای مشاهده نتایج و تحلیل آن از دستور  /testresults  استفاده کنید.';
			$a = Request::sendMessage($data);
			return $this->getTelegram()->executeCommand("cancel");
		} else if ($text === 'شروع تست') {
			$data['text'] = 'در صورتی که در حین پاسخ به تست تصمیم به لغو آن گرقتید از دستور' 
							.PHP_EOL .'/cancel'
							.PHP_EOL .'استفاده کنید. اگر سوالات را تا انتها پاسخ دهید نتیجه تست شما ثبت می شود. به خاطر دقت نتیجه هر فرد تنها یک بار مجاز به انجام تست می باشد .'
							.PHP_EOL .'برای مشاهده نتایج و تحلیل آن از دستور  /testresults  استفاده کنید.';
			$result = Request::sendMessage($data);
		}
		
		
		

        //Conversation start
        $this->conversation = new Conversation($user_id, $chat_id, $this->getName());

        $notes = &$this->conversation->notes;
        !is_array($notes) && $notes = [];

        //cache data from the tracking session if any
        $state = 0;
        if (isset($notes['state'])) {
            $state = $notes['state'];
			$isfirsttime = false;
        } else {
			$isfirsttime = true;
		}

        $result = Request::emptyResponse();
		
		

        //State machine
        //Entrypoint of the machine state if given by the track
        //Every time a step is achieved the track is updated
		$Questions = [	[	"آیا شناختن شما"	,	"آسان است ؟"	,	"دشوار است ؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"آیا"	,	"با هر کسی تا حدی که لازم می دانید به راحتی صحبت می کنید؟"	,	"فقط با افراد خاصی آن هم در شرایط خاصی میتوانید زیاد صحبت کنید؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"شما معمولا"	,	"زود جوش هستید؟"	,	"آرام و درون گرا هستید؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"روابط دوستانه شما "	,	"با افرادی معدود ولی عمیق است؟"	,	"با تعداد بسیاری از افراد ولی سطحی است؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"می توانید به طور نامحدود"	,	"فقط با کسانی که علایق مشترکی با شما دارند صحبت کنید؟"	,	"می توانید تقریبا با هر کسی صحبت کنید؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"در صحبت کردن با دوستانتان"	,	"گاهی مسائل شخصی را به طور خصوصی به آنان می گویید؟"	,	"تقریبا هرگز چیزی را که نمی خواهید بگویید بیان نمی کنید؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"آیا معمولا"	,	"آزادانه احساسات خود را نشان می دهید؟"	,	"احساسات خود را نشان نمی دهید؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"وقتی غریبه ها به شما توجه می کنند"	,	"احساس ناراحتی می کنید؟"	,	"اصلا ناراحت نمی شوید؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"آیا عادت دارید"	,	"به هیچ کس اعتماد نکنید یا حداکثر به یک نفر اعتماد کنید؟"	,	"تعدادی دوست دارید که به آنها اعتماد می کنید؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"آیا"	,	"فکر می کنید همه ی کسانی را که دوست دارید می دانند که دوستشان دارید؟"	,	"به بعضی افراد علاقه مند هستید بی آنکه بگذارید آنها بدانند؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"وقتی با گروهی از افراد هستید معمولا ترجیح می دهید ؟"	,	"به صحبت گروهی بپردازید؟"	,	"هر بار فقط با یک فرد صحبت کنید؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"در بین دوستانتان آیا"	,	"یکی از آخرین کسانی هستید که خبرها را می شنوید؟"	,	"همه نوع خبری در مورد هر کسی دارید؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"در یک محفل اجتماعی"	,	"سعی می کنید کسی را که دوست دارید با او صحبت کنید به گوشه ای بکشید؟"	,	"با گروه می جوشید؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"در میهمانی ها"	,	"گاهی کسل می شوید؟"	,	"همیشه به شما خوش می گذرد؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"کدام لغت برای شما جالبتر است ؟"	,	"صحبت کردن"	,	"نوشتن"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"کدام لغت برای شما جالبتر است ؟"	,	"آرام"	,	"سرزنده"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"کدام لغت برای شما جالبتر است ؟"	,	"ساکت"	,	"پر حرف"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"در یک مهمانی دوست دارید:"	,	"کاری کنید که مراسم به خوبی برگزار شود؟"	,	"می گذارید هر کسی به میل خودش خوش بگذراند؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"هنگامی که با گروهی از دوستان نزدیک خودتان هستید:"	,	"بیشتر از بقیه صحبت می کنید؟"	,	"کمتر از بقیه؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"در یک گروه بزرگ اغلب:"	,	"دیگران را معرفی می کنید؟"	,	"معرفی می شوید؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"زمانی که در مورد یک پیشامد فکر می کنید ترجیح می دهید:"	,	"در این مورد با شخصی صحبت کنید؟"	,	"در مورد آن خوب فکر می کنید؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"آیا افرادی که تازه با شما آشنا می شوند می توانند بگویند به چه علاقه دارید؟"	,	"خیلی سریع"	,	"تنها پس از آنکه شما را خوب شناختند"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"آیا معمولا منظورتان"	,	"بیشتر از آنچه می گویید می باشد؟"	,	"کمتر از آنچه می گویید است؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"وقتی که غریبه ها را ملاقات می کنید"	,	"برایتان خوشایند یا دست کم راحت است؟"	,	"زحمت زیادی برایتان دارد؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"وقتی در جلسه ای راجع به چیزی نظری دارید که باید گفته شود"	,	"آن را مطرح می کنید؟"	,	"در مورد گفتن آن تردید دارید؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"ترجیح می دهید در نظر مردم چگونه باشید؟"	,	"فردی اهل عمل؟"	,	"فردی مبتکر و خلاق؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"هنگام مطالعه با هدف سرگرم شدن آیا"	,	"از شیوه ی بیان عجیب و ابتکاری مطالب لذت می برید؟"	,	"نویسندگانی را دوست دارید که به روشنی منظور خود را می رسانید؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"اگر معلم بودید ترجیح می دادید "	,	"واقعیت ها را تدریس کنید؟"	,	"نظریه ها را شرح دهید؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"معمولا با کدام شخص راحت تر ارتباط برقرار می کنید؟"	,	"با فردی تخیلی؟"	,	"با فردی واقع بین؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"در انجام کاری که بسیاری از مردم انجام می دهند ترجیح می دهید"	,	"آن را به شیوه ی پذیرفته شده انجام دهید؟"	,	"روش خاص خود را برای انجام آن ابداع کنید؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"درشیوه ی زندگی تان ترجیح می دهید"	,	"متفاوت باشید؟"	,	"متعارف عمل کنید؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"کدام لغت برای شما جالبتر است ؟"	,	"عبارت"	,	"مفهوم"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"کدام لغت برای شما جالبتر است ؟"	,	"ساختن"	,	"اختراع کردن"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"کدام لغت برای شما جالبتر است ؟"	,	"واقعی"	,	"انتزاعی"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"کدام لغت برای شما جالبتر است ؟"	,	"حروفی"	,	"ارقامی"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"کدام لغت برای شما جالبتر است ؟"	,	"تولید"	,	"طراحی"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"کدام لغت برای شما جالبتر است ؟"	,	"علامت"	,	"نماد"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"کدام لغت برای شما جالبتر است ؟"	,	"پذیرش"	,	"تغییر"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"کدام لغت برای شما جالبتر است ؟"	,	"شناخته شده"	,	"ناشناخته"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"کدام لغت برای شما جالبتر است ؟"	,	"واقعیت ها"	,	"ایده ها"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"کدام لغت برای شما جالبتر است ؟"	,	"زیربنا"	,	"روبنا"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"کدام لغت برای شما جالبتر است ؟"	,	"نظریه"	,	"تجربه"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"کدام لغت برای شما جالبتر است ؟"	,	"مایع"	,	"جامد"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"کدام یک تمجید بیشتری از یک فرد است؟"	,	"بصیرت دارد؟"	,	"عقل سلیم دارد؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"اگر بخواهید در همسایگی خود برای مسائلی مانند کمک به کمیته امداد به جمع آوری اعانه بپردازید"	,	"درخواست شما خلاصه و تجاری است؟"	,	"دوستانه و اجتماعی است؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"کدام گزاره تعریف و تمجید بیشتری از شما به شمار می آید؟"	,	"شخصی با احساسات واقعی ؟"	,	"شخصی همیشه منطقی؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"آیا در تصمیم گیری ‚ بیشتر                    "	,	"قلبتان بر عقلتان غلبه دارد؟"	,	"عقلتان بر قلبتان؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"هنگام گفتگو بیشتر تمایل دارید"	,	"تمجید کنید؟"	,	"سرزنش کنید؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"احساس می کنید که کدام یک عیب بدتری به شمار می آید؟"	,	"همدردی نکردن؟"	,	"استدلال پذیر نبودن؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"اگر بخواهید عمل خاصی را انجام دهید کدام یک از این دو گزاره برایتان جالب تر بنظر می آید؟"	,	"مردم خیلی دوست دارند که شما آن را انجام دهید؟"	,	"این منطقی ترین کاری است که انجام می دهید؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"فکر می کنید وجود کدام یک در فرد نقص بیشتری است؟"	,	"خیلی احساساتی بودن؟"	,	"به اندازه کافی احساساتی نبودن؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"احساس می کنید کدام یک عیب بدتری به شمار می آید؟"	,	"گرمی زیاد نشان دادن؟"	,	"به اندازه کافی گرم نبودن؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"آیا معمولا"	,	"برای احساس بیشتر از منطق ارزش قائل هستید؟"	,	"برای منطق بیشتر از احساس؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"کدام لغت برای شما جالبتر است ؟"	,	"متقاعد کردن"	,	"لمس کردن"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"کدام لغت برای شما جالبتر است ؟"	,	"موافقت کردن"	,	"پرسیدن"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"کدام لغت برای شما جالبتر است ؟"	,	"مزایا"	,	"برکت"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"کدام لغت برای شما جالبتر است ؟"	,	"تحلیل کردن"	,	"همدردی"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"کدام لغت برای شما جالبتر است ؟"	,	"نرم"	,	"سخت"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"کدام لغت برای شما جالبتر است ؟"	,	"پایبند به اندیشه خود"	,	"دلگرم"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"کدام لغت برای شما جالبتر است ؟"	,	"چه کسی؟"	,	"چه چیزی؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"کدام لغت برای شما جالبتر است ؟"	,	"محتاط"	,	"خوش خیال"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"کدام لغت برای شما جالبتر است ؟"	,	"ملایم"	,	"محکم"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"کدام لغت برای شما جالبتر است ؟"	,	"عدالت"	,	"بخشندگی"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"کدام لغت برای شما جالبتر است ؟"	,	"غیر انتقادی"	,	"انتقادی"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"کدام لغت برای شما جالبتر است ؟"	,	"متفکر"	,	"احساسی"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"کدام لغت برای شما جالبتر است ؟"	,	"دلسوزی"	,	"دوراندیشی"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"بیشتر مراقب کدام یک هستید؟"	,	"احساسات افراد؟"	,	"حقوق افراد؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"آیا به طور طبیعی:"	,	"به مردم بیشتر از اشیاء علاقه مندید؟"	,	"به اشیاء و نحوه کار آنها بیشتر از روابط انسانها علاقه مندید؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"برای انجام یک کار ‚ آیا"	,	"آن را زود شروع می کنید بطوریکه پس از پایان کار وقت اضافی داشته باشید؟"	,	"آن را به لحظه آخر واگذار کرده و سعی میکنید هر چه سریعتر انجام دهید؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"هنگامی که رخدادی پیش بینی نشده شما را مجبور به کنار گذاشتن برنامه روزانه تان می نماید"	,	"آیا بوجود آمدن وقفه در برنامه تان را مزاحمت تلقی می نمایید؟"	,	"با تغییر وضعیت بوجود آمده با خوشرویی برخورد می کنید؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"آیا مطابق برنامه عمل کردن"	,	"مورد رضایت شماست؟"	,	"شما را در تنگنا قرار می دهد؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"هنگام شروع یک پروژه ی بزرگ که تا یک هفته باید انجام شود"	,	"زمانی را برای تهیه ی فهرستی از کارهایی که می بایست انجام شوند بر اساس اولویت در نظر می گیرید؟"	,	"دل به دریا می زنید و شروع می کنید؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"اگر قرار باشد که کاری خاص را از پیش در زمانی خاص انجام دهید"	,	"طبق برنامه عمل کردن برای شما خوشایند است؟"	,	"از در چهار چوب قرار گرفتن احساس ناخوشایندی می کنید؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"آیا ترجیح می دهید "	,	"قرار ملاقات ها وعده ها و میهمانی ها را از پیش تعیین کنید؟"	,	"فردی باشید که در لحظه آخر بتوانید آزادانه آنجایی را که تمایل دارید انتخاب کنید؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"آیا"	,	"ترجیح می دهید کارها را در دقیقه آخر انجام دهید؟"	,	"انجام دادن کارها در دقیقه آخر شما را عصبی می کند؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"آیا فکر می کنید که داشتن یک برنامه روزانه"	,	"راحت ترین روش برای دادن کارهاست؟"	,	"حتی اگر ضروری باشد رنج آور است؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"وقتی که کار خاصی برای انجام دادن دارید آیا مایلید"	,	"پیش از آغاز کار ‚ با دقت آن را سازماندهی کنید؟"	,	"آنچه ضروری است را حین انجام کار متوجه شوید؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"آیا "	,	"روزمره بودن برایتان ساده تر است؟"	,	"متنوع بودن؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"در زندگی شخصی وقتی به پایان مسئولیتی می رسید "	,	"آیا می دانید کار بعدی چیست و آماده درگیر شدن با آن هستید؟"	,	"تا پیشامد بعدی از آرامش خود خوشنود هستید؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"کدام لغت برای شما جالبتر است ؟"	,	"وقت شناس"	,	"بی دغدغه"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"کدام لغت برای شما جالبتر است ؟"	,	"منضبط"	,	"آسان گیر"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"کدام لغت برای شما جالبتر است ؟"	,	"منظم"	,	"خودمانی"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"کدام لغت برای شما جالبتر است ؟"	,	"برنامه ریز"	,	"بی برنامه"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"آیا به طور کلی ترجیح می دهید:"	,	"برای دیدار کسی از قبل برنامه ریزی کنید؟"	,	"آزاد باشید و لحظه ای عمل کنید؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"وقتی برای یک روز جایی می روید . آیا ترجیح می دهید :"	,	"برای اینکه چه کاری و چه موقع انجام دهید برنامه ریزی کنید؟"	,	"فقط می روید؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"در مورد کارهای روز مره ترجیح می دهید "	,	"ابتدای صبح همه کارها را انجام دهید؟"	,	"در ضمن فرصت پیش آمده حین انجام کارهای جالب آنها را انجام می دهید؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
						[	"آیا"	,	"از اتمام کارها پیش از زمان موعود احساس رضایت می کنید؟"	,	"از سرعت و کارآمدی خود در لحظات آخر انجام کار لذت می برید؟"	,	"گزینه الف"	,	"گزینه ب"	]	,
					];

		
		$keyboard1 = ['خیلی کم', 'کم', 'متوسط', 'زیاد', 'خیلی زیاد'];
		$keyboard2 = ['خیلی بد', 'بد', 'متوسط', 'خوب', 'خیلی خوب'];
		
		
		
		
		if ($state < count($Questions)){
			
			$Question = $Questions[$state];
			$thisKeyboard = [$Question[3], $Question[4]];
		
            if ($text === '' || !in_array($text, $thisKeyboard, true)) {
                $notes['state'] = $state;
                $this->conversation->update();

                $data['reply_markup'] = (new Keyboard($thisKeyboard))
                    ->setResizeKeyboard(true)
                    ->setOneTimeKeyboard(true)
                    ->setSelective(true);

                $num = $notes['state'] +1;
				if ($isfirsttime) {
					$data['text'] = 'سوال ' .$num .' از ' .count($Questions) .':' .PHP_EOL .$Question[0] .PHP_EOL .'گزینه الف : ' .$Question[1] .PHP_EOL .'گزینه ب : ' .$Question[2];
                } elseif ($text !== '') {
                    $data['text'] = 'برای پاسخ به سوال لطفا از کیبورد زیر استفاده کنید.' .PHP_EOL .$question;
                }

                $result = Request::sendMessage($data);
            } elseif (in_array($text, $thisKeyboard, true)){
				$key = array_search($text, $thisKeyboard);
				$notes['q' .$notes['state']] = $key + 1;//$notes['gender'] = $text;
				$state = $state + 1;
				//
				if ($state < count($Questions)) {
					$notes['state'] = $state;
					$this->conversation->update();
					//
					$Question = $Questions[$state];
					$thisKeyboard = [$Question[3], $Question[4]];
					//
					$data['reply_markup'] = (new Keyboard($thisKeyboard))
						->setResizeKeyboard(true)
						->setOneTimeKeyboard(true)
						->setSelective(true);

					$num = $notes['state'] +1;
					$data['text'] = 'سوال ' .$num .' از ' .count($Questions) .':' .PHP_EOL .$Question[0] .PHP_EOL .'گزینه الف : ' .$Question[1] .PHP_EOL .'گزینه ب : ' .$Question[2];
					$result = Request::sendMessage($data);
				}
			}
		} else {
			$this->conversation->update();
			$out_text = 'تست با موفقیت خاتمه یافت و نتایج آن ثبت شد.' .json_decode('"\uD83C\uDF39"')
						.PHP_EOL .'برای مشاهده نتایج و تحلیل آن از دستور  /testresults  استفاده کنید.';
			unset($notes['state']);
			//foreach ($notes as $k => $v) {
			//    $out_text .= PHP_EOL . ucfirst($k) . ': ' . $v;
			//}

			//$data['photo']        = $notes['photo_id'];
			$data['reply_markup'] = Keyboard::remove(['selective' => true]);
			$data['text']         = $out_text;//$data['caption']      = $out_text;
					
			$this->conversation->stop();

			//$result = Request::sendPhoto($data);
			$result = Request::sendMessage($data);
		}
      

        return $result;
    }
}
