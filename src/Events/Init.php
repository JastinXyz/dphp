<?php

namespace Bot\Events;

use Bot\Commands\Command;
use Bot\Handler\Reflect;

class Init
{
    protected $discord;
    protected $conn;
    protected $conf;
    protected $commandInit;

    public function __construct($discord, $conn, $conf)
    {
        $this->discord = $discord;
        $this->conn = $conn;
        $this->conf = $conf;
        $this->commandInit = new Command($this->discord, $this->conn);
    }

    public function init()
    {
        $EventsReflect = new Reflect('src\Events');
        $EventsFile  = $EventsReflect->getClasses();

        foreach ($EventsFile as $events) {
            $check = new $events($this->discord, $this->conn, $this->conf);
            if (@$check->name) $this->discord->on($check->name, fn (...$args) => $check->execute(...$args));
        }

        return;
    }
}
