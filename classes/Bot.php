<?php

namespace Classes;

use Goutte\Client;
use GuzzleHttp\Client as GuzzleHttpClient;
use Http\Adapter\Guzzle6\Client as Guzzle6Client;
use Http\Factory\Guzzle\RequestFactory;
use InvalidArgumentException;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\HttpClient\HttpClient;

class Bot
{
    const BOT_KEY = '1706812403:AAFrmuFtocFvf05EbF04-EByQOOtMjRdGOo';
    const WEBAPP_URL = 'https://script.google.com/macros/s/AKfycbyRzxCGqcf2bgmEqndrCXigHCYtIsv2UghZDoS-N9H_djLxAcO3EGz4Ov7TktjGa_Pb/exec';
    const PROXY = ''; //'51.81.82.175:80'; // Won't be needed if there's no block on any of the APIs needed

    /*public function getUpdates($offset = null) // returns an array of Updates if there exists any, null on none.
    {
        $url = "https://api.telegram.org/bot" . self::BOT_KEY . "/setWebHooks?url=" . self::WEBAPP_URL . '&' . "getUpdates?"
            . (($offset) ? '&offset=' . $offset + 1 : '');
        $curlHandle = curl_init($url);
        curl_setopt($curlHandle, CURLOPT_PROXY, self::PROXY);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
        $jsonResult = curl_exec($curlHandle);
        curl_close($curlHandle);
        $result = json_decode($jsonResult);
        // we want the result property 
        return (!empty($result->result) ? $result->result : null);
    }*/

    public function sendMessage($message, $chatId) // returns true on success, false on failure
    {
        $messages = $this->sectionLyrics($message);
        foreach ($messages as $message) {
            $url = "https://api.telegram.org/bot" . self::BOT_KEY . "/sendMessage";

            $curlHandle = curl_init($url);

            curl_setopt($curlHandle, CURLOPT_PROXY, self::PROXY);
            curl_setopt($curlHandle, CURLOPT_POST, true);
            curl_setopt($curlHandle, CURLOPT_POSTFIELDS, ['text' => $message, 'chat_id' => $chatId]);
            curl_setopt($curlHandle, CURLOPT_HTTPHEADER, ['Content-Type' => 'application/json']);
            curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);

            $jsonResult = curl_exec($curlHandle);
            curl_close($curlHandle);
            $result = json_decode($jsonResult);
        }
        return $result->ok;
    }

    public function processQuery($update)
    {
        //while (1) {
            //$updates = $this->getUpdates();

            //if ($updates) {
                //$this->moveOffsetPastLast();
                //foreach ($updates as $update) {
                    $query = $update->message->text;
                    $from = $update->message->from->id;
                    $reply = $this->processQueryMessage($query);
                    // the reply (lyrics) could be more than 4096 UTF characters (Telegram's limit for a message) so it has to be chunked up to multiple parts and be sent sequentially
                    $this->sendMessage($reply, $from);
                //}
            //}
        //}
    }
    public function processQueryMessage($query)
    {
        // the query is either a '/start' command which makes the bot introduce itself or it's a search query for a song
        $query = htmlspecialchars(strip_tags($query));
        $message = '';
        if (str_starts_with($query, '/start')) {
            $message = "Hi" . PHP_EOL . "you can find the lyrics to your song by typing in the name of the song.(along with artist(s) name to get more accurate result)" . PHP_EOL . "The bot will search genius for the song and if any match(es) are found, the closest search result will be returned to you" . PHP_EOL;
        } else {
            $song = Genius::scrapeSong($query);
            $message = ($song ? $this->fetchLyrics($song) : "No results, try again");
        }
        return $message;
    }
    public function fetchLyrics($song)
    {
        $title = $song->full_title;

        do { // could fail retrieving it
            $client = new Client(HttpClient::create(['proxy' => self::PROXY]));
            $request = $client->request('GET', $song->url);
            $lyrics = ($request->filter('.lyrics')->each(function ($node) {
                $text = $node->html();
                return $text;
            }));
        } while (empty($lyrics));

        $lyrics = $lyrics[0];
        $lyrics = $title . "<br>" . $lyrics;
        $lyrics = (new \Html2Text\Html2Text($lyrics))->getText();
        $lyrics = $this->omitLinkNotes($lyrics);
        return $lyrics; // returns the HTML document of the lyrics
    }
    /*public function moveOffsetPastLast() // moves the offset so that no new Updates are available
    {
        $updates = $this->getUpdates();
        $lastId = $updates[count($updates) - 1]->update_id;
        $this->getUpdates($lastId);
    }*/
    private function omitLinkNotes($string) // don't look at this function, it just omits the links from the lyrics
    {
        $string = (preg_replace("(\\[\\/.*\\])", '', $string));
        return $string;
    }
    private function sectionLyrics($lyrics) // the chunker function
    {
        $splitLyrics = [];
        $cuts = ceil(strlen($lyrics) / 4096);
        for ($i = 0; $i < $cuts; $i++) {
            $splitLyrics[] = substr($lyrics, $i * 4096, (strlen($lyrics) - $i * 4096) >= 4096 ? 4096 : (strlen($lyrics) - $i * 4096));
        }
        return $splitLyrics;
    }
}
