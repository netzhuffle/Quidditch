<?php

namespace Netzhuffle\Quidditch\Tests\Spieler;
use Netzhuffle\Quidditch\Spiel;
use Netzhuffle\Quidditch\Spieler\Jaeger;
use Netzhuffle\Quidditch\Ball\Quaffel;
use Netzhuffle\Quidditch\Chat\ArrayChat;

class JaegerTest extends \PHPUnit_Framework_TestCase
{
    public function testHasQuaffel()
    {
        $spiel = new Spiel(new ArrayChat());
        $spiel->quaffel = new Quaffel();
        $jaeger = new Jaeger("CJäger2", null, $spiel);
        $spiel->quaffel->besitzer = $jaeger;
        $this->assertTrue($jaeger->hasQuaffel());
    }
}
