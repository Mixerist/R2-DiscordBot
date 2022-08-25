<?php

require_once 'vendor/autoload.php';
require_once 'RegisterCommand.php';
require_once 'Gift.php';
$config = require_once 'config.php';

use Discord\Discord;
use Discord\Parts\Interactions\Interaction;

$discord = new Discord([
    'token' => $config['token']
]);

$pdo = new PDO("sqlsrv:Server={$config['server_ip']};Database={$config['database']}", $config['username'], $config['password']);

$discord->on('ready', function (Discord $discord) use ($pdo) {

    $discord->application->commands->save(RegisterCommand::register($discord));

    $discord->listenCommand('gift', function (Interaction $interaction) use ($pdo) {
        $gift = new Gift($pdo, $interaction);
        $gift->run();
    });
});

$discord->run();
