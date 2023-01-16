<?php

namespace Bot\Commands\Fun;

use Bot\Commands\Command;

class Say extends Command
{
    public $name = "say";
    public $aliases = ['echo'];
    public $description = "Echo command.";
    public $category = "Fun";

    public function execute($message, $args)
    {
        $message->channel->sendMessage(join(" ", $args));
    }
}
