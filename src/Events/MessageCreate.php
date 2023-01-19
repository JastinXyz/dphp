<?php

namespace Bot\Events;

use Discord\Builders\MessageBuilder;
use Discord\WebSockets\Event;

class MessageCreate extends Init
{
    public $name = Event::MESSAGE_CREATE;

    public function execute($message)
    {
        $prefixes = $this->conf['prefix'];

        if ($message->author->bot) return;

        $prefix = array_filter($prefixes, function ($x) use ($message) {
            return strpos($message->content, $x) === 0;
        });

        $client = $this->discord->user;
        if (trim($message->content) === "<@$client->id>") return $message->reply(MessageBuilder::new()->setAllowedMentions(['parse' => []])->setContent("Gunakan `$prefixes[0]help` untuk melihat command list!"));

        if (!empty($prefix)) {
            $prefix = join(" ", $prefix);
            $args = explode(" ", substr($message->content, strlen($prefix)));
            $command = strtolower(array_shift($args));

            $commandData = $this->commandInit->getCommandData($command);
            if (!$commandData) return;

            if (strtolower($commandData[0]->category) === "owner") {
                if (!in_array($message->author->id, $this->conf['owners'])) return $message->reply(MessageBuilder::new()->setAllowedMentions(['parse' => []])->setContent("kamu tidak memiliki akses!"));
            }

            $commandData[0]->execute($message, $args);
        }
    }
}
