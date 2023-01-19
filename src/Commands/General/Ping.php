<?php

namespace Bot\Commands\General;

use Bot\Commands\Command;
use Carbon\Carbon;

class Ping extends Command
{
    public $name = "ping";
    public $aliases = ['latency'];
    public $description = "Pong!";
    public $category = "General";

    public function execute($message)
    {
        $res = Carbon::now()->valueOf() - Carbon::parse($message->timestamp)->valueOf();
        $message->channel->sendMessage("Pong! $res"."ms");
    }
}
