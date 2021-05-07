<?php

require 'vendor/autoload.php';

set_time_limit(0); // the script should run forever

use Classes\Bot;
use Classes\Util;
use Classes\Genius;

$bot = new Bot();

$bot->processQueries();