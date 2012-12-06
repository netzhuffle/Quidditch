<?php

namespace Netzhuffle\MainChat\Quidditch;

class TreiberCP extends Treiber
{
    protected function act($befehl)
    {
        parent::act($befehl);
    }

    public function react($befehl)
    {
        parent::react($befehl);
        $quidditch = Quidditch::getInstance();
        if ($befehl->befehl == "Positiontreiber") {
            $feld = mt_rand(0, 2);
            $delay = mt_rand(2, 15);
            $this->delay($delay, $quidditch->feldernamen[$feld]);

        } elseif ($befehl->befehl == "Klatscherfreigeb") {
            if ($this->feld == $quidditch->schiedsrichter->die1feld || $this->feld == $quidditch->schiedsrichter->die2feld) {
                $drittelSpieler = $quidditch->getSpielerInDrittel($this->feld);
                $gegner = array();
                foreach ($drittelSpieler as $spieler) {
                    if ($spieler->team->name != $this->team->name && !($spieler instanceof Treiber)) {
                        $gegner[] = $spieler;
                    }
                }
                if (count($gegner)) {
                    $opfer = 0;
                    if (count($gegner) > 1) {
                        $opfer = mt_rand(0, count($gegner) - 1);
                    }
                    $spieler = $gegner[$opfer];
                    $this->delay(1, "Klatscherwurf", $spieler->name);
                    $this->delay(2, "Dice");
                }
            }
        }
    }
}
