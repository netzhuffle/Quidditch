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

class TreiberCPTest extends \PHPUnit_Framework_TestCase
{
    protected $spiel;
    protected $klatscher1;
    protected $klatscher2;
    protected $treiber;
    protected $gegnerJaeger;
    protected $gegnerSucher;
    
    protected function setUp()
    {
        $this->spiel = new Spiel(new ArrayChat());
        $this->klatscher1 = $this->quidditch->klatscher1 = new Klatscher();
        $this->klatscher2 = $this->quidditch->klatscher2 = new Klatscher();
        $this->spiel->team1 = new Team("X", $this->spiel);
        $this->spiel->team2 = new Team("C", $this->spiel);
        $this->spiel->schiedsrichter = new Schiedsrichter('aSchiedsrichter', $this->spiel);
        $this->treiber = $this->spiel->team1->treiber1;
        $this->gegnerJaeger = $this->spiel->team2->jaeger3;
        $this->gegnerSucher = $this->spiel->team2->sucher;
    }
    
    /*
    public function testReactPositiontreiber()
    {
        $this->treiber->reactPositiontreiber(null);
        
        $this->assertStackCount(1);
        $befehl = $this->getFromStack(0);
        $this->assertEquals($this->treiber, $befehl->wer);
        $this->assertThat($befehl->befehl, $this->logicalOr($this->equalTo('T'), $this->equalTo('M'), $this->equalTo('H')));
        $this->assertEquals(null, $befehl->param);
    }
    
    public function testReactKlatscherfreigebNoKlatscher()
    {
        $this->treiber->feld = 1;
        $this->gegnerJaeger->feld = 1;
        $this->gegnerSucher->feld = 1;
        $this->klatscher1->feld = 0;
        $this->klatscher2->feld = 2;
        $this->treiber->reactKlatscherfreigeb(null);
        
        $this->assertStackCount(0);
    }
    
    public function testReactKlatscherfreigebNoGegner()
    {
        $this->treiber->feld = 1;
        $this->gegnerJaeger->feld = 0;
        $this->gegnerSucher->feld = 2;
        $this->klatscher1->feld = 1;
        $this->klatscher2->feld = 1;
        $this->treiber->reactKlatscherfreigeb(null);
        
        $this->assertStackCount(0);
    }
    
    public function testReactKlatscherfreigebOneKlatscher()
    {
        $this->treiber->feld = 1;
        $this->gegnerJaeger->feld = 1;
        $this->gegnerSucher->feld = 1;
        $this->klatscher1->feld = 0;
        $this->klatscher2->feld = 1;
        $this->treiber->reactKlatscherfreigeb(null);
        
        $this->assertStackCount(2);
        $klatscherwurf = $this->getFromStack(0);
        $this->assertEquals($this->treiber, $klatscherwurf->wer);
        $this->assertEquals('Klatscherwurf', $klatscherwurf->befehl);
        $this->assertThat($klatscherwurf->param, $this->logicalOr($this->equalTo($this->gegnerJaeger->name), $this->equalTo($this->gegnerSucher->name)));
        $this->assertEquals(new Befehl($this->treiber, 'Dice', null, $this->quidditch), $this->getFromStack(1));
    }
    */
}
