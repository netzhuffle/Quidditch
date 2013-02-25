<?php

namespace Netzhuffle\Quidditch;

class Befehl
{
    public $wer;
    public $befehl;
    public $param;
    private $quidditch;

    public function __construct($wer, $befehl, $param, Quidditch $quidditch)
    {
        $this->wer = $this->getSpieler($wer);
        $this->befehl = $befehl;
        $this->param = $param;
        $this->quidditch = $quidditch;
    }

    public function execute()
    {
        if ($this->wer->canDoCommand($this)) {
            if ($this->befehl != "Write" && $this->befehl != "WriteNotAllowed" && $this->befehl != "Dice" && isset($this->wer->team) && $this->wer->team->isComputer) {
                $write = new self($this->wer->name, "Write", $this->befehl . " " . $this->param);
                $write->execute();
            }
            if (method_exists($this->wer, "act" . $this->befehl)) {
                call_user_func(array($this->wer, "act" . $this->befehl), $this);
            }
            $this->wer->doCommand($this);
            foreach ($this->quidditch->getAllSpieler() as $spieler) {
                if ($spieler->name != $this->wer->name) {
                    if (method_exists($spieler, "react" . $this->befehl)) {
                        call_user_func(array($spieler, "react" . $this->befehl), $this);
                    }
                }
            }
            if ($this->befehl != "Write") {
                $this->wer->lastCommand = $this;
            }
        } else { // keine Berechtigung für Befehl
            if ($this->befehl == "Dice") {
                $this->wer->actDice($this); // trotzdem würfeln
            } elseif ($this->befehl != "Write" && isset($this->wer->team) && $this->wer->team->isComputer) {
                $write = new self($this->wer->name, "WriteNotAllowed", $this->befehl . " " . $this->param);
                $write->execute();
            }
        }
    }
}
