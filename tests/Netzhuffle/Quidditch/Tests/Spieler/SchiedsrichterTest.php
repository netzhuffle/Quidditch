<?php

namespace Netzhuffle\Tests\Quidditch\Spieler;
use Netzhuffle\Quidditch\Quidditch;
use Netzhuffle\Quidditch\Team;
use Netzhuffle\Quidditch\Spieler\Schiedsrichter;
use Netzhuffle\Quidditch\Spieler\TreiberCP;
use Netzhuffle\Quidditch\Ball\Klatscher;
use Netzhuffle\Quidditch\Spieler\Jaeger;
use Netzhuffle\Quidditch\Spieler\Sucher;
use Netzhuffle\Quidditch\Befehl;

class SchiedsrichterTest extends \PHPUnit_Framework_TestCase
{
    protected $quidditch;
    protected $schiedsrichter;
    
    protected function setUp()
    {
        $this->quidditch = Quidditch::getInstance(true);
        $this->quidditch->team1 = new Team("X");
        $this->quidditch->team2 = new Team("C");
        $this->quidditch->schiedsrichter = new Schiedsrichter('aSchiedsrichter');
        $this->schiedsrichter = $this->quidditch->schiedsrichter;
    }
    
    protected function assertStackCount($count)
    {
        $befehle = $this->quidditch->getNextStackItems();
        $this->assertCount($count, $befehle);
    }
    
    protected function getFromStack($index)
    {
        $befehle = $this->quidditch->getNextStackItems();
        return $befehle[$index];
    }
    
    public function testPositionklatscherdice()
    {
        $this->schiedsrichter->actPositionklatscherdice(null); // no test, just check if everything works
    }
}
