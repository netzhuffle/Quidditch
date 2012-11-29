<?php

namespace Netzhuffle\MainChat\Quidditch;

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
            $this->jaeger1 = new JaegerCP($name."Jäger1", $this);
            $this->jaeger2 = new JaegerCP($name."Jäger2", $this);
            $this->jaeger3 = new JaegerCP($name."Jäger3", $this);
            $this->hueter = new HueterCP($name."Hüter", $this);
            $this->treiber1 = new TreiberCP($name."Treiber1", $this);
            $this->treiber2 = new TreiberCP($name."Treiber2", $this);
            $this->sucher = new SucherCP($name."Sucher", $this);
        } else {
            $this->jaeger1 = new Jaeger($name."Jäger1", $this);
            $this->jaeger2 = new Jaeger($name."Jäger2", $this);
            $this->jaeger3 = new Jaeger($name."Jäger3", $this);
            $this->hueter = new Hueter($name."Hüter", $this);
            $this->treiber1 = new Treiber($name."Treiber1", $this);
            $this->treiber2 = new Treiber($name."Treiber2", $this);
            $this->sucher = new Sucher($name."Sucher", $this);
        }
        $this->kapitaen = $this->sucher;
    }

    public function setGegner($gegner)
    {
        $this->gegner = $gegner;
    }
}
