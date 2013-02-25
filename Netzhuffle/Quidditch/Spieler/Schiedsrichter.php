<?php

namespace Netzhuffle\Quidditch\Spieler;
use Netzhuffle\Quidditch\Quidditch;

class Schiedsrichter extends Spieler
{
    private $die1feld;
    private $die2feld;
    private $waiting = array();

    public function __construct($name, Quidditch $quidditch)
    {
        parent::__construct($name, null, $quidditch);
    }

    public function actDice($befehl)
    {
        parent::actDice($befehl);
        $die1 = $this->erfolgswurf;
        $die2 = $this->die2;

        $this->die1feld = intval(floor(($die1 - 1) / 2));
        $this->die2feld = intval(floor(($die2 - 1) / 2));
    }

    public function actRunde($befehl)
    {
        $this->quidditch->runde++;
        $this->write("Chatende");
        if ($befehl->param == 1) {
            $this->delay(1, "Write", "Kleiner Hinweis: Diese Simulation unterstützt noch keine Erfolgspunkte und nimmt an, dass die Sucher Mannschaftskapitäne sind.");
            $this->delay(2, "Write", "Runde 1 - $t1 gegen $t2");
            $this->delay(3, "Quaffeldice");
        } else {
            $this->delay(1, "Write", "Runde $param, $qb->team->name hat den Quaffel.");
            $this->delay(2, "Positionjäger");
        }
        $spieler = $this->quidditch->getAllSpieler();
        foreach ($spieler as $s) {
            $s->isOut = false;
        }
        $this->quidditch->quaffel->besitzer = null;
        $this->quidditch->klatscher1->besitzer = null;
        $this->quidditch->klatscher2->besitzer = null;
        $this->quidditch->klatscher1->used = false;
        $this->quidditch->klatscher2->used = false;
        $this->quidditch->klatscher1->opfer = null;
        $this->quidditch->klatscher2->opfer = null;
        $this->quidditch->klatscher1->hitTime = null;
        $this->quidditch->klatscher2->hitTime = null;
        $this->quidditch->klatscher1->hitBy = null;
        $this->quidditch->klatscher2->hitBy = null;
        $this->quidditch->klatscher1->blocked = false;
        $this->quidditch->klatscher2->blocked = false;
    }

    public function actQuaffeldice($befehl)
    {
        $this->write("Kapitäne würfeln bitte um den Quaffel");
        $k1->setCommands(array("Dice" => null));
        $k2->setCommands(array("Dice" => null));
        $this->waiting = array($k1->name, $k2->name);
    }

    public function actTordrittel($befehl)
    {
        $qb = $this->quidditch->quaffel->besitzer;
        $kapitaen = $qb->team->gegner->kapitaen;
        $kapitaen->setCommands(array($this->quidditch->feldernamen[0] => null, $this->quidditch->feldernamen[2] => null));
        $this->waiting = array($kapitaen->name);
        $this->write("Wo soll euer Tor sein, $kapitaen->name?");
    }

    public function actQuaffeljäger($befehl)
    {
        $qb = $this->quidditch->quaffel->besitzer;
        $qt = $qb->team->name;
        $kapitaen = $qb->team->kapitaen;
        $kapitaen->setCommands(array($qt . "Jäger1" => null, $qt . "Jäger2" => null, $qt . "Jäger3" => null));
        $this->waiting = array($kapitaen->name);
        $this->write("$kapitaen->name, welcher Jäger in deinem Team soll den Quaffel bekommen (Name)?");
    }

    public function actPositionjäger($befehl)
    {
        $team1 = $this->quidditch->team1;
        $team2 = $this->quidditch->team2;
        $jaeger = array($team1->jaeger1, $team1->jaeger2, $team1->jaeger3, $team2->jaeger1, $team2->jaeger2, $team2->jaeger3);
        $felder = array($this->quidditch->feldernamen[0] => null, $this->quidditch->feldernamen[1] => null, $this->quidditch->feldernamen[2] => null);
        foreach ($jaeger as $spieler) {
            if ($qb->name != $spieler->name) {
                $spieler->setCommands($felder);
                $this->waiting[] = $spieler->name;
            }
        }
        $this->write("Position Jäger");
    }

