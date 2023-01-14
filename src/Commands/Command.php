<?php

namespace Bot\Commands;

class Command
{
    protected $discord;
    protected $conn;

    public function __construct($discord, $conn)
    {
        $this->discord = $discord;
        $this->conn = $conn;
    }

}