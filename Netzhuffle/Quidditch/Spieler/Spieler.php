<?php

namespace Netzhuffle\Quidditch\Spieler;
use Netzhuffle\Quidditch\Spiel;

abstract class Spieler
{
    public $name;
    public $team;
    private $allowedCommands;
    public $isOut;
    public $lastCommand;
    public $erfolgswurf;
    public $kampfwurf;
    public $die2;
    public $feld;
    protected $spiel;

    public function __construct($name, $team, Spiel $spiel)
    {
        $this->name = $name;
        $this->team = $team;
        $this->spiel = $spiel;
        $this->allowedCommands = array();
    }

    public function canDoCommand($command)
    {
        return in_array($command, $this->allowedCommands);
    }

    public function setCommands(array $commands)
    {
        $this->commands = $commands;
    }

    public function deleteCommands()
    {
        $this->setCommands(array());
    }

    /*
    public function reactQuaffeldice($befehl)
    {
        if ($this->isComputerCaptain()) {
            $this->delay(2, "Dice");
        }
    }

    public function reactTordrittel($befehl)
    {
        if ($this->isComputerCaptain()) {
            $hasTeamQuaffel = ($this->quidditch->quaffel->besitzer && $this->quidditch->quaffel->besitzer->team == $this->team);
            if (!$hasTeamQuaffel) {
                $feld = mt_rand(0, 1) * 2; // = $feld entweder 0 oder 2
                $this->delay(2, $this->quidditch->feldernamen[$feld]);
            }
        }
    }

    public function reactQuaffeljäger($befehl)
    {
        if ($this->isComputerCaptain()) {
            $hasTeamQuaffel = ($this->quidditch->quaffel->besitzer && $this->quidditch->quaffel->besitzer->team == $this->team);
            if ($hasTeamQuaffel) {
                $team = $this->team->name;
                $nummer = mt_rand(1, 3);
                $this->delay(2, $team . "Jäger" . $nummer);
            }
        }
    }
    */

    protected function dice()
    {
        $this->erfolgswurf = mt_rand(1, 6);
        $this->die2 = mt_rand(1, 6);
        $this->kampfwurf = $this->erfolgswurf + $this->die2;

        $this->quidditch->chat->rollDice($this, $this->erfolgswurf, $this->die2);
    }

    protected function write($message)
    {
        $this->quidditch->chat->write($this, $message);
    }
}
