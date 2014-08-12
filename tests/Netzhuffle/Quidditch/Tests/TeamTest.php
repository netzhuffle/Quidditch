<?php
namespace Netzhuffle\MainChat\Test\Quidditch;
use Netzhuffle\Quidditch\Spiel;
use Netzhuffle\Quidditch\Chat\ArrayChat;
use Netzhuffle\Quidditch\Team;

class TeamTest extends \PHPUnit_Framework_TestCase
{
    protected $spiel;
    protected $team;
    
    public function setUp()
    {
        $this->spiel = new Spiel(new ArrayChat());
    }
    
    public function tearDown()
    {
        $this->team = null;
    }
    
    protected function assertPlayer($expectedClass, $expectedName, $actual)
    {
    	$className = 'Netzhuffle\\Quidditch\\Spieler\\' . $expectedClass;
        $this->assertEquals(new $className($expectedName, $this->team, $this->spiel), $actual);
    }
    
    public function testConstructTeam()
    {
        $this->team = new Team('X', $this->spiel);
        
        $this->assertPlayer('JaegerCP', 'XJäger1', $this->team->jaeger1);
        $this->assertPlayer('JaegerCP', 'XJäger2', $this->team->jaeger2);
        $this->assertPlayer('JaegerCP', 'XJäger3', $this->team->jaeger3);
        $this->assertPlayer('HueterCP', 'XHüter', $this->team->hueter);
        $this->assertPlayer('TreiberCP', 'XTreiber1', $this->team->treiber1);
        $this->assertPlayer('TreiberCP', 'XTreiber2', $this->team->treiber2);
        $this->assertPlayer('SucherCP', 'XSucher', $this->team->sucher);
    }
}
