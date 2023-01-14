<?php

namespace Bot\Handler;

use Discord\Builders\MessageBuilder;
use Discord\Helpers\Collection;
use Discord\Parts\Embed\Embed;

class MessageCollector
{
    public $message;
    public $isFirst;
    public $players;
    public $winnerId;
    public $jawaban;

    public function __construct($message, $isFirst = true, $players = null, $winnerId = null, $jawaban = null)
    {
        $this->message = $message;
        $this->isFirst = $isFirst;
        $this->players = $players;
        $this->winnerId = $winnerId;
        $this->jawaban = $jawaban;
    }

    public function init()
    {
        $tableRonde = $GLOBALS['tableRonde'];
        $guild = $this->message->guild;
        $channel = $this->message->channel;

        $GLOBALS['conn']->query("UPDATE $tableRonde SET count = count + 1 WHERE guild=$guild->id AND channel=$channel->id");
        $totalRonde = $GLOBALS['conn']->query("SELECT * FROM $tableRonde WHERE guild=$guild->id AND channel=$channel->id")->fetch_assoc();
        $totalRonde = $totalRonde['count'];

        if ($this->isFirst) {
            $GLOBALS['embed']->setAuthor('Tebak Gambar | Bersiap!');
            $ronde = 'pertama';
        } else {
            if ($this->jawaban) {
                $GLOBALS['embed']->setAuthor('Game Paused');
                $GLOBALS['embed']->setColor('0xffff00');
                $GLOBALS['embed']->setDescription("Tidak ada yang berhasil menjawab. Jawaban:\n```$this->jawaban```\n" . setupMinigamesDescription($this->players));
            } else {
                $GLOBALS['embed']->setDescription(setupMinigamesDescription($this->players));
            }

            $ronde = 'selanjutnya';
        }

        $GLOBALS['embed']->setFooter("Ronde $ronde akan dimulai dalam 5 detik");
        $GLOBALS['embed']->setTimestamp();
        $getReadyBuilder = MessageBuilder::new();
        $getReadyBuilder->addEmbed($GLOBALS['embed']);

        if ($this->winnerId) $getReadyBuilder->setContent("<@$this->winnerId> Menjawab dengan benar!");

        $this->message->channel->sendMessage($getReadyBuilder)->done(function ($getReadyMessage) use ($totalRonde) {
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

            $this->message->channel->sendMessage(MessageBuilder::new()->addEmbed($qEmbed))->done(function ($msg) use ($getReadyMessage, $selected, $totalRonde) {
                $getReadyMessage->delete();
                $getPlayers = $GLOBALS['conn']->query("SELECT * from " . $GLOBALS['table']);
                $players = mysqli_fetch_all($getPlayers, MYSQLI_ASSOC);
                $playersId = array_column($players, 'player');

                $filter = fn ($message) => (in_array($message->author->id, $playersId) && strtolower($message->content) === strtolower($selected['jawaban'])) || ($message->author->id === $GLOBALS['host'] && strtolower($message->content) === "exit");

                $this->message->channel->createMessageCollector($filter, [
                    'time' => 60000,
                    'limit' => 1
                ])->done(function (Collection $collected) use ($selected, $msg, $totalRonde) {
                    $table = $GLOBALS['table'];
                    $tableRonde = $GLOBALS['tableRonde'];

                    $guild = $this->message->guild;
                    $channel = $this->message->channel;

                    if (!$collected->count()) {
                        $getPlayers = $GLOBALS['conn']->query("SELECT * from $table");
                        $players = mysqli_fetch_all($getPlayers, MYSQLI_ASSOC);
                        $msg->delete();
                        $col = new MessageCollector($this->message, false, $players, null, $selected['jawaban']);
                        $col->init();
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

                        if ($winnerPoin < 1) $playerWinEmbed->setDescription("**Nampaknya permainan seri.**");

                        $winBuilder = MessageBuilder::new();
                        $winBuilder->setEmbeds([$winEmbed, $playerWinEmbed]);

                        $this->message->channel->sendMessage($winBuilder);

                        $GLOBALS['conn']->query("DROP TABLE IF EXISTS $table");
                        $GLOBALS['conn']->query("DELETE FROM $tableRonde WHERE `$tableRonde`.`guild`=$guild->id AND `$tableRonde`.`channel`=$channel->id");
                        return;
                    } else {
                        $author = $collected[0]->author;
                        $GLOBALS['conn']->query("UPDATE $table SET poin = poin + 1 WHERE player = $author->id");
                        $msg->delete();
                        $getPlayers = $GLOBALS['conn']->query("SELECT * from $table");
                        $players = mysqli_fetch_all($getPlayers, MYSQLI_ASSOC);

                        $col = new MessageCollector($this->message, false, $players, $author->id);
                        $col->init();
                        return;
                    }
                });
            });
        });
    }
}