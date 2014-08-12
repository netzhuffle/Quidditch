<?php

namespace Netzhuffle\Quidditch\Spieler;

class Jaeger extends Spieler
{
    public function hasQuaffel()
    {
        return $this->spiel->quaffel->besitzer == $this;
    }
}
