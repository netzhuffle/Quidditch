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
        if ($name instanceof Spieler) {
            return $name;
        } else {
            return Quidditch::getInstance()->getSpieler($name);
        }
    }

    public function execute()
    {
        $quidditch = Quidditch::getInstance();
        if ($this->befehl != "Write" && $this->befehl != "Dice" && isset($this->wer->team) && $this->wer->team->isComputer) {
            $write = new self($this->wer->name, "Write", $this->befehl . " " . $this->param);
            $write->execute();
        }
        if ($this->wer->canDoCommand($this)) {
            $this->wer->doCommand($this);
            foreach ($quidditch->getAllSpieler() as $spieler) {
                if ($spieler->name != $this->wer->name) {
                    $spieler->react($this);
                }
            }
            $this->wer->lastCommand = $this;
        } elseif ($this->befehl == "Dice") {
            $this->wer->doCommand($this);
        } else { // keine Berechtigung für Befehl
            $fehler = new Befehl($quidditch->schiedsrichter, "Write", "(Befehl $this->befehl " . (isset($this->param) ? $this->param : "") . " von {$this->wer->name} momentan nicht erlaubt …)");
            $fehler->execute();
        }
    }
}
