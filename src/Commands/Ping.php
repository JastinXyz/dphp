<?php

namespace Bot\Commands;

use Discord\Builders\MessageBuilder;

class Ping extends Command
{
    public function execute($message)
    {
        $message->channel->sendMessage(MessageBuilder::new()->setContent('pong!'));
    }
}
