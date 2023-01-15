<?php

namespace Bot\Commands\General;

use Bot\Commands\Command;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Embed\Embed;

class Help extends Command
{
    public $name = "help";
    public $description = "Command menu.";
    public $category = "General";
    
    public function execute($message, $args, $commands)
    {
        $embed = new Embed($this->discord);
        $embed->setColor("0x272935");
        $builder = new MessageBuilder();
        $builder->setAllowedMentions(['parse' => []]);

        if ($args) {
            $info = @$commands[$args[0]];

            if (!$info) return $message->reply($builder->setContent("Command tidak ditemukan! Gunakan nama command bukan alias!"));

            $embed->setAuthor($info->name);
            $embed->setDescription($info->description);
            $embed->addFieldValues("Kategori", $info->category);

            return $message->channel->sendMessage($builder->addEmbed($embed)->setReplyTo($message));
        }

        $client = $this->discord->user;
        $embed->setAuthor("List perintah $client->username", $client->avatar);

        $fields = [];
        foreach ($commands as $command) {
            if (!@$fields[$command->category]) {
                $fields[$command->category] = "`$command->name`";
            } else {
                $fields[$command->category] .= ", `$command->name`";
            }
        }

        foreach ($fields as $category => $commandList) {
            $embed->addFieldValues($category, $commandList);
        }

        $message->channel->sendMessage($builder->addEmbed($embed)->setReplyTo($message));
    }
}