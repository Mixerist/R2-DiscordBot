<?php

use Discord\Builders\CommandBuilder;
use Discord\Discord;
use Discord\Parts\Interactions\Command\Command;
use Discord\Parts\Interactions\Command\Option;

class RegisterCommand
{
    public static function gift(Discord $discord): Command
    {
        $builder = CommandBuilder::new()
            ->setName('gift')
            ->setDescription('Используйте промокод, чтобы получить подарок.')
            ->setType(Command::CHAT_INPUT);

        $builder->addOption((new Option($discord))
            ->setName('promo_code')
            ->setDescription('Введите промокод.')
            ->setType(Option::STRING)
            ->setRequired(true));

        $builder->addOption((new Option($discord))
            ->setName('login')
            ->setDescription('Логин от аккаунта.')
            ->setType(Option::STRING)
            ->setRequired(true));

        return new Command($discord, $builder->toArray());
    }

    public static function balance(Discord $discord): Command
    {
        $builder = CommandBuilder::new()
            ->setName('balance')
            ->setDescription('Используйте купон, чтобы пополнить баланс аккаунта.')
            ->setType(Command::CHAT_INPUT);

        $builder->addOption((new Option($discord))
            ->setName('coupon')
            ->setDescription('Введите купон.')
            ->setType(Option::STRING)
            ->setRequired(true));

        $builder->addOption((new Option($discord))
            ->setName('login')
            ->setDescription('Логин от аккаунта.')
            ->setType(Option::STRING)
            ->setRequired(true));

        return new Command($discord, $builder->toArray());
    }

    public static function sc(Discord $discord)
    {
        $builder = CommandBuilder::new()
            ->setName('sc')
            ->setDescription('Установить SC-карту на аккаунт.')
            ->setType(Command::CHAT_INPUT);

        $builder->addOption((new Option($discord))
            ->setName('login')
            ->setDescription('Логин от аккаунта.')
            ->setType(Option::STRING)
            ->setRequired(true));

        $builder->addOption((new Option($discord))
            ->setName('password')
            ->setDescription('Пароль.')
            ->setType(Option::STRING)
            ->setRequired(true));

        return new Command($discord, $builder->toArray());
    }
}
