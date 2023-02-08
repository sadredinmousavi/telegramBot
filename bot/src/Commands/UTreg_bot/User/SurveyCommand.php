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
class SurveyCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'survey';

    /**
     * @var string
     */
    protected $description = 'سوالات پرسش نامه';

    /**
     * @var string
     */
    protected $usage = '/survey';

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

        //Conversation start
        $this->conversation = new Conversation($user_id, $chat_id, $this->getName());

        $notes = &$this->conversation->notes;
        !is_array($notes) && $notes = [];

        //cache data from the tracking session if any
        $state = 0;
        if (isset($notes['state'])) {
            $state = $notes['state'];
        }

        $result = Request::emptyResponse();
		
		$conversations = ConversationDB::findStoppedConversation($user_id, $chat_id, 1);
		if ($conversations){
			$data['text'] = 'شما قبلا  در ارزیابی شرکت کردید!';
			$a = Request::sendMessage($data);
			return $this->getTelegram()->executeCommand("cancel");
		} else if ($state == 0 && $text === '') {
			$data['text'] = 'در صورتی که در حین پاسخ به پرسش نامه تصمیم به لغو پرسش نامه گرقتید از دستور' 
							.PHP_EOL .'/cancel'
							.PHP_EOL .'استفاده کنید. اگر سوالات را تا انتها پاسخ دهید نظر شما ثبت می شود. هر فرد تنها یک بار مجاز به ثبت نظر در پرسش نامه است.';
			$result = Request::sendMessage($data);
		}

        //State machine
        //Entrypoint of the machine state if given by the track
        //Every time a step is achieved the track is updated
		$Questions['q0'] = 'به‌نظر شما راهنماهای موجود در سایت پذیرش دانشجویان ورودی جدید (reg.ut.ac.ir) تا چه اندازه کامل و جامع بودند؟';
		$Questions['q1'] = 'میزان رضایت شما از دقت و شفافیت راهنماهای موجود در سایت پذیرش دانشجویان ورودی جدید چه مقدار است؟';
		$Questions['q2'] = 'میزان رضایت شما از نحوه اطلاع‌رسانی در مورد زمان و نحوه ثبت‌نام چه میزان است؟';
		$Questions['q3'] = 'میزان رضایت شما از اطلاع رسانی در خصوص مشکلات احتمالی سامانه در فرایند ثبت نام چه مقدار است؟';
		$Questions['q4'] = 'میزان رضایت شما از سهولت و سادگی فرایند ثبت‌نام چه اندازه است؟';
		$Questions['q5'] = 'میزان رضایت شما از سهولت و سادگی فرایند دریافت شناسه یکتای مربوط به ثبت¬نام چه اندازه است؟';
		$Questions['q6'] = 'سرعت و سهولت فرایند بارگذاری مدارک در سامانه جامع آموزش را چگونه ارزیابی می¬کنید؟';
		$Questions['q7'] = 'در صورتی که از درگاه پرداخت تعبیه شده در سامانه جامع آموزش استفاده کرده‌اید، سرعت و سهولت فرایند پرداخت را چگونه ارزیابی می‌کنید؟';
		$Questions['q8'] = 'زیبایی ظاهری و رابط کاربری سایت پذیرش دانشجویان ورودی جدید (دریافت شناسه یکتا) را چگونه ارزیابی می‌کنید؟';
		$Questions['q9'] = 'زیبایی ظاهری و رابط کاربری سامانه جامع آموزش (فرایند ارسال مدارک، پرداخت شهریه و غیره) را چگونه ارزیابی می‌کنید؟';
		$Questions['q10'] = 'میزان رضایت شما از فرایند ثبت نام حضوری، اخذ مدارک تحصیلی، تشخیص هویت و غیره چه مقدار است؟';
		$Questions['q11'] = 'میزان رضایت شما از شفافیت و جامعیت آیین نامه‌های آموزشی ارائه شده در دوره ثبت نام چه مقدار است؟';
		$Questions['q12'] = 'رفتار کارشناسان مستقر در مکان‌های تعیین شده برای انجام فرایند ثبت نام حضوری را چگونه ارزیابی می‌کنید؟';
		$Questions['q13'] = 'چنانچه پیشنهادی بمنظور بهبود و ارتقای فرایند ثبت نام حضوری و غیرحضوری دارید ارائه بفرمایید.';
		$keyboard1 = ['خیلی کم', 'کم', 'متوسط', 'زیاد', 'خیلی زیاد'];
		$keyboard2 = ['خیلی بد', 'بد', 'متوسط', 'خوب', 'خیلی خوب'];
        switch ($state) {
            case 0:
				$thisKeyboard = $keyboard1;
                if ($text === '' || !in_array($text, $thisKeyboard, true)) {
                    $notes['state'] = 0;
                    $this->conversation->update();

                    $data['reply_markup'] = (new Keyboard($thisKeyboard))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);

                    $num = $notes['state'] +1;
                    $data['text'] = 'سوال ' .$num .' از ' .sizeof($Questions, 0) .':' .PHP_EOL .$Questions['q' .$notes['state']];
                    if ($text !== '') {
                        $data['text'] = 'برای پاسخ به سوال لطفا از کیبورد زیر استفاده کنید.' .PHP_EOL .$question;
                    }

                    $result = Request::sendMessage($data);
                    break;
                }
				$key = array_search($text, $thisKeyboard);
                $notes['q' .$notes['state']] = $key + 1;//$notes['gender'] = $text;
                $text          = '';

            // no break
            case 1:
				$thisKeyboard = $keyboard1;
                if ($text === '' || !in_array($text, $thisKeyboard, true)) {
                    $notes['state'] = 1;
                    $this->conversation->update();

                    $data['reply_markup'] = (new Keyboard($thisKeyboard))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);

                    $num = $notes['state'] +1;
                    $data['text'] = 'سوال ' .$num .' از ' .sizeof($Questions, 0) .':' .PHP_EOL .$Questions['q' .$notes['state']];
                    if ($text !== '') {
                        $data['text'] = 'برای پاسخ به سوال لطفا از کیبورد زیر استفاده کنید.' .PHP_EOL .$question;
                    }

                    $result = Request::sendMessage($data);
                    break;
                }
				$key = array_search($text, $thisKeyboard);
                $notes['q' .$notes['state']] = $key + 1;//$notes['gender'] = $text;
                $text          = '';

            // no break
			case 2:
				$thisKeyboard = $keyboard1;
                if ($text === '' || !in_array($text, $thisKeyboard, true)) {
                    $notes['state'] = 2;
                    $this->conversation->update();

                    $data['reply_markup'] = (new Keyboard($thisKeyboard))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);

                    $num = $notes['state'] +1;
                    $data['text'] = 'سوال ' .$num .' از ' .sizeof($Questions, 0) .':' .PHP_EOL .$Questions['q' .$notes['state']];
                    if ($text !== '') {
                        $data['text'] = 'برای پاسخ به سوال لطفا از کیبورد زیر استفاده کنید.' .PHP_EOL .$question;
                    }

                    $result = Request::sendMessage($data);
                    break;
                }
				$key = array_search($text, $thisKeyboard);
                $notes['q' .$notes['state']] = $key + 1;//$notes['gender'] = $text;
                $text          = '';

            // no break
			case 3:
				$thisKeyboard = $keyboard1;
                if ($text === '' || !in_array($text, $thisKeyboard, true)) {
                    $notes['state'] = 3;
                    $this->conversation->update();

                    $data['reply_markup'] = (new Keyboard($thisKeyboard))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);

                    $num = $notes['state'] +1;
                    $data['text'] = 'سوال ' .$num .' از ' .sizeof($Questions, 0) .':' .PHP_EOL .$Questions['q' .$notes['state']];
                    if ($text !== '') {
                        $data['text'] = 'برای پاسخ به سوال لطفا از کیبورد زیر استفاده کنید.' .PHP_EOL .$question;
                    }

                    $result = Request::sendMessage($data);
                    break;
                }
				$key = array_search($text, $thisKeyboard);
                $notes['q' .$notes['state']] = $key + 1;//$notes['gender'] = $text;
                $text          = '';

            // no break
			case 4:
				$thisKeyboard = $keyboard1;
                if ($text === '' || !in_array($text, $thisKeyboard, true)) {
                    $notes['state'] = 4;
                    $this->conversation->update();

                    $data['reply_markup'] = (new Keyboard($thisKeyboard))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);

                    $num = $notes['state'] +1;
                    $data['text'] = 'سوال ' .$num .' از ' .sizeof($Questions, 0) .':' .PHP_EOL .$Questions['q' .$notes['state']];
                    if ($text !== '') {
                        $data['text'] = 'برای پاسخ به سوال لطفا از کیبورد زیر استفاده کنید.' .PHP_EOL .$question;
                    }

                    $result = Request::sendMessage($data);
                    break;
                }
				$key = array_search($text, $thisKeyboard);
                $notes['q' .$notes['state']] = $key + 1;//$notes['gender'] = $text;
                $text          = '';

            // no break
			case 5:
				$thisKeyboard = $keyboard1;
                if ($text === '' || !in_array($text, $thisKeyboard, true)) {
                    $notes['state'] = 5;
                    $this->conversation->update();

                    $data['reply_markup'] = (new Keyboard($thisKeyboard))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);

                    $num = $notes['state'] +1;
                    $data['text'] = 'سوال ' .$num .' از ' .sizeof($Questions, 0) .':' .PHP_EOL .$Questions['q' .$notes['state']];
                    if ($text !== '') {
                        $data['text'] = 'برای پاسخ به سوال لطفا از کیبورد زیر استفاده کنید.' .PHP_EOL .$question;
                    }

                    $result = Request::sendMessage($data);
                    break;
                }
				$key = array_search($text, $thisKeyboard);
                $notes['q' .$notes['state']] = $key + 1;//$notes['gender'] = $text;
                $text          = '';

            // no break
			case 6:
				$thisKeyboard = $keyboard2;
                if ($text === '' || !in_array($text, $thisKeyboard, true)) {
                    $notes['state'] = 6;
                    $this->conversation->update();

                    $data['reply_markup'] = (new Keyboard($thisKeyboard))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);

                    $num = $notes['state'] +1;
                    $data['text'] = 'سوال ' .$num .' از ' .sizeof($Questions, 0) .':' .PHP_EOL .$Questions['q' .$notes['state']];
                    if ($text !== '') {
                        $data['text'] = 'برای پاسخ به سوال لطفا از کیبورد زیر استفاده کنید.' .PHP_EOL .$question;
                    }

                    $result = Request::sendMessage($data);
                    break;
                }
				$key = array_search($text, $thisKeyboard);
                $notes['q' .$notes['state']] = $key + 1;//$notes['gender'] = $text;
                $text          = '';

            // no break
			case 7:
				$thisKeyboard = $keyboard2;
                if ($text === '' || !in_array($text, $thisKeyboard, true)) {
                    $notes['state'] = 7;
                    $this->conversation->update();

                    $data['reply_markup'] = (new Keyboard($thisKeyboard))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);

                    $num = $notes['state'] +1;
                    $data['text'] = 'سوال ' .$num .' از ' .sizeof($Questions, 0) .':' .PHP_EOL .$Questions['q' .$notes['state']];
                    if ($text !== '') {
                        $data['text'] = 'برای پاسخ به سوال لطفا از کیبورد زیر استفاده کنید.' .PHP_EOL .$question;
                    }

                    $result = Request::sendMessage($data);
                    break;
                }
				$key = array_search($text, $thisKeyboard);
                $notes['q' .$notes['state']] = $key + 1;//$notes['gender'] = $text;
                $text          = '';

            // no break
			case 8:
				$thisKeyboard = $keyboard2;
                if ($text === '' || !in_array($text, $thisKeyboard, true)) {
                    $notes['state'] = 8;
                    $this->conversation->update();

                    $data['reply_markup'] = (new Keyboard($thisKeyboard))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);

                    $num = $notes['state'] +1;
                    $data['text'] = 'سوال ' .$num .' از ' .sizeof($Questions, 0) .':' .PHP_EOL .$Questions['q' .$notes['state']];
                    if ($text !== '') {
                        $data['text'] = 'برای پاسخ به سوال لطفا از کیبورد زیر استفاده کنید.' .PHP_EOL .$question;
                    }

                    $result = Request::sendMessage($data);
                    break;
                }
				$key = array_search($text, $thisKeyboard);
                $notes['q' .$notes['state']] = $key + 1;//$notes['gender'] = $text;
                $text          = '';

            // no break
			case 9:
				$thisKeyboard = $keyboard2;
                if ($text === '' || !in_array($text, $thisKeyboard, true)) {
                    $notes['state'] = 9;
                    $this->conversation->update();

                    $data['reply_markup'] = (new Keyboard($thisKeyboard))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);

                    $num = $notes['state'] +1;
                    $data['text'] = 'سوال ' .$num .' از ' .sizeof($Questions, 0) .':' .PHP_EOL .$Questions['q' .$notes['state']];
                    if ($text !== '') {
                        $data['text'] = 'برای پاسخ به سوال لطفا از کیبورد زیر استفاده کنید.' .PHP_EOL .$question;
                    }

                    $result = Request::sendMessage($data);
                    break;
                }
				$key = array_search($text, $thisKeyboard);
                $notes['q' .$notes['state']] = $key + 1;//$notes['gender'] = $text;
                $text          = '';

            // no break
			case 10:
				$thisKeyboard = $keyboard1;
                if ($text === '' || !in_array($text, $thisKeyboard, true)) {
                    $notes['state'] = 10;
                    $this->conversation->update();

                    $data['reply_markup'] = (new Keyboard($thisKeyboard))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);

                    $num = $notes['state'] +1;
                    $data['text'] = 'سوال ' .$num .' از ' .sizeof($Questions, 0) .':' .PHP_EOL .$Questions['q' .$notes['state']];
                    if ($text !== '') {
                        $data['text'] = 'برای پاسخ به سوال لطفا از کیبورد زیر استفاده کنید.' .PHP_EOL .$question;
                    }

                    $result = Request::sendMessage($data);
                    break;
                }
				$key = array_search($text, $thisKeyboard);
                $notes['q' .$notes['state']] = $key + 1;//$notes['gender'] = $text;
                $text          = '';

            // no break
			case 11:
				$thisKeyboard = $keyboard1;
                if ($text === '' || !in_array($text, $thisKeyboard, true)) {
                    $notes['state'] = 11;
                    $this->conversation->update();

                    $data['reply_markup'] = (new Keyboard($thisKeyboard))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);

                    $num = $notes['state'] +1;
                    $data['text'] = 'سوال ' .$num .' از ' .sizeof($Questions, 0) .':' .PHP_EOL .$Questions['q' .$notes['state']];
                    if ($text !== '') {
                        $data['text'] = 'برای پاسخ به سوال لطفا از کیبورد زیر استفاده کنید.' .PHP_EOL .$question;
                    }

                    $result = Request::sendMessage($data);
                    break;
                }
				$key = array_search($text, $thisKeyboard);
                $notes['q' .$notes['state']] = $key + 1;//$notes['gender'] = $text;
                $text          = '';

            // no break
			case 12:
				$thisKeyboard = $keyboard2;
                if ($text === '' || !in_array($text, $thisKeyboard, true)) {
                    $notes['state'] = 12;
                    $this->conversation->update();

                    $data['reply_markup'] = (new Keyboard($thisKeyboard))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);
						
					$num = $notes['state'] +1;
                    $data['text'] = 'سوال ' .$num .' از ' .sizeof($Questions, 0) .':' .PHP_EOL .$Questions['q' .$notes['state']];
                    if ($text !== '') {
                        $data['text'] = 'برای پاسخ به سوال لطفا از کیبورد زیر استفاده کنید.' .PHP_EOL .$question;
                    }

                    $result = Request::sendMessage($data);
                    break;
                }
				$key = array_search($text, $thisKeyboard);
                $notes['q' .$notes['state']] = $key + 1;//$notes['gender'] = $text;
                $text          = '';

            // no break
			case 13:
				$thisKeyboard = $keyboard2;
                if ($text === '') {
                    $notes['state'] = 13;
                    $this->conversation->update();

                    //$data['reply_markup'] = (new Keyboard($thisKeyboard))
                    //    ->setResizeKeyboard(true)
                    //    ->setOneTimeKeyboard(true)
                    //    ->setSelective(true);
						
					$num = $notes['state'] +1;
                    $data['text'] = 'سوال ' .$num .' از ' .sizeof($Questions, 0) .':' .PHP_EOL .$Questions['q' .$notes['state']] .PHP_EOL .PHP_EOL .'لطفا پاسخ خود را در قالب یک پیام ارسال فرمایید.';
                    if ($text !== '') {
                        $data['text'] = 'برای پاسخ به سوال لطفا از کیبورد زیر استفاده کنید.' .PHP_EOL .$question;
                    }

                    $result = Request::sendMessage($data);
                    break;
                }
				//$key = array_search($text, $thisKeyboard);
                $notes['q' .$notes['state']] = $text;//$notes['gender'] = $text;
                $text          = '';

            // no break
            case 14:
                $this->conversation->update();
                $out_text = 'نظرات شما ثبت شد.' . PHP_EOL .'با تشکر از شرکت شما در این نظر سنجی.' .json_decode('"\uD83C\uDF39"');
                //unset($notes['state']);
                //foreach ($notes as $k => $v) {
                //    $out_text .= PHP_EOL . ucfirst($k) . ': ' . $v;
                //}

                //$data['photo']        = $notes['photo_id'];
                $data['reply_markup'] = Keyboard::remove(['selective' => true]);
                $data['text']         = $out_text;//$data['caption']      = $out_text;
				
                $this->conversation->stop();

                //$result = Request::sendPhoto($data);
				$result = Request::sendMessage($data);
                break;
        }

		//$data['text'] = 'متن اول قبل از اجرای پرسش نامه';
		//$result = Request::sendMessage($data);
		
        return $result;
    }
}
