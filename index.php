<?php

require 'vendor/autoload.php';


use Classes\{
    Bot,
};

$update = json_decode(file_get_contents('php://input'));
$bot = new Bot();
var_dump("Gets here");
die;
$bot->processQuery($update);
