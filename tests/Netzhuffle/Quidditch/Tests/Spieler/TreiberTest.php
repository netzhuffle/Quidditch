<?php

namespace Netzhuffle\Quidditch\Tests\Spieler;
use Netzhuffle\Quidditch\Spiel;
use Netzhuffle\Quidditch\Spieler\Treiber;
use Netzhuffle\Quidditch\Ball\Klatscher;
use Netzhuffle\Quidditch\Chat\ArrayChat;

class TreiberTest extends \PHPUnit_Framework_TestCase
{
    protected $spiel;
    protected $treiber;

    protected function setUp()
    {
        $this->spiel = new Spiel(new ArrayChat());
        $this->spiel->klatscher1 = new Klatscher();
        $this->spiel->klatscher2 = new Klatscher();
        $this->treiber = new Treiber("CTreiber2", null, $this->spiel);
    }

    public function testHasKlatscherNone()
    {
        $this->assertFalse($this->treiber->hasKlatscher());
    }

    public function testHasKlatscher1()
    {
        $this->spiel->klatscher1->besitzer = $this->treiber;
        $this->assertTrue($this->treiber->hasKlatscher());
    }

    public function testHasKlatscher2()
    {
        $this->spiel->klatscher2->besitzer = $this->treiber;
        $this->assertTrue($this->treiber->hasKlatscher());
    }
}
