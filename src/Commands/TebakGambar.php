<?php

namespace Bot\Commands;

use Bot\Handler\MessageCollector;
use Discord\Builders\MessageBuilder;
use Discord\Builders\Components\ActionRow;
use Discord\Builders\Components\Button;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Interactions\Interaction;

class TebakGambar extends Command
{
    public function execute($message)
    {
        $guild = $message->guild;
        $channel = $message->channel;
        $author = $message->author;
        $table = "MG1_$guild->id" . "_" . "$channel->id";
        $tableRonde = "MG1_RONDE";

        $this->conn->query("CREATE TABLE IF NOT EXISTS $table (
                id int(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                host TINYINT(1) NOT NULL,
                player varchar(20) NOT NULL,
                poin int(99) NOT NULL
            )");

        $this->conn->query("CREATE TABLE IF NOT EXISTS $tableRonde (
                id int(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                guild varchar(99) NOT NULL,
                channel varchar(99) NOT NULL,
                count int(99) NOT NULL
            )");

        $this->conn->query("INSERT INTO $table (host, player) VALUES (1, $author->id)");
        $this->conn->query("INSERT INTO $tableRonde (guild, channel, count) VALUES ($guild->id, $channel->id, 0)");
        $host = $this->conn->query("SELECT * FROM $table WHERE host=1")->fetch_assoc();
        $host = $host['player'];
        $getPlayers = $this->conn->query("SELECT * from $table");
        $players = mysqli_fetch_all($getPlayers, MYSQLI_ASSOC);
        $playersId = array_column($players, 'player');

        $gabungButton = Button::new(Button::STYLE_PRIMARY);
        $gabungButton->setLabel("Gabung");

        $startButton = Button::new(Button::STYLE_SUCCESS);
        $startButton->setLabel("Gass");

        $exitButton = Button::new(Button::STYLE_DANGER);
        $exitButton->setLabel("Hentikan Game");

        $row = ActionRow::new();

        $row->addComponent($gabungButton);
        $row->addComponent($startButton);
        $row->addComponent($exitButton);

        $embed = new Embed($this->discord);
        $embed->setAuthor("Tebak Gambar | Player List");
        $embed->setDescription(setupMinigamesDescription($players));
        $embed->setColor(rand(0, 0xffffff));

        $builder = MessageBuilder::new();
        $builder->setAllowedMentions(['parse' => []]);
        $builder->addEmbed($embed);
        $builder->addComponent($row);
        $message->reply($builder);

        $gabungButton->setListener(function (Interaction $interaction) use ($table, $embed, $host) {
            $member = $interaction->member;
            $getPlayers = $this->conn->query("SELECT * from $table");
            $players = mysqli_fetch_all($getPlayers, MYSQLI_ASSOC);
            $playersId = array_column($players, 'player');
            if (isset($playersId) && in_array($member->id, $playersId)) {
                if ($member->id === $host) return;
                $this->conn->query("DELETE FROM $table WHERE player=$member->id");
                $getPlayers = $this->conn->query("SELECT * from $table");
                $players = mysqli_fetch_all($getPlayers, MYSQLI_ASSOC);
                $embed->setDescription(setupMinigamesDescription($players));
                $interaction->updateMessage(MessageBuilder::new()->addEmbed($embed));
                return;
            }

            $this->conn->query("INSERT INTO $table (host, player) VALUES (0, $member->id)");
            $getPlayers = $this->conn->query("SELECT * from $table");
            $players = mysqli_fetch_all($getPlayers, MYSQLI_ASSOC);
            $embed->setDescription(setupMinigamesDescription($players));
            $interaction->updateMessage(MessageBuilder::new()->addEmbed($embed));
            return;
        }, $this->discord);

        $startButton->setListener(function (Interaction $interaction) use ($table, $row, $embed, $builder, $host, $message, $tableRonde) {
            $GLOBALS['conn'] = $this->conn;
            $GLOBALS['table'] = $table;
            $GLOBALS['tableRonde'] = $tableRonde;
            $GLOBALS['discord'] = $this->discord;
            $GLOBALS['host'] = $host;
            $GLOBALS['embed'] = $embed;

            $member = $interaction->member;
            if ($member->id !== $host) return $interaction->respondWithMessage(MessageBuilder::new()->setContent('hanya host saja yang dapat memulai permainan'), true);

            $builder->removeComponent($row);
            $interaction->updateMessage($builder);
            $col = new MessageCollector($message);
            $col->init();
            return;
        }, $this->discord);

        $exitButton->setListener(function (Interaction $interaction) use ($builder, $table, $tableRonde, $row, $embed, $host) {
            $member = $interaction->member;
            $guild = $interaction->guild;
            $channel = $interaction->channel;
            if ($member->id !== $host) return $interaction->respondWithMessage(MessageBuilder::new()->setContent('hanya host saja yang dapat menghentikan permainan'), true);

            $getPlayers = $this->conn->query("SELECT * from $table");
            $players = mysqli_fetch_all($getPlayers, MYSQLI_ASSOC);
            $embed->setDescription(setupMinigamesDescription($players));
            $embed->setFooter('Permainan dihentikan');
            $embed->setColor('0xff0000');
            $builder->removeComponent($row);
            $builder->setEmbeds([]);
            $builder->addEmbed($embed);
            $this->conn->query("DROP TABLE $table");
            $this->conn->query("DELETE FROM $tableRonde WHERE `$tableRonde`.`guild`=$guild->id AND `$tableRonde`.`channel`=$channel->id");
            $interaction->updateMessage($builder);
        }, $this->discord);
    }
}
