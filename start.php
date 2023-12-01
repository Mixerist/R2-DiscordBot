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

$discord->on('ready', function (Discord $discord) {

    /* Регистрируем команды */
    foreach (get_class_methods(RegisterCommand::class) as $method) {
        $discord->application->commands->save(RegisterCommand::$method($discord));
    }

    /* Список команд для прослушивания */
    $discord->listenCommand('gift', function (Interaction $interaction) {
        (new Gift($interaction))->run();
    });

    $discord->listenCommand('balance', function (Interaction $interaction) {
        (new Balance($interaction))->run();
    });

    $discord->listenCommand('sc', function (Interaction $interaction) {
        (new SecretCard($interaction))->run();
    });
});

$discord->run();
