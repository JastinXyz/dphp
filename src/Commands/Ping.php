<?php

namespace Bot\Commands;

class Ping extends Command
{
    public function execute($message)
    {
        $message->channel->sendMessage('Pong!');
    }
}
