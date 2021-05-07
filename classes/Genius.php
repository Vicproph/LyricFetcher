<?php

namespace Classes;

class Genius
{

    public $song;

    public static function scrapeSong($searchQuery) // returns the song(s) returned from the search list from Genius
    {
        $url = "https://genius.p.rapidapi.com/search?q=" . rawurlencode($searchQuery);
        $curlHandle = curl_init($url);
        curl_setopt_array($curlHandle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "x-rapidapi-host: genius.p.rapidapi.com",
                "x-rapidapi-key: 3e94abbe94msh1952d5aafbd7ac7p117353jsnf10be779ce25"
            ],
        ]);
        $jsonResult = curl_exec($curlHandle);
        curl_close($curlHandle);
        $result = json_decode($jsonResult);
        $song = (!empty($result->response->hits) ? $result->response->hits[0]->result : null);
        return $song;
    }
}
