<?php

namespace App\Http\Controllers\Telegram;

use App\LyricsFinder\MusicxmatchProvider;

class ResponseController extends AbstractTelegramController
{
    protected $musicxmatch;

    public function __construct()
    {
        parent::__construct();
        $this->musicxmatch = new MusicxmatchProvider();
    }

    public function create()
    {
        $update = $this->telegram->getWebhookUpdates();
        $message = $update->getMessage();

        if (is_null($message)) return;

        $chat = $message->getChat();
        $text = $message->getText();

        return $this->telegram
                    ->setAsyncRequest(true)
                    ->sendMessage($this->prepareMessage($chat, $text));
    }

    protected function prepareMessage($chat, $text)
    {
        if (strpos($text, 'id:') === 0) {
            $track_id = substr($text, 3, strpos($text, ' ') -3);
            $message = [
                'chat_id' => $chat->getId(),
                'text' => $this->musicxmatch->getLyric($track_id),
            ];
        } else {
            $tracks = $this->musicxmatch->find($text);
            $message = [
                'chat_id' => $chat->getId(),
                'text' => "There are {$tracks->count()} results.",
                'reply_markup' => $this->prepareReplyMarkup($tracks),
            ];
        }

        return $message;
    }

    protected function prepareReplyMarkup($tracks)
    {
        if ($tracks->isEmpty()) return $this->telegram->replyKeyboardHide();

        $keyboard = $tracks->map(function ($track) {
            return ['id:'.$track['track_id'].' - '.$track['track_name'].' - '.$track['artist_name']];
        });

        return $this->telegram->replyKeyboardMarkup([
            'keyboard' => $keyboard,
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]);
    }
}