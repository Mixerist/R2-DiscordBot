<?php

use App\Handlers\Balance;
use App\Handlers\Gift;
use App\Handlers\SecretCard;
use App\RegisterCommand;
use Discord\Discord;
use Discord\Exceptions\IntentException;
use Discord\Parts\Interactions\Interaction;

require_once __DIR__ . '/vendor/autoload.php';

try {
    $discord = new Discord([
        'token' => config()['token']
    ]);
} catch (IntentException $e) {
    exit($e->getMessage());
}

$pdo = new PDO(
    'sqlsrv:Server=' . config()['server_ip'] . ';Database=' . config()['database'],
    config()['username'],
    config()['password']
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$discord->on('ready', function (Discord $discord) use ($pdo) {

    /* Регистрируем команды */
    foreach (get_class_methods(RegisterCommand::class) as $method) {
        $discord->application->commands->save(RegisterCommand::$method($discord));
    }

    /* Список команд для прослушивания */
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
