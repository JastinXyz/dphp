<?php

include __DIR__ . '/vendor/autoload.php';
include __DIR__ . '/koneksi.php';

require __DIR__ . '/utils/functions.php';

use Discord\Discord;
use Discord\WebSockets\Intents;
use Monolog\Logger;
use Monolog\Handler\NullHandler;
use Noodlehaus\Config;

$conf = new Config('config.json');
$logger = new Logger('Logger');
$logger->pushHandler(new NullHandler());
$discord = new Discord([
    'logger' => $logger,
    'token' => $conf['token'],
    'intents' => Intents::getDefaultIntents() | Intents::MESSAGE_CONTENT
]);

$events = new Bot\Events\Init($discord, $conn, $conf);
$events->init();

$discord->run();
