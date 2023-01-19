<?php

namespace Bot\Events;

class Ready extends Init
{
    public $name = "ready";

    public function execute()
    {
        echo "Bot is ready!", PHP_EOL;
        return;
    }
}