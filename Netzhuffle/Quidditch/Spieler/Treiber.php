<?php

namespace Netzhuffle\Quidditch\Spieler;
use Netzhuffle\Quidditch\Quidditch;

class Treiber extends Spieler
{
    public $lastHitKlatscher;
    private $didKlatscherwurf;
    
    protected function actKlatscherwurf($befehl)
    {
        $this->didKlatscherwurf = true;
    }
    
    public function reactKlatscherfreigeb($befehl)
    {
        $this->didKlatscherwurf = false;
    }
    
    public function hasDoneKlatscherwurf()
    {
        return $this->didKlatscherwurf;
    }
    
    public function hasKlatscher()
    {
        $quidditch = Quidditch::getInstance();
        $hasKlatscher1 = $this == $quidditch->klatscher1->besitzer;
        $hasKlatscher2 = $this == $quidditch->klatscher2->besitzer;
        
        return $hasKlatscher1 || $hasKlatscher2;
    }
}
