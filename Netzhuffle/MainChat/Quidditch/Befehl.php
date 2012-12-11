<?php

namespace Netzhuffle\MainChat\Quidditch;

class Befehl
{
    public $wer;
    public $befehl;
    public $param;

    public function __construct($wer, $befehl, $param = null)
    {
        $this->wer = $this->getSpieler($wer);
        $this->befehl = $befehl;
        $this->param = $param;
    }

    private function getSpieler($name)
    {
        if ($name instanceof Spieler\Spieler) {
            return $name;
        } else {
            return Quidditch::getInstance()->getSpieler($name);
        }
    }

    public function execute()
    {
        $quidditch = Quidditch::getInstance();
        if ($this->wer->canDoCommand($this)) {
			if ($this->befehl != "Write" && $this->befehl != "WriteNotAllowed" && $this->befehl != "Dice" && isset($this->wer->team) && $this->wer->team->isComputer) {
				$write = new self($this->wer->name, "Write", $this->befehl . " " . $this->param);
				$write->execute();
			}
            $this->wer->doCommand($this);
            foreach ($quidditch->getAllSpieler() as $spieler) {
                if ($spieler->name != $this->wer->name) {
                    $spieler->react($this);
                }
            }
            if ($this->befehl != "Write") {
                $this->wer->lastCommand = $this;
            }
        } else { // keine Berechtigung für Befehl
            if ($this->befehl == "Dice") {
                $this->wer->doCommand($this); // trotzdem würfeln
            } elseif ($this->befehl != "Write" && isset($this->wer->team) && $this->wer->team->isComputer) {
				$write = new self($this->wer->name, "WriteNotAllowed", $this->befehl . " " . $this->param);
				$write->execute();
			}
        }
    }
}
