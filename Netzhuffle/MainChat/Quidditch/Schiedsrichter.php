<?php

namespace Netzhuffle\MainChat\Quidditch;

class Schiedsrichter extends Spieler
{
    public $die1feld;
    public $die2feld;
    private $waiting = array();

    protected function act($befehl)
    {
        parent::act($befehl);

        $param = $befehl->param;
        $befehl = $befehl->befehl;

        $quidditch = Quidditch::getInstance();
        $team1 = $quidditch->team1;
        $team2 = $quidditch->team2;
        $t1 = $team1->name;
        $t2 = $team2->name;
        $k1 = $team1->kapitaen;
        $k2 = $team2->kapitaen;
        $qs = $quidditch->quaffelSpieler;

        if ($befehl == "Runde") {
            $quidditch->runde++;
            $this->write("Chatende");
            if ($param == 1) {
                $this->delay(1, "Write", "Kleiner Hinweis: Diese Simulation unterstützt noch keine Erfolgspunkte und nimmt an, dass die Sucher Mannschaftskapitäne sind.");
                $this->delay(2, "Write", "Runde 1 - $t1 gegen $t2");
                $this->delay(3, "Quaffeldice");
            } else {
                $this->delay(1, "Write", "Runde $param, $qs->team->name hat den Quaffel.");
                $this->delay(2, "Positionjäger");
            }

        } elseif ($befehl == "Quaffeldice") {
            $this->write("Kapitäne würfeln bitte um den Quaffel");
            $k1->setCommands(array("Dice" => null));
            $k2->setCommands(array("Dice" => null));
            $this->waiting = array($k1->name, $k2->name);

        } elseif ($befehl == "Tordrittel") {
            $kapitaen = $qs->team->gegner->kapitaen;
            $kapitaen->setCommands(array($quidditch->feldernamen[0] => null, $quidditch->feldernamen[2] => null));
            $this->waiting = array($kapitaen->name);
            $this->write("Wo soll euer Tor sein, $kapitaen->name?");

        } elseif ($befehl == "Quaffeljäger") {
            $qt = $qs->team->name;
            $kapitaen = $qs->team->kapitaen;
            $kapitaen->setCommands(array($qt."Jäger1" => null, $qt."Jäger2" => null, $qt."Jäger3" => null));
            $this->waiting = array($kapitaen->name);
            $this->write("$kapitaen->name, welcher Jäger in deinem Team soll den Quaffel bekommen (Name)?");

        } elseif ($befehl == "Positionjäger") {
            $jaeger = array($team1->jaeger1, $team1->jaeger2, $team1->jaeger3, $team2->jaeger1, $team2->jaeger2, $team2->jaeger3);
            $felder = array($quidditch->feldernamen[0] => null, $quidditch->feldernamen[1] => null, $quidditch->feldernamen[2] => null);
            foreach ($jaeger as $spieler) {
                if ($qs->name != $spieler->name) {
                    $spieler->setCommands($felder);
                    $this->waiting[] = $spieler->name;
                }
            }
            $this->write("Position Jäger");

        } elseif ($befehl == "Positionsucher") {
            $this->waiting = array($team1->sucher->name, $team2->sucher->name);
            $felder = array($quidditch->feldernamen[0] => null, $quidditch->feldernamen[2] => null);
            $team1->sucher->setCommands($felder);
            $team2->sucher->setCommands($felder);
            $this->write("Position Sucher");

        } elseif ($befehl == "Positiontreiber") {
            $this->waiting = array($team1->treiber1->name, $team1->treiber2->name, $team2->treiber1->name, $team2->treiber2->name);
            $felder = array($quidditch->feldernamen[0] => null, $quidditch->feldernamen[1] => null, $quidditch->feldernamen[2] => null);
            $team1->treiber1->setCommands($felder);
            $team1->treiber2->setCommands($felder);
            $team2->treiber1->setCommands($felder);
            $team2->treiber2->setCommands($felder);
            $this->write("Position Treiber");

        } elseif ($befehl == "Positionklatscher") {
            $this->write("Klatscher sind in");
            $this->delay(1, "Dice");
            $this->delay(2, "Positionklatscherdice");

        } elseif ($befehl == "Positionklatscherdice") {
            $this->write($quidditch->feldernamen[$this->die1feld] . " und " . $quidditch->feldernamen[$this->die2feld]);
            $hasSpielerInDrittel = $this->setTreiberCommands();
            if (!$hasSpielerInDrittel) {
                $this->delay(3, "Write", "Da sind keine Treiber");
                $this->delay(6, "Positionquaffel");
            } else {
                $this->delay(3, "Write", "*Klatscher freigeb*");
                $this->delay(3, "Klatscherfreigeb");
            }

        } elseif ($befehl == "Positionquaffel") {
            $this->write("The End.");

        } elseif ($befehl == "Waitingempty") {
            if ($this->lastcommand->befehl == "Quaffeldice") {
                $kampfwurfGewinner = $this->getKampfwurfGewinner($k1, $k2);
                if ($kampfwurfGewinner) {
                    $team = $kampfwurfGewinner->team;
                    $quidditch->quaffelSpieler = $team->jaeger1;
                    $this->write("$team->name hat den Quaffel.");
                    $this->delay(1, "Tordrittel");
                } else {
                    $this->delay(1, "Quaffeldice");
                }

            } elseif ($this->lastcommand->befehl == "Tordrittel") {
                $qt = $qs->team; // QuaffelTeam
                $qg = $qt->gegner; // QuaffelGegner
                $tor = $qg->kapitaen->feld; // welches Feld für qg; als boolean gesehen: feld2 für qg, feld0 für qt
                $quidditch->feldernamen[0] = $tor ? $qt->name : $qg->name;
                $quidditch->feldernamen[2] = $tor ? $qg->name : $qt->name;
                $feld0 = $quidditch->feldernamen[0];
                $feld2 = $quidditch->feldernamen[2];
                $this->write("T = $feld0, H = $feld2");
                $this->delay(1, "Quaffeljäger");

            } elseif ($this->lastcommand->befehl == "Quaffeljäger") {
                $this->delay(1, "Positionjäger");

            } elseif ($this->lastcommand->befehl == "Positionjäger") {
                $this->delay(1, "Positionsucher");

            } elseif ($this->lastcommand->befehl == "Positionsucher") {
                $this->delay(1, "Positiontreiber");

            } elseif ($this->lastcommand->befehl == "Positiontreiber") {
                $this->delay(1, "Positionklatscher");
            }
        }
    }

