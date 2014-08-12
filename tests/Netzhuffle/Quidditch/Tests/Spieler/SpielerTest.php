<?php

namespace Netzhuffle\Quidditch\Tests\Spieler;
use Netzhuffle\Quidditch\Spiel;
use Netzhuffle\Quidditch\Team;
use Netzhuffle\Quidditch\Spieler\Sucher;
use Netzhuffle\Quidditch\Ball\Quaffel;
use Netzhuffle\Quidditch\Chat\ArrayChat;

class SpielerTest extends \PHPUnit_Framework_TestCase
{
    protected $spiel;
    protected $team;
    protected $spieler;

    protected function setUp()
    {
        $this->spiel = new Spiel(new ArrayChat());
        $this->spiel->team1 = $this->team = new Team("C", $this->spiel);
        $this->spiel->quaffel = new Quaffel();
        $this->spieler = new Sucher("CSucher", $this->spiel->team1, $this->spiel);
    }

    /*
    public function testReactQuaffeldice()
    {
        $this->team->kapitaen = $this->spieler;
        $this->spieler->reactQuaffeldice(null);

        $this->assertStackCount(1);
        $befehl = $this->getNextOnStack();
        $this->assertEquals($this->spieler, $befehl->wer);
        $this->assertEquals('Dice', $befehl->befehl);
        $this->assertNull($befehl->param);
    }

    public function testReactTordrittel()
    {
        $this->team->kapitaen = $this->spieler;
        $this->quidditch->quaffel->besitzer = new Sucher("XSucher", null, $this->quidditch);
        $this->spieler->reactTordrittel(null);

        $this->assertStackCount(1);
        $befehl = $this->getNextOnStack();
        $this->assertEquals($this->spieler, $befehl->wer);
        $this->assertThat($befehl->befehl, $this->logicalOr($this->equalTo('T'), $this->equalTo('M'), $this->equalTo('H')));
        $this->assertNull($befehl->param);
    }

    public function testReactQuaffeljaeger()
    {
        $this->team->kapitaen = $this->spieler;
        $this->quidditch->quaffel->besitzer = $this->spieler;
        $this->spieler->reactQuaffeljäger(null);

        $this->assertStackCount(1);
        $befehl = $this->getNextOnStack();
        $this->assertEquals($this->spieler, $befehl->wer);
        $this->assertThat($befehl->befehl, $this->logicalOr($this->equalTo('CJäger1'), $this->equalTo('CJäger2'), $this->equalTo('CJäger3')));
        $this->assertEquals(null, $befehl->param);
    }
    */
}
