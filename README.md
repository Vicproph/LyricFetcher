# LyricFetcher
A telegram bot that runs a PHP-script to fetch lyrics for songs ( lyrics are web-scraped from the Genius website)

#Introduction
A Telegram bot API application that gets an input string from the Bot Api (from a User) and uses the "search" Genius API to search
the site for the song that the User has entered. (could have inputted the artist(s) name for a more accurate result)
If there's any match for the User query, the first one (the closest and the most relevant one) will be chosen, and then in the song lryics page,
we'll get the lyrics out using web-scraper libraies (here we used Goutte). After that's done, we'll send back the text to the user.

#Requirements
-most of the requests would work fine with cURL, but the Web-scraping part needs web-scraping libraries and for that 
we use Goutte which uses Guzzle as its HTTP request sender.
for getting a plain-text out of the web-scraped html, we use the Html2Text that is good for converting HTML to Plain Text.
