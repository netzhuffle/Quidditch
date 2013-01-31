<?php

namespace Netzhuffle\Tests\Quidditch\Spieler;
use Netzhuffle\Quidditch\Quidditch;
use Netzhuffle\Quidditch\Spieler\Jaeger;
use Netzhuffle\Quidditch\Ball\Quaffel;

class JaegerTest extends \PHPUnit_Framework_TestCase
{
    public function testHasQuaffel()
    {
        $quidditch = new Quidditch();
        $quidditch->quaffel = new Quaffel();
        $jaeger = new Jaeger("CJäger2", null, $quidditch);
        $quidditch->quaffel->besitzer = $jaeger;
        $this->assertTrue($jaeger->hasQuaffel());
    }
}
