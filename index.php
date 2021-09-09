<?php

require 'vendor/autoload.php';


use Classes\{
    Bot,
    Util,
    Genius,
    Env
};
use GuzzleHttp\Client;

$dotEnv = (new Env)->dotEnv;
$update = json_decode(file_get_contents('php://input'));
$bot = new Bot($dotEnv);
$bot->processQuery($update);
