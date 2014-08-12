<?php

namespace Netzhuffle\Quidditch\Spieler;
use Netzhuffle\Quidditch\Quidditch;

class TreiberCP extends Treiber
{
    /*
    public function reactPositiontreiber($befehl)
    {
        $feld = mt_rand(0, 2);
        $delay = 1;//mt_rand(2, 15); // XXX
        $this->delay($delay, $this->quidditch->feldernamen[$feld]);

    }

    public function reactKlatscherfreigeb($befehl)
    {
        if ($this->feld == $this->quidditch->klatscher1->feld || $this->feld == $this->quidditch->klatscher2->feld) {
            $drittelSpieler = $this->quidditch->getSpielerInDrittel($this->feld);
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

    private function needsBlock($befehl)
    {
        if ($befehl->wer->erfolgswurf >= 4) {
            $opferTeam = substr($befehl->wer->lastCommand->param, 0, 1);
            if ($opferTeam == $this->team->name) {
                return true;
            }
        }

        return false;
    }

    private function block()
    {
        $this->delay(3, "Abblocken");
        $this->delay(4, "Dice");
    }

    public function reactDiceKlatscherwurf($befehl)
    {
        if ($this->needsBlock($befehl)) {
            $this->block();
        }
    }

    public function reactDiceKlatscherabwurf($befehl)
    {
        if ($this->needsBlock($befehl)) {
            $this->block();
        }
    }
    */
}
