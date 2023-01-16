<?php

namespace Bot\Commands\General;

use Bot\Commands\Command;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Embed\Embed;

class Help extends Command
{
    public $name = "help";
    public $aliases = ['menu', '?'];
    public $description = "Command menu.";
    public $category = "General";
    
    public function execute($message, $args)
    {
        $embed = new Embed($this->discord);
        $embed->setColor("0x272935");
        $builder = new MessageBuilder();
        $builder->setAllowedMentions(['parse' => []]);

        $commands = $this->getAllCommands();

        if ($args) {
            $info = $this->getCommandData($args[0]);
            if (!$info) return $message->reply($builder->setContent("Command **$args[0]** tidak ditemukan!!"));

            $info = $info[0];
            $embed->setAuthor($info->name);
            $embed->setDescription($info->description);
            $embed->addFieldValues("Kategori", $info->category);
            $embed->addFieldValues("Alias", count($info->aliases) ? "`".join("`, `", $info->aliases)."`" : '`tidak ada`');

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