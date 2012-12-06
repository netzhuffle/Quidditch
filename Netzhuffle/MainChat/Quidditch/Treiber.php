<?php

namespace Netzhuffle\MainChat\Quidditch;

class Treiber extends Spieler
{
    private $didKlatscherwurf;
    private $hasKlatscher;

    protected function act($befehl)
    {
        parent::act($befehl);
        if ($befehl->befehl == "Klatscherwurf") {
            $this->didKlatscherwurf = true;
        } elseif ($befehl->befehl == "Dice" && $this->lastCommand->befehl == "Klatscherabfang" && $this->erfolgswurf >= 4) {
            $this->hasKlatscher = true;
        }
    }

    public function react($befehl)
    {
        parent::react($befehl);
        if ($befehl->befehl == "Klatscherfreigeb") {
            $this->didKlatscherwurf = false;
            $this->hasKlatscher = false;
        }
    }

    public function hasDoneKlatscherwurf()
    {
        return $this->didKlatscherwurf;
    }

    public function hasKlatscher()
    {
        return $this->hasKlatscher;
    }
}
