<?php

namespace Netzhuffle\Tests\Quidditch\Chat;
use Netzhuffle\Quidditch\Quidditch;
use Netzhuffle\Quidditch\Chat\ArrayChat;
use Netzhuffle\Quidditch\Spieler\Hueter;

class ArrayChatTest extends \PHPUnit_Framework_TestCase
{
    protected $chat;
    protected $spieler;
    
    protected function setUp()
    {
        $this->chat = new ArrayChat();
        $quidditch = $this->getMock('Quidditch');
        $this->spieler = new Hueter("SHüter", null, $quidditch);
    }
    
    public function testWrite()
    {
        $this->chat->write($this->spieler, "my message", false);
        $messages = $this->chat->getMessages();
        
        $this->assertCount(1, $messages);
        $message = $messages[0];
        
        $this->assertInstanceOf("\DateTime", $message['datetime']);
        $this->assertEquals($this->spieler, $message['spieler']);
        $this->assertEquals("my message", $message['message']);
        $this->assertEquals(false, $message['isAllowed']);
    }
    
    public function testRollDice()
    {
        $this->chat->rollDice($this->spieler, 5, 6, true);
        $messages = $this->chat->getMessages();
        
        $this->assertCount(1, $messages);
        $message = $messages[0];
        
        $this->assertInstanceOf("\DateTime", $message['datetime']);
        $this->assertEquals($this->spieler, $message['spieler']);
        $this->assertEquals(5, $message['die1']);
        $this->assertEquals(6, $message['die2']);
        $this->assertEquals(true, $message['isAllowed']);
    }
}
