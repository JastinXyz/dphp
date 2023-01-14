<?php

include __DIR__ . '/vendor/autoload.php';
include __DIR__ . '/koneksi.php';

require __DIR__ . '/utils/functions.php';

use Bot\Commands\Ping;
use Bot\Commands\TebakGambar;
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

$commands = [
    'ping' => new Ping($discord, $conn),
    'tbk' => new TebakGambar($discord, $conn)
];

$discord->on('ready', function (Discord $discord) use ($prefix, $commands) {
    echo "Bot is ready!", PHP_EOL;

    // Listen for messages.
    $discord->on(Event::MESSAGE_CREATE, function (Message $message) use ($prefix, $commands) {
        if ($message->author->bot) return;

        if(str_starts_with($message->content, $prefix)) {
            $args = explode(" ", substr($message->content, strlen($prefix)));
            $command = strtolower(array_shift($args));

            $commands[$command]->execute($message);
        }
    });
});

$discord->run();
