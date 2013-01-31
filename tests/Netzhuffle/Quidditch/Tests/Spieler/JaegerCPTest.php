<?php

namespace Netzhuffle\Tests\Quidditch\Spieler;
use Netzhuffle\Quidditch\Quidditch;
use Netzhuffle\Quidditch\Spieler\JaegerCP;
use Netzhuffle\Quidditch\Ball\Quaffel;

class JaegerCPTest extends \PHPUnit_Framework_TestCase
{
    protected $quidditch;
    
    protected function setUp()
    {
        $this->quidditch = Quidditch::getInstance(true);
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
        $jaeger = new JaegerCP("CJäger2", null);
        $jaeger->reactPositionjäger(null);
        
        $this->assertStackCount(1);
        $befehl = $this->getNextOnStack();
        $this->assertEquals($jaeger, $befehl->wer);
        $this
            ->assertTrue(
                $befehl->befehl === 'T' || $befehl->befehl === 'M'
                    || $befehl->befehl === 'H');
        $this->assertEquals(null, $befehl->param);
    }
}
