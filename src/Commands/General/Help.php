<?php

namespace Bot\Commands\General;

use Bot\Commands\Command;

class Help extends Command
{
    public $name = "help";
    public $description = "Command menu.";
    public $category = "General";
    
    public function execute($message, $args, $commands)
    {
        if($args)
        {
            $info = $commands[$args[0]];
            $message->channel->sendMessage("Name: $info->name\nDesc: $info->description");
        }
    }
}