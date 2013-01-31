<?php

namespace Netzhuffle\Tests\Quidditch\Spieler;
use Netzhuffle\Quidditch\Quidditch;
use Netzhuffle\Quidditch\Team;
use Netzhuffle\Quidditch\Spieler\Sucher;
use Netzhuffle\Quidditch\Ball\Quaffel;

class SpielerTest extends \PHPUnit_Framework_TestCase
{
    protected $quidditch;
    protected $team;
    protected $spieler;
    
    protected function setUp()
    {
        $this->quidditch = new Quidditch();
        $this->quidditch->team1 = $this->team = new Team("C", $this->quidditch);
        $this->quidditch->quaffel = new Quaffel();
        $this->spieler = new Sucher("CSucher", $this->quidditch->team1, $this->quidditch);
    }
    
    protected function assertStackCount($count)
    {
        $befehle = $this->quidditch->getNextStackItems();
        $this->assertCount($count, $befehle);
    }
    
    protected function getNextOnStack()
    {
        $befehle = $this->quidditch->getNextStackItems();
        return $befehle[0];
    }
    
    public function testIsComputerCaptain()
    {
        $this->team->kapitaen = $this->spieler;
        $this->assertTrue($this->spieler->isComputerCaptain());
    }
    
    public function testReactQuaffeldice()
    {
        $this->team->kapitaen = $this->spieler;
        $this->spieler->reactQuaffeldice(null);
        
        $this->assertStackCount(1);
        $befehl = $this->getNextOnStack();
        $this->assertEquals($this->spieler, $befehl->wer);
        $this->assertEquals('Dice', $befehl->befehl);
        $this->assertEquals(null, $befehl->param);
    }
    
    public function testReactTordrittel()
    {
        $this->team->kapitaen = $this->spieler;
        $this->quidditch->quaffel->besitzer = new Sucher("XSucher", null, $this->quidditch);
        $this->spieler->reactTordrittel(null);
        
        $this->assertStackCount(1);
        $befehl = $this->getNextOnStack();
        $this->assertEquals($this->spieler, $befehl->wer);
        $this
            ->assertTrue(
                $befehl->befehl === 'T' || $befehl->befehl === 'M'
                    || $befehl->befehl === 'H');
        $this->assertEquals(null, $befehl->param);
    }
    
    public function testReactQuaffeljaeger()
    {
        $this->team->kapitaen = $this->spieler;
        $this->quidditch->quaffel->besitzer = $this->spieler;
        $this->spieler->reactQuaffeljäger(null);
        
        $this->assertStackCount(1);
        $befehl = $this->getNextOnStack();
        $this->assertEquals($this->spieler, $befehl->wer);
        $this
            ->assertTrue(
                $befehl->befehl === 'CJäger1' || $befehl->befehl === 'CJäger2'
                    || $befehl->befehl === 'CJäger3');
        $this->assertEquals(null, $befehl->param);
    }
}
