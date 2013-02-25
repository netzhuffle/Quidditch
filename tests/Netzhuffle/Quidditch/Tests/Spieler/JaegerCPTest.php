<?php

namespace Netzhuffle\Tests\Quidditch\Spieler;
use Netzhuffle\Quidditch\Quidditch;
use Netzhuffle\Quidditch\Spieler\JaegerCP;
use Netzhuffle\Quidditch\Ball\Quaffel;
use Netzhuffle\Quidditch\Chat\ArrayChat;

class JaegerCPTest extends \PHPUnit_Framework_TestCase
{
    protected $quidditch;
    
    protected function setUp()
    {
        $this->quidditch = new Quidditch(new ArrayChat());
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
}
