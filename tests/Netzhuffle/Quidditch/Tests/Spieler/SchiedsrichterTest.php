<?php

namespace Netzhuffle\Tests\Quidditch\Spieler;
use Netzhuffle\Quidditch\Spiel;
use Netzhuffle\Quidditch\Team;
use Netzhuffle\Quidditch\Spieler\Schiedsrichter;
use Netzhuffle\Quidditch\Spieler\TreiberCP;
use Netzhuffle\Quidditch\Ball\Klatscher;
use Netzhuffle\Quidditch\Spieler\Jaeger;
use Netzhuffle\Quidditch\Spieler\Sucher;
use Netzhuffle\Quidditch\Befehl;
use Netzhuffle\Quidditch\Chat\ArrayChat;

class SchiedsrichterTest extends \PHPUnit_Framework_TestCase
{
    protected $quidditch;
    protected $schiedsrichter;
    
    protected function setUp()
    {
        $this->spiel = new Spiel(new ArrayChat());
        $this->spiel->team1 = new Team("X", $this->spiel);
        $this->spiel->team2 = new Team("C", $this->spiel);
        $this->spiel->schiedsrichter = new Schiedsrichter('aSchiedsrichter', $this->spiel);
        $this->schiedsrichter = $this->spiel->schiedsrichter;
    }
}
