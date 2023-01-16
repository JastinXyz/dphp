<?php

include __DIR__ . '/vendor/autoload.php';
include __DIR__ . '/koneksi.php';

require __DIR__ . '/utils/functions.php';

use Bot\Commands\Command;
use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\WebSockets\Intents;
use Discord\WebSockets\Event;
use Monolog\Logger;
use Monolog\Handler\NullHandler;
use Noodlehaus\Config;

$conf = new Config('config.json');
$prefix = $conf['prefix'];
$logger = new Logger('Logger');
$logger->pushHandler(new NullHandler());
$discord = new Discord([
    'logger' => $logger,
    'token' => $conf['token'],
    'intents' => Intents::getDefaultIntents() | Intents::MESSAGE_CONTENT
]);

$discord->on('ready', function (Discord $discord) use ($prefix, $conn) {
    echo "Bot is ready!", PHP_EOL;

    $commandInit = new Command($discord, $conn);
    $commands = $commandInit->getAllCommands();

    // Listen for messages.
    $discord->on(Event::MESSAGE_CREATE, function (Message $message) use ($prefix, $commandInit, $commands) {
        if ($message->author->bot) return;

        if(str_starts_with($message->content, $prefix)) {
            $args = explode(" ", substr($message->content, strlen($prefix)));
            $command = strtolower(array_shift($args));

            $commandData = $commandInit->getCommandData($command);
            if(!$commandData) return;

            $commandData[0]->execute($message, $args, $commands);
        }
    });
});

$discord->run();
