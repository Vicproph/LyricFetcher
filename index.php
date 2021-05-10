<?php

require 'vendor/autoload.php';


use Classes\Bot;
use Classes\Util;
use Classes\Genius;

$update = json_decode(file_get_contents('php://input'));
$bot = new Bot();
$bot->processQuery($update);
