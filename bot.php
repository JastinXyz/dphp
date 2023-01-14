<?php

include __DIR__ . '/vendor/autoload.php';
include __DIR__ . '/koneksi.php';

require __DIR__ . '/utils/functions.php';

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\WebSockets\Intents;
use Discord\WebSockets\Event;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Embed\Embed;
use Discord\Builders\Components\ActionRow;
use Discord\Builders\Components\Button;
use Discord\Parts\Interactions\Interaction;
use Discord\Helpers\Collection;
use Monolog\Logger;
use Monolog\Handler\NullHandler;
use Noodlehaus\Config;
use Noodlehaus\Parser\Json;

$conf = new Config('config.json');
$logger = new Logger('Logger');
$logger->pushHandler(new NullHandler());
$discord = new Discord([
    'logger' => $logger,
    'token' => $conf['token'],
    'intents' => Intents::getDefaultIntents() | Intents::MESSAGE_CONTENT
]);

function messageCollectorHandler($message, $isFirst = true, $players = null, $winnerId = null, $jawaban = null)
{
    $tableRonde = $GLOBALS['tableRonde'];
    $guild = $message->guild;
    $channel = $message->channel;

    $GLOBALS['conn']->query("UPDATE $tableRonde SET count = count + 1 WHERE guild=$guild->id AND channel=$channel->id");
    $totalRonde = $GLOBALS['conn']->query("SELECT * FROM $tableRonde WHERE guild=$guild->id AND channel=$channel->id")->fetch_assoc();
    $totalRonde = $totalRonde['count'];

    if ($isFirst) {
        $GLOBALS['embed']->setAuthor('Tebak Gambar | Bersiap!');
        $ronde = 'pertama';
    } else {
        if($jawaban) {
            $GLOBALS['embed']->setAuthor('Game Paused');
            $GLOBALS['embed']->setColor('0xffff00');
            $GLOBALS['embed']->setDescription("Tidak ada yang berhasil menjawab. Jawaban:\n```$jawaban```\n". setupMinigamesDescription($players));
        } else {
            $GLOBALS['embed']->setDescription(setupMinigamesDescription($players));
        }

        $ronde = 'selanjutnya';
    }

    $GLOBALS['embed']->setFooter("Ronde $ronde akan dimulai dalam 5 detik");
    $GLOBALS['embed']->setTimestamp();
    $getReadyBuilder = MessageBuilder::new();
    $getReadyBuilder->addEmbed($GLOBALS['embed']);

    if ($winnerId) $getReadyBuilder->setContent("<@$winnerId> Menjawab dengan benar!");

    $message->channel->sendMessage($getReadyBuilder)->done(function ($getReadyMessage) use ($message, $totalRonde) {
        sleep(5);
        $loadJson = file_get_contents('storage/tebakgambar.json');
        $descodedJson = json_decode($loadJson, true);
        $random = array_rand($descodedJson);
        $selected = $descodedJson[$random];

        $qEmbed = new Embed($GLOBALS['discord']);
        $qEmbed->setImage($selected['image']);
        $qEmbed->setFooter("Tebak Gambar nomer " . $selected['no'] . " level " . $selected['level']);
        $qEmbed->setColor(rand(0, 0xffffff));
        $qEmbed->setTimestamp();

        $message->channel->sendMessage(MessageBuilder::new()->addEmbed($qEmbed))->done(function ($msg) use ($getReadyMessage, $message, $selected, $totalRonde) {
            $getReadyMessage->delete();
            $getPlayers = $GLOBALS['conn']->query("SELECT * from " . $GLOBALS['table']);
            $players = mysqli_fetch_all($getPlayers, MYSQLI_ASSOC);
            $playersId = array_column($players, 'player');

            $filter = fn ($message) => (in_array($message->author->id, $playersId) && strtolower($message->content) === strtolower($selected['jawaban'])) || ($message->author->id === $GLOBALS['host'] && strtolower($message->content) === "exit");

            $message->channel->createMessageCollector($filter, [
                'time' => 60000,
                'limit' => 1
            ])->done(function (Collection $collected) use ($selected, $message, $msg, $totalRonde) {
                $table = $GLOBALS['table'];
                $tableRonde = $GLOBALS['tableRonde'];

                $guild = $message->guild;
                $channel = $message->channel;

                if (!$collected->count()) {
                    $getPlayers = $GLOBALS['conn']->query("SELECT * from $table");
                    $players = mysqli_fetch_all($getPlayers, MYSQLI_ASSOC);
                    $msg->delete();
                    messageCollectorHandler($message, false, $players, null, $selected['jawaban']);
                    return;
                } else if (strtolower($collected[0]->content) === "exit") {
                    $msg->delete();

                    $getPlayers = $GLOBALS['conn']->query("SELECT * from $table");
                    $players = mysqli_fetch_all($getPlayers, MYSQLI_ASSOC);

                    $winEmbed = new Embed($GLOBALS['discord']);
                    $winEmbed->setAuthor("Tebak Gambar | Game ended");
                    $winEmbed->setDescription(setupMinigamesDescription($players, true));
                    $winEmbed->setColor("0x5865F2");

                    usort($players, function ($a, $b) {
                        return $b["poin"] - $a["poin"];
                    });

                    $winner = $players[0]['player'];
                    $winnerPoin = $players[0]['poin'];

                    $playerWinEmbed = new Embed($GLOBALS['discord']);
                    $playerWinEmbed->setDescription("**<@$winner> Telah memenangkan permainan!**");
                    $playerWinEmbed->setColor("0x5865F2");
                    $playerWinEmbed->setFooter("GG! $totalRonde Ronde telah dimainkan.");
                    $playerWinEmbed->setTimestamp();

                    if($winnerPoin < 1) $playerWinEmbed->setDescription("**Nampaknya permainan seri.**");

                    $winBuilder = MessageBuilder::new();
                    $winBuilder->setEmbeds([$winEmbed, $playerWinEmbed]);

                    $message->channel->sendMessage($winBuilder);

                    $GLOBALS['conn']->query("DROP TABLE IF EXISTS $table");
                    $GLOBALS['conn']->query("DELETE FROM $tableRonde WHERE `$tableRonde`.`guild`=$guild->id AND `$tableRonde`.`channel`=$channel->id");
                    return;
                } else {
                    $author = $collected[0]->author;
                    $GLOBALS['conn']->query("UPDATE $table SET poin = poin + 1 WHERE player = $author->id");
                    $msg->delete();
                    $getPlayers = $GLOBALS['conn']->query("SELECT * from $table");
                    $players = mysqli_fetch_all($getPlayers, MYSQLI_ASSOC);

                    messageCollectorHandler($message, false, $players, $author->id);
                    return;
                }
            });
        });
    });
}

