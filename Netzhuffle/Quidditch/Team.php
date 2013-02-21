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
    private $quidditch;

    /**
     * Creates the team
     * @param string    $name      The teams name, one letter. C and X are NPCs, other letters are player teams.
     * @param Quidditch $quidditch An Quidditch instance
     */
    public function __construct($name, Quidditch $quidditch)
    {
        $this->name = $name;
        $this->quidditch = $quidditch;
        if ($name == "C" || $name == "X") {
            $this->isComputer = true;
            $this->jaeger1 = new Spieler\JaegerCP($name . "Jäger1", $this, $quidditch);
            $this->jaeger2 = new Spieler\JaegerCP($name . "Jäger2", $this, $quidditch);
            $this->jaeger3 = new Spieler\JaegerCP($name . "Jäger3", $this, $quidditch);
            $this->hueter = new Spieler\HueterCP($name . "Hüter", $this, $quidditch);
            $this->treiber1 = new Spieler\TreiberCP($name . "Treiber1", $this, $quidditch);
            $this->treiber2 = new Spieler\TreiberCP($name . "Treiber2", $this, $quidditch);
            $this->sucher = new Spieler\SucherCP($name . "Sucher", $this, $quidditch);
        } else {
            $this->jaeger1 = new Spieler\Jaeger($name . "Jäger1", $this, $quidditch);
            $this->jaeger2 = new Spieler\Jaeger($name . "Jäger2", $this, $quidditch);
            $this->jaeger3 = new Spieler\Jaeger($name . "Jäger3", $this, $quidditch);
            $this->hueter = new Spieler\Hueter($name . "Hüter", $this, $quidditch);
            $this->treiber1 = new Spieler\Treiber($name . "Treiber1", $this, $quidditch);
            $this->treiber2 = new Spieler\Treiber($name . "Treiber2", $this, $quidditch);
            $this->sucher = new Spieler\Sucher($name . "Sucher", $this, $quidditch);
        }
        $this->kapitaen = $this->sucher;
    }

    public function setGegner($gegner)
    {
        $this->gegner = $gegner;
    }
}
