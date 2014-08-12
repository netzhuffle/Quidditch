<?php
namespace Netzhuffle\Quidditch\Tests;
use Netzhuffle\Quidditch\Spiel;
use Netzhuffle\Quidditch\Team;
use Netzhuffle\Quidditch\Spieler\Schiedsrichter;
use Netzhuffle\Quidditch\Chat\ArrayChat;

class SpielTest extends \PHPUnit_Framework_TestCase
{
    protected $spiel;

    public function setUp()
    {
        $this->spiel = new Spiel(new ArrayChat());
        $this->spiel->team1 = new Team('C', $this->spiel);
        $this->spiel->team2 = new Team('X', $this->spiel);
        $this->spiel->schiedsrichter = new Schiedsrichter('aSchiedsrichter', $this->spiel);
    }

    public function testGetAllSpieler()
    {
        $this->assertCount(15, $this->spiel->getAllSpieler());
    }

    public function testGetSpieler()
    {
        $spieler = $this->spiel->getSpieler('XJäger3');
        $this->assertEquals($this->spiel->team2->jaeger3, $spieler);
    }

    public function testGetSpielerSchiedsrichter()
    {
        $spieler = $this->spiel->getSpieler('aSchiedsrichter');
        $this->assertEquals($this->spiel->schiedsrichter, $spieler);
    }

    public function testGetSpielerUnknown()
    {
        $spieler = $this->spiel->getSpieler('HJäger2');
        $this->assertNull($spieler);
    }

    public function testGetSpielerInDrittel()
    {
        $this->spiel->team1->jaeger2->feld = 1;
        $this->spiel->team1->sucher->feld = 1;
        $this->spiel->team2->treiber1->feld = 1;
        $this->spiel->team1->jaeger3->feld = 0;
        $this->spiel->team2->sucher->feld = 2;
        $this->spiel->team2->jaeger2->feld = 2;

        $this->assertCount(3, $this->spiel->getSpielerInDrittel(1));
    }
}
