<?php

namespace Classes;

use Goutte\Client as GoutteClient;
use GuzzleHttp\Client;
use phpDocumentor\Reflection\PseudoTypes\LowercaseString;
use Symfony\Component\HttpClient\CurlHttpClient;

class Bot
{
    const PROXY = ''; // Won't be needed if there's no block on any of the APIs needed
    public function sendMessage($message, $chatId) // returns true on success, false on failure
    {

        $messages = $this->sectionLyrics($message);
        foreach ($messages as $message) {
            $url = "https://api.telegram.org/bot" . getenv('BOT_KEY') . "/sendMessage";
            $client = new Client();
            $response = $client->post($url, [
                'json' => [
                    'chat_id' => $chatId,
                    'text' => mb_convert_encoding($message, 'UTF-8', 'UTF-8')
                ],
                'verify' => Test::DEVELOPMENT_MODE ? false : true,
                'proxy' => self::PROXY
            ]);

            $result = json_decode($response->getBody()->getContents());
        }
        return $result->ok;
    }

    public function processQuery($update)
    {
        $query = html_entity_decode($update->message->text);
        $from =  $update->message->from->id;
        $reply = $this->processQueryMessage($query);
        // the reply (lyrics) could be more than 4096 UTF characters (Telegram's limit for a message) so it has to be chunked up to multiple parts and be sent sequentially
        if (is_array($reply)) {
            $songs = $reply;
            $this->sendMenuButtons($from, $songs);
        } else if (is_string($reply)) {
            $this->sendMessage($reply, $from);
        }
        $this->informAuthority($update);
    }
    private function informAuthority($update)
    {
        $query = $update->message->text;
        $this->sendMessage($query = "{$update->message->from->first_name} {$update->message->from->last_name} \t(  @{$update->message->from->username} ) just Queried!\n (Query = '$query')", getenv('AUTHORITY'));
        echo "$query\n";
    }
    public function processQueryMessage($query)
    {
        // the query is either a '/start' command which makes the bot introduce itself or it's a search query for a song
        $query = strip_tags($query);
        $message = '';
        if (str_starts_with($query, '/start')) {
            $message = "Hi" . PHP_EOL . "you can find the lyrics to your song by typing in the name of the song.(along with artist(s) name to get more accurate result)" . PHP_EOL . "The bot will search genius for the song and if any match(es) are found, the closest search result will be returned to you" . PHP_EOL;
        } else if (preg_match('/^[0-9]\./', $query)) { // selected a song
            $query = self::fixateQuery($query);
            $song = $this->scrapeSong($query, 0);
            $message = ($song != null) ? $this->fetchLyrics($song) : "No results, try again";
        } else {
            $songs = $this->scrapeSong($query);
            $message = ($songs ? $songs : "No results, try again");
        }
        return $message;
    }
    public function fetchLyrics($song)
    {
        $title = $song['full_title'];
        $attempts = 0;
        do { // could fail retrieving it
            $httpClient = new CurlHttpClient([
                'http_version' => '1.1',
                'proxy' => Bot::PROXY,
                'verify_peer' => Test::DEVELOPMENT_MODE ? false : true,
                'verify_host' => Test::DEVELOPMENT_MODE ? false : true
            ]);
            $client = new GoutteClient($httpClient);
            $request = $client->request('GET', $song['url']);
            $lyrics = ($request->filter('#lyrics-root [data-lyrics-container=true]')->each(function ($node) {
                $text = $node->html();
                return $text;
            }));
        } while (empty($lyrics) && $attempts <= 5);
        if (empty($lyrics))
            return 'This one doesn\'t have lyrics yet.';
        $lyrics = implode($lyrics);
        $lyrics = $title . "<br><br>" . $lyrics;
        $lyrics = (new \Html2Text\Html2Text($lyrics))->getText();
        $lyrics = $this->omitLinkNotes($lyrics);
        return $lyrics; // returns the HTML document of the lyrics
    }
    public static function scrapeSong($searchQuery, $index = null) // returns the song(s) returned from the search list from Genius
    {
        $url = "https://genius.p.rapidapi.com/search?q=" . urlencode($searchQuery);
        $client = new Client([
            'verify' => Test::DEVELOPMENT_MODE ? false : true
        ]);
        $response = $client->get($url, [
            'proxy' => Bot::PROXY,
            'headers' => [
                'X-RapidAPI-Host' => 'genius.p.rapidapi.com',
                'X-RapidAPI-Key' => getenv('RAPID_API_KEY')
            ]
        ]);
        $result = $response->getBody()->getContents();
        $result = json_decode($result);
        $songs = [];
        foreach ($result->response->hits as $i => $hit) {
            $songs[$i]['full_title'] = $hit->result->full_title;
            $songs[$i]['url'] = $hit->result->url;
        }
        if (empty($songs)) // could have no results
            return null;
        return $index === null ? $songs : $songs[$index];
    }
    public function sendMenuButtons($from, $songs)
    {
        $i = 0;
        $songTitles = array_map(function ($song) use (&$i) {
            return  [['text' => ++$i . '.' . $song['full_title']]];
        }, $songs);
        $textBeforeButtons = "Choose your result.";
        $client = new Client();
        $response = $client->post("https://api.telegram.org/bot" . getenv("BOT_KEY") . "/sendMessage", [
            'proxy' => Bot::PROXY,
            'json' => [
                'chat_id' => $from,
                'text' => $textBeforeButtons,
                'reply_markup' => [
                    'keyboard' => $songTitles,
                    'resize_keyboard' => true,
                    'one_time_keyboard' => true
                ],
            ],
            'verify' => Test::DEVELOPMENT_MODE ? false : true
        ]);
        $jsonResult = json_decode($response->getBody()->getContents());
    }

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
    static public function fixateQuery($query)
    {

        $partitionedQuery = explode('.', $query, 2);
        $partitionedQuery[1] = ltrim($partitionedQuery[1]);
        $byPartitions = explode('by', $partitionedQuery[1]);
        $output = '';
        $i = 0;
        for (; $i < count($byPartitions); $i++) {
            if ($i < count($byPartitions) - 2)
                $output .= ($byPartitions[$i] . ("by"));
            else
                $output .=  ' ' . trim($byPartitions[$i]);
        }
        return trim($output);
    }
}
