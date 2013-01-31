<?php

namespace Netzhuffle\Quidditch\Spieler;
use Netzhuffle\Quidditch\Quidditch;

class Jaeger extends Spieler
{
    public function hasQuaffel()
    {
        return Quidditch::getInstance()->quaffel->besitzer == $this;
    }
}
