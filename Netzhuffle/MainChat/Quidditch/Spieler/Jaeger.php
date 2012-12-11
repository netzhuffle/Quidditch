<?php

namespace Netzhuffle\MainChat\Quidditch\Spieler;

use Netzhuffle\MainChat\Quidditch\Quidditch;

class Jaeger extends Spieler
{
    protected function act($befehl)
    {
        parent::act($befehl);
    }

    public function react($befehl)
    {
        parent::react($befehl);
    }

    protected function hasQuaffel()
    {
        return Quidditch::getInstance()->quaffel->besitzer->name == $this->name;
    }
}