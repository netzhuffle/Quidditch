<?php

namespace Netzhuffle\Quidditch;
use Netzhuffle\Quidditch\Chat\ChatInterface;

class Spiel
{
    public $runde;
    public $schiedsrichter;
    public $team1;
    public $team2;
    public $klatscher1;
    public $klatscher2;
    public $quaffel;
    public $schnatz;
    public $feldernamen;
    public $chat;

    public function __construct(ChatInterface $chat)
    {
    	$this->chat = $chat;
        $this->runde = 0;
        $this->feldernamen = array("T", "M", "H");
        $this->klatscher1 = new Ball\Klatscher();
        $this->klatscher2 = new Ball\Klatscher();
        $this->quaffel = new Ball\Quaffel();
        $this->schnatz = new Ball\Schnatz();
    }

    public function getAllSpieler()
    {
        $array = array();
        $array[] = $this->schiedsrichter;
        if ($this->team1) {
            $array[] = $this->team1->jaeger1;
            $array[] = $this->team1->jaeger2;
            $array[] = $this->team1->jaeger3;
            $array[] = $this->team1->hueter;
            $array[] = $this->team1->treiber1;
            $array[] = $this->team1->treiber2;
            $array[] = $this->team1->sucher;
        }
        if ($this->team2) {
            $array[] = $this->team2->jaeger1;
            $array[] = $this->team2->jaeger2;
            $array[] = $this->team2->jaeger3;
            $array[] = $this->team2->hueter;
            $array[] = $this->team2->treiber1;
            $array[] = $this->team2->treiber2;
            $array[] = $this->team2->sucher;
        }

        return $array;
    }

    public function getSpieler($name)
    {
        foreach ($this->getAllSpieler() as $spieler) {
            if ($spieler->name == trim($name)) {
                return $spieler;
            }
        }

        return null;
    }

    public function getSpielerInDrittel($drittel)
    {
        $spieler = $this->getAllSpieler();
        $drittelSpieler = array();
        foreach ($spieler as $s) {
            if (!($s instanceof Spieler\Schiedsrichter) && $s->feld == $drittel) {
                $drittelSpieler[] = $s;
            }
        }

        return $drittelSpieler;
    }
}
