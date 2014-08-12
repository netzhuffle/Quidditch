<?php

namespace Netzhuffle\Tests\Quidditch\Spieler;
use Netzhuffle\Quidditch\Spiel;
use Netzhuffle\Quidditch\Spieler\JaegerCP;
use Netzhuffle\Quidditch\Ball\Quaffel;
use Netzhuffle\Quidditch\Chat\ArrayChat;

class JaegerCPTest extends \PHPUnit_Framework_TestCase
{
    protected $spiel;
    
    protected function setUp()
    {
        $this->spiel = new Spiel(new ArrayChat());
    }
    
    /*
    public function testReactPositionjäger()
    {
        $this->quidditch->quaffel = new Quaffel();
        $jaeger = new JaegerCP("CJäger2", null, $this->quidditch);
        $jaeger->reactPositionjäger(null);
        
        $this->assertStackCount(1);
        $befehl = $this->getNextOnStack();
        $this->assertEquals($jaeger, $befehl->wer);
        $this->assertThat($befehl->befehl, $this->logicalOr($this->equalTo('T'), $this->equalTo('M'), $this->equalTo('H')));
        $this->assertNull($befehl->param);
    }
    */
}
