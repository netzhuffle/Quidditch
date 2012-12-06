<?php

namespace Netzhuffle\MainChat\Quidditch;

class Treiber extends Spieler
{
    private $didKlatscherwurf;

    protected function act($befehl)
    {
        parent::act($befehl);
        if ($befehl->befehl == "Klatscherwurf") {
            $this->didKlatscherwurf = true;
        }
    }

    public function react($befehl)
    {
        parent::react($befehl);
        if ($befehl->befehl == "Klatscherfreigeb") {
            $this->didKlatscherwurf = false;
        }
    }

    public function hasDoneKlatscherwurf()
    {
        return $this->didKlatscherwurf;
    }

    public function hasKlatscher()
    {
        $quidditch = Quidditch::getInstance();
        $hasKlatscher1 = $quidditch->klatscher1->besitzer && $quidditch->klatscher1->besitzer->name == $this->name;
        $hasKlatscher2 = $quidditch->klatscher2->besitzer && $quidditch->klatscher2->besitzer->name == $this->name;

        return $hasKlatscher1 || $hasKlatscher2;
    }
}
