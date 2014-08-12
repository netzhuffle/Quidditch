<?php

namespace Netzhuffle\Quidditch\Chat;
use Netzhuffle\Quidditch\Spieler\Spieler;

interface ChatInterface
{
    /**
     * A Spieler writes a message
     * @param Spieler $spieler
     * @param string  $message
     */
    public function write(Spieler $spieler, $message);

    /**
     * A Spieler rolls the dice
     * @param Spieler $spieler
     * @param number  $die1      1–6
     * @param number  $die2      1–6
     */
    public function rollDice(Spieler $spieler, $die1, $die2);
}
