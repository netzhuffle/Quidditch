<?php

namespace Netzhuffle\Tests\Quidditch\Spieler;
use Netzhuffle\Quidditch\Quidditch;
use Netzhuffle\Quidditch\Spieler\Jaeger;
use Netzhuffle\Quidditch\Ball\Quaffel;

class JaegerTest extends \PHPUnit_Framework_TestCase
{
    public function testHasQuaffel()
    {
        $quidditch = Quidditch::getInstance(true);
        $quidditch->quaffel = new Quaffel();
        $jaeger = new Jaeger("CJäger2", null);
        $quidditch->quaffel->besitzer = $jaeger;
        $this->assertTrue($jaeger->hasQuaffel());
    }
}
