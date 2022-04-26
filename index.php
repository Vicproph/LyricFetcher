<?php

require 'vendor/autoload.php';


use Classes\{
    Bot,
    Genius,
};

$update = json_decode(file_get_contents('php://input'));
$bot = new Bot();
$bot->processQuery($update);
Genius::scrapeSong('master');
