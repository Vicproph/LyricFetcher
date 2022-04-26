<?php

namespace Classes;

use GuzzleHttp\Client;
use Symfony\Component\Serializer\Encoder\JsonDecode;

class Genius
{
    public $song;

    public static function scrapeSong($searchQuery, $index = null) // returns the song(s) returned from the search list from Genius
    {
        $url = "https://genius.p.rapidapi.com/search?q=" . urlencode($searchQuery);
        $client = new Client(Test::DEVELOPMENT_MODE ? Test::GUZZLEHTTP_CLIENT_SSL_VERIFY : []);
        $response = $client->get($url, [
            'proxy' => Bot::PROXY,
            'headers' => [
                'X-RapidAPI-Host' => 'genius.p.rapidapi.com',
                'X-RapidAPI-Key' => getenv('RAPID_API_KEY')
            ]
        ]);
        $result = $response->getBody()->getContents();
        $result = json_decode($result);
        var_dump($result);
        $songs = [];
        foreach ($result->response->hits as $i => $hit) {
            $songs[$i]['full_title'] = $hit->result->full_title;
            $songs[$i]['url'] = $hit->result->url;
        }
        if (empty($songs)) // could have no results
            return null;
        return $index === null ? $songs : $songs[$index];
    }
}
