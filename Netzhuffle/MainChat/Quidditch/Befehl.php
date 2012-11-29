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
        if ($this->wer) {
            $quidditch = Quidditch::getInstance();
            if ($this->wer->canDoCommand($this)) {
                $this->wer->doCommand($this);
                foreach ($quidditch->getAllSpieler() as $spieler) {
                    if ($spieler->name != $this->wer->name) {
                        $spieler->react($this);
                    }
                }
            } else { // keine Berechtigung für Befehl
                $fehler = new Befehl($quidditch->schiedsrichter, "Write", "(Befehl $this->befehl von {$this->wer->name} momentan nicht erlaubt..)");
                $quidditch->addStackItem($fehler, 0);
            }
        }
    }
}
