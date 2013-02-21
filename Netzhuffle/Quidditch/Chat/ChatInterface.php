<?php

namespace Netzhuffle\Quidditch\Chat;
use Netzhuffle\Quidditch\Spieler\Spieler;

interface ChatInterface
{
    /**
     * A Spieler writes a message
     * @param Spieler $spieler
     * @param string  $message
     * @param boolean $isAllowed if the Spieler is allowed to do write the message at the moment
     */
    public function write(Spieler $spieler, $message, $isAllowed);

    /**
     * A Spieler rolls the dice
     * @param Spieler $spieler
     * @param number  $die1      1–6
     * @param number  $die2      1–6
     * @param boolean $isAllowed if the Spieler is allowed to do roll the dice at the moment
     */
    public function rollDice(Spieler $spieler, $die1, $die2, $isAllowed);
}
