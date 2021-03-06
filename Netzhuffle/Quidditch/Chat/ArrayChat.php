<?php

namespace Netzhuffle\Quidditch\Chat;
use Netzhuffle\Quidditch\Spieler\Spieler;

class ArrayChat implements ChatInterface
{
    /**
     * The messages
     * @var array
     */
    private $messages = [];

    /**
     * (non-PHPdoc)
     * @see \Netzhuffle\Quidditch\Chat\ChatInterface::write()
     */
    public function write(Spieler $spieler, $message)
    {
        $this->messages[] = ['datetime' => new \DateTime(), 'spieler' => $spieler, 'message' => $message];
    }

    /**
     * (non-PHPdoc)
     * @see \Netzhuffle\Quidditch\Chat\ChatInterface::rollDice()
     */
    public function rollDice(Spieler $spieler, $die1, $die2)
    {
        $this->messages[] = ['datetime' => new \DateTime(), 'spieler' => $spieler, 'die1' => $die1, 'die2' => $die2];
    }

    /**
     * Returns the written messages.
     * Newer messages have higher keys. Each entry is an array with the keys
     * 'datetime' (\DateTime),
     * 'spieler' (\Netzhuffle\Quidditch\Spieler\Spieler),
     * 'message' (string) or 'die1'/'die2' (number)
     * @return array the messages
     */
    public function getMessages()
    {
        return $this->messages;
    }

}
