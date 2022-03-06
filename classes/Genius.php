<?php

namespace Classes;

class Genius
{
    public $song;

    public static function scrapeSong($searchQuery) // returns the song(s) returned from the search list from Genius
    {

        $url = "https://genius.p.rapidapi.com/search?q=" . urldecode($searchQuery);
        $curlHandle = curl_init($url);
        curl_setopt_array($curlHandle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "x-rapidapi-host: genius.p.rapidapi.com",
                "x-rapidapi-key: " .
                    getenv('RAPID_API_KEY')
            ],
        ]);
        $jsonResult = curl_exec($curlHandle);
        curl_close($curlHandle);
        $result = json_decode($jsonResult);
        $song = (!empty($result->response->hits) ? $result->response->hits[0]->result : null);
        return $song;
    }
}
