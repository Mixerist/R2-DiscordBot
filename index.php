<?php

require_once 'vendor/autoload.php';
require_once 'RegisterCommand.php';
$config = config();

use App\Handlers\Gift;
use Discord\Discord;
use Discord\Exceptions\IntentException;
use Discord\Parts\Interactions\Interaction;

try {
    $discord = new Discord([
        'token' => $config['token']
    ]);
} catch (IntentException $e) {
    exit($e->getMessage());
}

$pdo = new PDO("sqlsrv:Server={$config['server_ip']};Database={$config['database']}", $config['username'], $config['password']);

$discord->on('ready', function (Discord $discord) use ($pdo) {

    $discord->application->commands->save(RegisterCommand::register($discord));

    $discord->listenCommand('gift', function (Interaction $interaction) use ($pdo) {
        (new Gift($pdo, $interaction))->run();
    });
});

$discord->run();