    public function react($befehl)
    {
        parent::react($befehl);
        $quidditch = Quidditch::getInstance();
        if ($befehl->befehl != "Write" && ($pos = array_search($befehl->wer->name, $this->waiting)) !== false) {
            unset($this->waiting[$pos]);
            if (!count($this->waiting)) {
                $this->delay(2, "Waitingempty");
            }
            if ($this->lastcommand->befehl == "Quaffeljäger") {
                $quidditch->quaffelSpieler = $quidditch->getSpieler($befehl->befehl);
            }

        } elseif ($befehl->befehl == "Klatscherwurf") {
            if ($this->die1feld == $befehl->wer->feld) {
                $this->die1feld = null;
            } elseif ($this->die2feld == $befehl->wer->feld) {
                $this->die2feld = null;
            }
            $this->setTreiberCommands();
            $befehl->wer->setCommands(array("Dice" => null));

        } elseif ($befehl->befehl == "Dice") {
            if ($befehl->wer->lastcommand->befehl == "Klatscherwurf" || $befehl->wer->lastcommand->befehl == "Klatscherabwurf") {
                if ($befehl->wer->erfolgswurf >= 4) {
                    $quidditch->getSpieler($befehl->wer->lastcommand->param)->isOut = true;
                    $this->write($befehl->wer->lastcommand->param . " is out!");
                }
            }
            $hasSpielerInDrittel = $this->setTreiberCommands();
            if (!$hasSpielerInDrittel) {
                $this->delay(6, "Positionquaffel");
            }
        }
    }

    protected function dice()
    {
        parent::dice();
        $die1 = $this->erfolgswurf;
        $die2 = $this->die2;

        $this->die1feld = floor(($die1-1)/2);
        $this->die2feld = floor(($die2-1)/2);
    }

    public function canDoCommand($befehl)
    {
        return true;
    }

    private function getKampfwurfGewinner($spieler1, $spieler2)
    {
        if ($spieler1->kampfwurf > $spieler2->kampfwurf) {
            return $spieler1;
        } elseif ($spieler1->kampfwurf < $spieler2->kampfwurf) {
            return $spieler2;
        } elseif ($spieler1->erfolgswurf > $spieler2->erfolgswurf) {
            return $spieler1;
        } elseif ($spieler1->erfolgswurf < $spieler2->erfolgswurf) {
            return $spieler2;
        } elseif ($spieler1->die2 > $spieler2->die2) {
            return $spieler1;
        } elseif ($spieler1->die2 < $spieler2->die2) {
            return $spieler2;
        } else {
            return null;
        }
    }

    /**
     * @return boolean Is any Treiber able to do something?
     */
    private function setTreiberCommands()
    {
        $quidditch = Quidditch::getInstance();
        $hasSpielerInDrittel = false;
        for ($i = 0; $i <= 2; $i++) {
            $drittelSpieler = $quidditch->getSpielerInDrittel($i);
            foreach ($drittelSpieler as $spieler) {
                if ($spieler instanceof Treiber && ($spieler->feld == $this->die1feld || $spieler->feld == $this->die2feld)) {
                    $opfer = array();
                    foreach ($drittelSpieler as $anderer) {
                        if (!($anderer instanceof Treiber)) {
                            $hasSpielerInDrittel = true;
                            $opfer[] = $anderer->name;
                        }
                    }
                    $spieler->setCommands(array("Klatscherwurf" => $opfer));
                }
            }
        }

        return $hasSpielerInDrittel;
    }

}
