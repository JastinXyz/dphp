<?php

namespace Bot\Commands\General;

use Bot\Commands\Command;

class Ping extends Command
{
    public $name = "ping";
    public $description = "Pong!";
    public $category = "General";

    public function execute($message)
    {
        $message->channel->sendMessage('Pong!');
    }
}
