<?php

namespace Bot\Commands;

use Bot\Handler\Reflect;

class Command
{
    protected $discord;
    protected $conn;

    public function __construct($discord, $conn)
    {
        $this->discord = $discord;
        $this->conn = $conn;
    }

    public function getAllCommands()
    {
        $commands = array();
        $r = new Reflect('src/Commands');
        $classes = $r->getClasses();

        foreach($classes as $class) {
            $check = new $class($this->discord, $this->conn);
            if(@$check->name) $commands[$check->name] = $check;
        }

        return $commands;
    }

    public function getCommandData($command)
    {
        $result = array();
        $commands = $this->getAllCommands();
        foreach ($commands as $c) {
            if (strtolower($c->name) === strtolower($command) || (isset($c->aliases) && is_array($c->aliases) && in_array(strtolower($command), array_map('strtolower', $c->aliases)))) {
                $result[] = $c;
            }
        }

        return $result;
    }

}