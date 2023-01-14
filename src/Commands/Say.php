<?php

namespace Bot\Commands;

class Say extends Command
{
    public function execute($message, $args)
    {
        $message->channel->sendMessage(join(" ", $args));
    }
}