    public function actPositionsucher($befehl)
    {
        $team1 = $this->quidditch->team1;
        $team2 = $this->quidditch->team2;
        $this->waiting = array($team1->sucher->name, $team2->sucher->name);
        $felder = array($this->quidditch->feldernamen[0] => null, $this->quidditch->feldernamen[2] => null);
        $team1->sucher->setCommands($felder);
        $team2->sucher->setCommands($felder);
        $this->write("Position Sucher");
    }

    public function actPositiontreiber($befehl)
    {
        $team1 = $quidditch->team1;
        $team2 = $quidditch->team2;
        $this->waiting = array($team1->treiber1->name, $team1->treiber2->name, $team2->treiber1->name, $team2->treiber2->name);
        $felder = array($this->quidditch->feldernamen[0] => null, $this->quidditch->feldernamen[1] => null, $this->quidditch->feldernamen[2] => null);
        $team1->treiber1->setCommands($felder);
        $team1->treiber2->setCommands($felder);
        $team2->treiber1->setCommands($felder);
        $team2->treiber2->setCommands($felder);
        $this->write("Position Treiber");
    }

    public function actPositionklatscher($befehl)
    {
        $this->write("Klatscher sind in");
        $this->delay(1, "Dice");
        $this->delay(2, "Positionklatscherdice");
    }

    public function actPositionklatscherdice($befehl)
    {
        $this->quidditch->klatscher1->feld = $this->die1feld;
        $this->quidditch->klatscher2->feld = $this->die2feld;
        $this->write($this->quidditch->feldernamen[$this->die1feld] . " und " . $this->quidditch->feldernamen[$this->die2feld]);
        $end = $this->setTreiberCommands();
        if ($end) {
            $this->delay(3, "Write", "Da sind keine Treiber (oder keine Opfer)");
            $this->delay(6, "Positionquaffel");
        } else {
            $this->delay(3, "Write", "*Klatscher freigeb*");
            $this->delay(3, "Klatscherfreigeb");
        }
    }

    public function actTreiberende($befehl)
    {
        $this->write("Treiberende");
    }

    public function actPositionquaffel($befehl)
    {
        $this->write("The End.");
    }

    public function actWaitingempty($befehl)
    {
        $qb = $this->quidditch->quaffel->besitzer;
        if ($this->lastCommand->befehl == "Quaffeldice") {
            $kampfwurfGewinner = $this->getKampfwurfGewinner($k1, $k2);
            if ($kampfwurfGewinner) {
                $team = $kampfwurfGewinner->team;
                $this->quidditch->quaffel->besitzer = $team->jaeger1;
                $this->write("$team->name hat den Quaffel.");
                $this->delay(1, "Tordrittel");
            } else {
                $this->delay(1, "Quaffeldice");
            }

        } elseif ($this->lastCommand->befehl == "Tordrittel") {
            $qt = $qb->team; // QuaffelTeam
            $qg = $qt->gegner; // QuaffelGegner
            $tor = $qg->kapitaen->feld; // welches Feld für qg; als boolean gesehen: feld2 für qg, feld0 für qt
            $this->quidditch->feldernamen[0] = $tor ? $qt->name : $qg->name;
            $this->quidditch->feldernamen[2] = $tor ? $qg->name : $qt->name;
            $feld0 = $this->quidditch->feldernamen[0];
            $feld2 = $this->quidditch->feldernamen[2];
            $this->write("T = $feld0, H = $feld2");
            $this->delay(1, "Quaffeljäger");

        } elseif ($this->lastCommand->befehl == "Quaffeljäger") {
            $this->delay(1, "Positionjäger");

        } elseif ($this->lastCommand->befehl == "Positionjäger") {
            $this->delay(1, "Positionsucher");

        } elseif ($this->lastCommand->befehl == "Positionsucher") {
            $this->delay(1, "Positiontreiber");

        } elseif ($this->lastCommand->befehl == "Positiontreiber") {
            $this->delay(1, "Positionklatscher");
        }
    }

