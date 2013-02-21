<?php
namespace Netzhuffle\MainChat\Test\Quidditch;
use Netzhuffle\Quidditch\Quidditch;
use Netzhuffle\Quidditch\Ball\Quaffel;
use Netzhuffle\Quidditch\Team;
use Netzhuffle\Quidditch\Spieler\Schiedsrichter;
use Netzhuffle\Quidditch\Chat\ArrayChat;

class QuidditchTest extends \PHPUnit_Framework_TestCase
{
    protected $quidditch;
    
    public function setUp()
    {
        $this->quidditch = new Quidditch(new ArrayChat());
        $this->quidditch->team1 = new Team('C', $this->quidditch);
        $this->quidditch->team2 = new Team('X', $this->quidditch);
        $this->quidditch->schiedsrichter = new Schiedsrichter(
            'aSchiedsrichter', $this->quidditch);
    }
    
    public function testGetAllSpieler()
    {
        $this->assertCount(15, $this->quidditch->getAllSpieler());
    }
    
    public function testGetSpieler()
    {
        $spieler = $this->quidditch->getSpieler('XJäger3');
        $this->assertEquals($this->quidditch->team2->jaeger3, $spieler);
    }
    
    public function testGetSpielerSchiedsrichter()
    {
        $spieler = $this->quidditch->getSpieler('aSchiedsrichter');
        $this->assertEquals($this->quidditch->schiedsrichter, $spieler);
    }
    
    public function testGetSpielerUnknown()
    {
        $spieler = $this->quidditch->getSpieler('HJäger2');
        $this->assertNull($spieler);
    }
    
    public function testGetSpielerInDrittel()
    {
        $this->quidditch->team1->jaeger2->feld = 1;
        $this->quidditch->team1->sucher->feld = 1;
        $this->quidditch->team2->treiber1->feld = 1;
        $this->quidditch->team1->jaeger3->feld = 0;
        $this->quidditch->team2->sucher->feld = 2;
        $this->quidditch->team2->jaeger2->feld = 2;
        
        $this->assertCount(3, $this->quidditch->getSpielerInDrittel(1));
    }
    
    // TODO Test commands and stacks
}