$discord->on('ready', function (Discord $discord) use ($conn) {
    echo "Bot is ready!", PHP_EOL;

    // Listen for messages.
    $discord->on(Event::MESSAGE_CREATE, function (Message $message, Discord $discord) use ($conn) {
        if ($message->author->bot) return;

        if($message->content == '!a') {
            $author = $message->author;

            $yz = new Embed($discord);
            $yz->setAuthor("aaaaaaa");

            $ab = new Embed($discord);
            $ab->setDescription("**<@$author->id> abc**");
            $message->channel->sendMessage(MessageBuilder::new()->setEmbeds([$yz, $ab]));
            return;
        }

        if ($message->content == '!tbk') {
            $guild = $message->guild;
            $channel = $message->channel;
            $author = $message->author;
            $table = "MG1_$guild->id" . "_" . "$channel->id";
            $tableRonde = "MG1_RONDE";

            $conn->query("CREATE TABLE IF NOT EXISTS $table (
                id int(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                host TINYINT(1) NOT NULL,
                player varchar(20) NOT NULL,
                poin int(99) NOT NULL
            )");

            $conn->query("CREATE TABLE IF NOT EXISTS $tableRonde (
                id int(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                guild varchar(99) NOT NULL,
                channel varchar(99) NOT NULL,
                count int(99) NOT NULL
            )");

            $conn->query("INSERT INTO $table (host, player) VALUES (1, $author->id)");
            $conn->query("INSERT INTO $tableRonde (guild, channel, count) VALUES ($guild->id, $channel->id, 0)");
            $host = $conn->query("SELECT * FROM $table WHERE host=1")->fetch_assoc();
            $host = $host['player'];
            $getPlayers = $conn->query("SELECT * from $table");
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

            $embed = new Embed($discord);
            $embed->setAuthor("Tebak Gambar | Player List");
            $embed->setDescription(setupMinigamesDescription($players));
            $embed->setColor(rand(0, 0xffffff));

            $builder = MessageBuilder::new();
            $builder->setAllowedMentions(['parse' => []]);
            $builder->addEmbed($embed);
            $builder->addComponent($row);
            $message->reply($builder);

            $gabungButton->setListener(function (Interaction $interaction) use ($conn, $table, $embed, $host) {
                $member = $interaction->member;
                $getPlayers = $conn->query("SELECT * from $table");
                $players = mysqli_fetch_all($getPlayers, MYSQLI_ASSOC);
                $playersId = array_column($players, 'player');
                if(isset($playersId) && in_array($member->id, $playersId)) {
                    if($member->id === $host) return;
                    $conn->query("DELETE FROM $table WHERE player=$member->id");
                    $getPlayers = $conn->query("SELECT * from $table");
                    $players = mysqli_fetch_all($getPlayers, MYSQLI_ASSOC);
                    $embed->setDescription(setupMinigamesDescription($players));
                    $interaction->updateMessage(MessageBuilder::new()->addEmbed($embed));
                    return;
                }

                $conn->query("INSERT INTO $table (host, player) VALUES (0, $member->id)");
                $getPlayers = $conn->query("SELECT * from $table");
                $players = mysqli_fetch_all($getPlayers, MYSQLI_ASSOC);
                $embed->setDescription(setupMinigamesDescription($players));
                $interaction->updateMessage(MessageBuilder::new()->addEmbed($embed));
                return;
            }, $discord);

            $startButton->setListener(function (Interaction $interaction) use ($discord, $conn, $table, $row, $embed, $builder, $host, $message, $tableRonde) {
                $GLOBALS['conn'] = $conn;
                $GLOBALS['table'] = $table;
                $GLOBALS['tableRonde'] = $tableRonde;
                $GLOBALS['discord'] = $discord;
                $GLOBALS['host'] = $host;
                $GLOBALS['embed'] = $embed;

                $member = $interaction->member;
                if ($member->id !== $host) return $interaction->respondWithMessage(MessageBuilder::new()->setContent('hanya host saja yang dapat memulai permainan'), true);

                $builder->removeComponent($row);
                $interaction->updateMessage($builder);
                messageCollectorHandler($message);
                return;
            }, $discord);

            $exitButton->setListener(function (Interaction $interaction) use ($conn, $builder, $table, $tableRonde, $row, $embed, $host) {
                $member = $interaction->member;
                $guild = $interaction->guild;
                $channel = $interaction->channel;
                if ($member->id !== $host) return $interaction->respondWithMessage(MessageBuilder::new()->setContent('hanya host saja yang dapat menghentikan permainan'), true);

                $getPlayers = $conn->query("SELECT * from $table");
                $players = mysqli_fetch_all($getPlayers, MYSQLI_ASSOC);
                $embed->setDescription(setupMinigamesDescription($players));
                $embed->setFooter('Permainan dihentikan');
                $embed->setColor('0xff0000');
                $builder->removeComponent($row);
                $builder->setEmbeds([]);
                $builder->addEmbed($embed);
                $conn->query("DROP TABLE $table");
                $conn->query("DELETE FROM $tableRonde WHERE `$tableRonde`.`guild`=$guild->id AND `$tableRonde`.`channel`=$channel->id");
                $interaction->updateMessage($builder);
            }, $discord);
        }
    });
});

$discord->run();
