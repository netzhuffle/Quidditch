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
    public $farbe = "FFFFFF";
    private $spiel;

    /**
     * Creates the team
     * @param string    $name      The teams name, one letter
     * @param Spiel $spiel An Spiel instance
     */
    public function __construct($name, Spiel $spiel)
    {
        $this->name = $name;
        $this->spiel = $spiel;
            $this->jaeger1 = new Spieler\JaegerCP($name . "Jäger1", $this, $spiel);
            $this->jaeger2 = new Spieler\JaegerCP($name . "Jäger2", $this, $spiel);
            $this->jaeger3 = new Spieler\JaegerCP($name . "Jäger3", $this, $spiel);
            $this->hueter = new Spieler\HueterCP($name . "Hüter", $this, $spiel);
            $this->treiber1 = new Spieler\TreiberCP($name . "Treiber1", $this, $spiel);
            $this->treiber2 = new Spieler\TreiberCP($name . "Treiber2", $this, $spiel);
            $this->sucher = new Spieler\SucherCP($name . "Sucher", $this, $spiel);
        $this->kapitaen = $this->sucher;
    }

    public function setGegner($gegner)
    {
        $this->gegner = $gegner;
    }
}
