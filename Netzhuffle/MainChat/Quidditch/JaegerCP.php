<?php

namespace Netzhuffle\MainChat\Quidditch;

class JaegerCP extends Jaeger
{
    protected function act($befehl)
    {
        parent::act($befehl);
    }

    public function react($befehl)
    {
        parent::react($befehl);
        $quidditch = Quidditch::getInstance();
        if ($befehl->befehl == "Positionjäger" && !$this->hasQuaffel()) {
            $feld = mt_rand(0, 2);
            $delay = mt_rand(2, 15);
            $this->delay($delay, $quidditch->feldernamen[$feld]);
        }
    }
}
