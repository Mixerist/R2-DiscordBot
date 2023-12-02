<?php

namespace App\Handlers;

use Discord\Parts\Interactions\Interaction;

abstract class AbstractHandler
{
    protected Interaction $interaction;

    public function __construct(Interaction $interaction)
    {
        $this->interaction = $interaction;
    }

    protected function getParam(string $key)
    {
        if ($param = $this->interaction->data->options[$key]['value']) {
            return $param;
        }
    }
}