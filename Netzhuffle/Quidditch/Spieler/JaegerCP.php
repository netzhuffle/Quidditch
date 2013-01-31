<?php

namespace Netzhuffle\Quidditch\Spieler;
use Netzhuffle\Quidditch\Quidditch;

class JaegerCP extends Jaeger
{
    public function reactPositionjäger($befehl)
    {
        if (!$this->hasQuaffel()) {
            $feld = mt_rand(0, 2);
            $delay = 1;//mt_rand(2, 15); // XXX
            $this->delay($delay, Quidditch::getInstance()->feldernamen[$feld]);
        }
    }
}
