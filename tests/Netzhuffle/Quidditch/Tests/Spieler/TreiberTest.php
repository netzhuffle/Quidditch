<?php

namespace Netzhuffle\Tests\Quidditch\Spieler;
use Netzhuffle\Quidditch\Quidditch;
use Netzhuffle\Quidditch\Spieler\Treiber;
use Netzhuffle\Quidditch\Ball\Klatscher;

class TreiberTest extends \PHPUnit_Framework_TestCase
{
    protected $quidditch;
    protected $treiber;
    
    protected function setUp()
    {
        $this->quidditch = new Quidditch();
        $this->quidditch->klatscher1 = new Klatscher();
        $this->quidditch->klatscher2 = new Klatscher();
        $this->treiber = new Treiber("CTreiber2", null, $this->quidditch);
    }
    
    public function testHasKlatscherNone()
    {
        $this->assertFalse($this->treiber->hasKlatscher());
    }
    
    public function testHasKlatscher1()
    {
        $this->quidditch->klatscher1->besitzer = $this->treiber;
        $this->assertTrue($this->treiber->hasKlatscher());
    }
    
    public function testHasKlatscher2()
    {
        $this->quidditch->klatscher2->besitzer = $this->treiber;
        $this->assertTrue($this->treiber->hasKlatscher());
    }
}
