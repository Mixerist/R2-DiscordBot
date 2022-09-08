<?php

require_once 'vendor/autoload.php';
require_once 'RegisterCommand.php';
$config = config();

use App\Handlers\Balance;
use App\Handlers\Gift;
use App\Handlers\SecretCard;
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
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$discord->on('ready', function (Discord $discord) use ($pdo) {

    $discord->application->commands->save(RegisterCommand::gift($discord));
    $discord->application->commands->save(RegisterCommand::balance($discord));
    $discord->application->commands->save(RegisterCommand::sc($discord));

    $discord->listenCommand('gift', function (Interaction $interaction) use ($pdo) {
        (new Gift($pdo, $interaction))->run();
    });

    $discord->listenCommand('balance', function (Interaction $interaction) use ($pdo) {
        (new Balance($pdo, $interaction))->run();
    });

    $discord->listenCommand('sc', function (Interaction $interaction) use ($pdo) {
        (new SecretCard($pdo, $interaction))->run();
    });
});

$discord->run();
