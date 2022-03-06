<?php

namespace Classes;

use Goutte\Client as GoutteClient;
use GuzzleHttp\Client;
use Symfony\Component\HttpClient\HttpClient;

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
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'proxy' => self::PROXY
            ]);

            $result = json_decode($response->getBody()->getContents());
        }
        return $result->ok;
    }

    public function processQuery($update)
    {
        $query = $update->message->text;
        $from = $update->message->from->id;
        $reply = $this->processQueryMessage($query);
        // the reply (lyrics) could be more than 4096 UTF characters (Telegram's limit for a message) so it has to be chunked up to multiple parts and be sent sequentially
        $this->sendMessage($reply, $from);
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
            $client = new GoutteClient(HttpClient::create(['proxy' => self::PROXY]));
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