    public function react($befehl) // TODO refactor each waiting command to react…()
    {
        parent::react($befehl);
        if ($befehl->befehl != "Write" && ($pos = array_search($befehl->wer->name, $this->waiting)) !== false) {
            unset($this->waiting[$pos]);
            if (!count($this->waiting)) {
                $this->delay(2, "Waitingempty");
            }
            if ($this->lastCommand->befehl == "Quaffeljäger") {
                $this->quidditch->quaffel->besitzer = $quidditch->getSpieler($befehl->befehl);
            }
        }
    }

    public function reactKlatscherwurf($befehl)
    {
        if ($befehl->wer->feld == $this->quidditch->klatscher1->feld && !$this->quidditch->klatscher1->used) {
            $klatscher = $this->quidditch->klatscher1;
        } elseif ($befehl->wer->feld == $this->quidditch->klatscher2->feld) {
            $klatscher = $this->quidditch->klatscher2;
        }
        $klatscher->used = true;
        $klatscher->hitBy = $befehl->wer;
        $klatscher->opfer = $this->quidditch->getSpieler($befehl->param);
        $befehl->wer->lastHitKlatscher = $klatscher;
        $this->setTreiberCommands($befehl);
    }

    public function reactKlatscherabwurf($befehl)
    {
        $this->reactKlatscherwurf($befehl);
    }

    public function reactKlatscherabfang($befehl)
    {
        $this->setTreiberCommands($befehl);
    }

    public function reactDiceKlatscherwurf($befehl)
    {
        $treiber = $befehl->wer;
        $klatscher = $treiber->lastHitKlatscher;
        if ($treiber->erfolgswurf >= 4) {
            $klatscher->hitTime = time();
            $klatscher->opfer->isOut = true;
        }
        $end = $this->setTreiberCommands($befehl);
        if ($end) {
            $this->delay(6, "Treiberende");
        }
    }
    public function reactDiceKlatscherabwurf($befehl)
    {
        $this->reactDiceKlatscherabwurf($befehl);
    }

    public function reactDiceKlatscherabfang($befehl)
    {
        if ($befehl->wer->erfolgswurf >= 4) {
            if (!$this->quidditch->klatscher2->used) {
                $this->quidditch->klatscher2->besitzer = $befehl->wer;
            }
        }
        $this->setTreiberCommands();
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
    private function setTreiberCommands($runningCommand = null)
    {
        $end = true;

        for ($i = 0; $i <= 2; $i++) {
            $drittelSpieler = $this->quidditch->getSpielerInDrittel($i);
            foreach ($drittelSpieler as $spieler) {
                if ($spieler instanceof Treiber) {
                    $spielerBefehl = $spieler->lastCommand;
                    if ($runningCommand && $runningCommand->wer->name == $spieler->name) {
                        $spielerBefehl = $runningCommand;
                    }
                    if ($spielerBefehl->befehl == "Klatscherwurf" || $spielerBefehl->befehl == "Klatscherabfang" || $spielerBefehl->befehl == "Klatscherabwurf") {
                        $spieler->setCommands(array("Dice" => null));
                        $end = false;
                    } elseif ($spielerBefehl->befehl == "Dice" && $spieler->hasDoneKlatscherwurf() && $spieler->feld == $quidditch->klatscher2->feld && !$this->quidditch->klatscher2->used) {
                        $spieler->setCommands(array("Klatscherabfang" => null));
                        $end = false;
                    } elseif ($spieler->feld == $this->quidditch->klatscher1->feld && !$this->quidditch->klatscher1->used || $spieler->feld == $this->quidditch->klatscher2->feld && !$this->quidditch->klatscher2->used) {
                        $opfer = array();
                        foreach ($drittelSpieler as $anderer) {
                            if (!($anderer instanceof Treiber)) {
                                if ($anderer->team->name != $spieler->team->name) {
                                    $end = false;
                                }
                                $opfer[] = $anderer->name;
                            }
                        }
                        if ($spieler->hasKlatscher()) {
                            $spieler->setCommands(array("Klatscherabwurf" => $opfer));
                        } else {
                            $spieler->setCommands(array("Klatscherwurf" => $opfer));
                        }
                    } else {
                        $spieler->deleteCommands();
                    }
                }
            }
        }

        return $end;
    }

}
