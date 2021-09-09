<?php

namespace Classes;

use Dotenv\Dotenv;

class Env
{
    public $dotEnv;
    public function __construct()
    {
        $this->dotEnv = (Dotenv::createImmutable(dirname(__DIR__))->load());
    }
}
