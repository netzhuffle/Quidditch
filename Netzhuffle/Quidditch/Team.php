<?php

namespace Netzhuffle\Quidditch;

class Team
{
    public $name;
    public $punkte = 0;
    public $kapitaen;
    public $jaeger1;
    public $jaeger2;
    public $jaeger3;
    public $hueter;
    public $treiber1;
    public $treiber2;
    public $sucher;
    public $gegner;
    public $isComputer = false;
    public $farbe = "FFFFFF";
    
    public function __construct($name)
    {
        $this->name = $name;
        if ($name == "C" || $name == "X") {
            $this->isComputer = true;
            $this->jaeger1 = new Spieler\JaegerCP($name . "Jäger1", $this);
            $this->jaeger2 = new Spieler\JaegerCP($name . "Jäger2", $this);
            $this->jaeger3 = new Spieler\JaegerCP($name . "Jäger3", $this);
            $this->hueter = new Spieler\HueterCP($name . "Hüter", $this);
            $this->treiber1 = new Spieler\TreiberCP($name . "Treiber1", $this);
            $this->treiber2 = new Spieler\TreiberCP($name . "Treiber2", $this);
            $this->sucher = new Spieler\SucherCP($name . "Sucher", $this);
        } else {
            $this->jaeger1 = new Spieler\Jaeger($name . "Jäger1", $this);
            $this->jaeger2 = new Spieler\Jaeger($name . "Jäger2", $this);
            $this->jaeger3 = new Spieler\Jaeger($name . "Jäger3", $this);
            $this->hueter = new Spieler\Hueter($name . "Hüter", $this);
            $this->treiber1 = new Spieler\Treiber($name . "Treiber1", $this);
            $this->treiber2 = new Spieler\Treiber($name . "Treiber2", $this);
            $this->sucher = new Spieler\Sucher($name . "Sucher", $this);
        }
        $this->kapitaen = $this->sucher;
    }
    
    public function setGegner($gegner)
    {
        $this->gegner = $gegner;
    }
}
