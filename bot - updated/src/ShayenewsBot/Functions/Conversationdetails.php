<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Longman\TelegramBot;


use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\MyDB;


class Conversationdetails extends Conversation
{
    public function __construct($user_id, $chat_id, $command = null) {
        switch ($command) {
            case 'load_by_id':
                return $this->loadWithID($user_id);
                break;

            default:
                parent::__construct($user_id, $chat_id, $command);
                break;
        }
    }

    public function getID() {
        return ( $this->conversation['id']);
    }

    protected function loadWithID($id) {
        //Select an active conversation
        //$conversation = ConversationDB::selectConversation($this->user_id, $this->chat_id, 1);
        $conversation = MyDB::selectConversationN(['conversation_id' => $id, 'status' => 'stopped']);
        if (isset($conversation[0])) {
            //Pick only the first element
            $this->conversation = $conversation[0];

            //Load the command from the conversation if it hasn't been passed
            $this->command = $this->command ?: $this->conversation['command'];

            if ($this->command !== $this->conversation['command']) {
                $this->cancel();
                return false;
            }

            //Load the conversation notes
            $this->protected_notes = json_decode($this->conversation['notes'], true);
            $this->notes           = $this->protected_notes;
        }

        return $this->exists();
    }



}
