<?php

namespace Classes;

use GuzzleHttp\Client;

class Test
{
    const DEVELOPMENT_MODE = false;
    const GUZZLEHTTP_CLIENT_SSL_VERIFY = ['verify' => false];

    static public function getUpdates()
    {
        $client = new Client(self::DEVELOPMENT_MODE ? self::GUZZLEHTTP_CLIENT_SSL_VERIFY : null);
        $response = $client->get("https://api.telegram.org/bot" . getenv('BOT_KEY') . "/getUpdates", [
            'proxy' => Bot::PROXY,
            'json' => [
                'offset' => -1
            ]
        ]);
        $jsonResult = json_decode($response->getBody()->getContents());
        return array_map(function ($item) {
            return [
                'text' =>  $item->message->text,
                'chat_id' => $item->message->chat->id
            ];
        }, $jsonResult->result);
    }
}
