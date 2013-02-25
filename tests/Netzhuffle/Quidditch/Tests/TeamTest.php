<?php
namespace Netzhuffle\MainChat\Test\Quidditch;
use Netzhuffle\Quidditch\Quidditch;
use Netzhuffle\Quidditch\Chat\ArrayChat;
use Netzhuffle\Quidditch\Team;

class TeamTest extends \PHPUnit_Framework_TestCase
{
    protected $quidditch;
    protected $team;
    
    public function setUp()
    {
        $this->quidditch = new Quidditch(new ArrayChat());
    }
    
    public function tearDown()
    {
        $this->team = null;
    }
    
    protected function assertPlayer($expectedClass, $expectedName, $actual)
    {
        $this->assertEquals(new $expectedClass($expectedName, $this->team, $this->quidditch), $actual);
    }
    
    protected function assertPlayerNotCP($expectedClass, $expectedName, $actual)
    {
        $this->assertPlayer($expectedClass, $expectedName, $actual);
        $this->assertNotInstanceOf($expectedClass . 'CP', $actual);
    }
    
    public function testConstructHumanTeam()
    {
        $this->team = new Team('H', $this->quidditch);
        
        $this->assertPlayerNotCP('Netzhuffle\\Quidditch\\Spieler\\Jaeger', 'HJäger1', $this->team->jaeger1);
        $this->assertPlayerNotCP('Netzhuffle\\Quidditch\\Spieler\\Jaeger', 'HJäger2', $this->team->jaeger2);
        $this->assertPlayerNotCP('Netzhuffle\\Quidditch\\Spieler\\Jaeger', 'HJäger3', $this->team->jaeger3);
        $this->assertPlayerNotCP('Netzhuffle\\Quidditch\\Spieler\\Hueter', 'HHüter', $this->team->hueter);
        $this->assertPlayerNotCP('Netzhuffle\\Quidditch\\Spieler\\Treiber', 'HTreiber1', $this->team->treiber1);
        $this->assertPlayerNotCP('Netzhuffle\\Quidditch\\Spieler\\Treiber', 'HTreiber2', $this->team->treiber2);
        $this->assertPlayerNotCP('Netzhuffle\\Quidditch\\Spieler\\Sucher', 'HSucher', $this->team->sucher);
    }
    
    public function testConstructComputerTeam()
    {
        $this->team = new Team('X', $this->quidditch);
        
        $this->assertPlayer('Netzhuffle\\Quidditch\\Spieler\\JaegerCP', 'XJäger1', $this->team->jaeger1);
        $this->assertPlayer('Netzhuffle\\Quidditch\\Spieler\\JaegerCP', 'XJäger2', $this->team->jaeger2);
        $this->assertPlayer('Netzhuffle\\Quidditch\\Spieler\\JaegerCP', 'XJäger3', $this->team->jaeger3);
        $this->assertPlayer('Netzhuffle\\Quidditch\\Spieler\\HueterCP', 'XHüter', $this->team->hueter);
        $this->assertPlayer('Netzhuffle\\Quidditch\\Spieler\\TreiberCP', 'XTreiber1', $this->team->treiber1);
        $this->assertPlayer('Netzhuffle\\Quidditch\\Spieler\\TreiberCP', 'XTreiber2', $this->team->treiber2);
        $this->assertPlayer('Netzhuffle\\Quidditch\\Spieler\\SucherCP', 'XSucher', $this->team->sucher);
    }
}
