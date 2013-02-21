<?php

namespace Netzhuffle\Quidditch\Spieler;
use Netzhuffle\Quidditch\Quidditch;
use Netzhuffle\Quidditch\Befehl;

abstract class Spieler
{
    public $name;
    public $ids;
    public $team;
    private $commands;
    public $isOut;
    public $lastCommand;
    public $erfolgswurf;
    public $kampfwurf;
    public $die2;
    public $feld;
    protected $quidditch;

    public function __construct($name, $team, Quidditch $quidditch)
    {
        $this->name = $name;
        $this->team = $team;
        $this->quidditch = $quidditch;
        $this->commands = array();
    }

    public function canDoCommand($befehl)
    {
        if ($befehl->befehl == "Write" || $befehl->befehl == "WriteNotAllowed") {
            return true;
        }
        if (!array_key_exists($befehl->befehl, $this->commands)) {
            return false;
        }

        return in_array($befehl->param, $this->commands[$befehl->befehl]);
    }

    public function setCommands($commands)
    {
        foreach ($commands as $command => $params) {
            if (!is_array($params)) {
                $commands[$command] = array($params);
            }
        }
        $this->commands = $commands;
    }

    public function deleteCommands()
    {
        $this->commands = array();
    }

    public function actWrite($befehl)
    {
        $this->write($befehl->param);
    }

    public function actWriteNotAllowed($befehl)
    {
        $this->write($befehl->param, false);
    }

    public function actDice($befehl)
    {
        $this->dice($this->canDoCommand($befehl));
        if (isset($this->lastCommand)
            && method_exists($this, "actDice" . $this->lastCommand->befehl)) {
            call_user_func(
                array($this, "actDice" . $this->lastCommand->befehl), $befehl);
        }
    }

    protected function actDrittel($befehl)
    {
        $this->feld = array_search($befehl->befehl, $quidditch->feldernamen);
    }

    public function reactDice($befehl)
    {
        if (isset($befehl->wer->lastCommand)
            && method_exists($this,
                "actDice" . $befehl->wer->lastCommand->befehl)) {
            call_user_func(
                array($this, "actDice" . $befehl->wer->lastCommand->befehl),
                $befehl);
        }
    }

    public function reactQuaffeldice($befehl)
    {
        if ($this->isComputerCaptain()) {
            $this->delay(2, "Dice");
        }
    }

    public function reactTordrittel($befehl)
    {
        if ($this->isComputerCaptain()) {
            $hasTeamQuaffel = ($this->quidditch->quaffel->besitzer
                && $this->quidditch->quaffel->besitzer->team == $this->team);
            if (!$hasTeamQuaffel) {
                $feld = mt_rand(0, 1) * 2; // = $feld entweder 0 oder 2
                $this->delay(2, $this->quidditch->feldernamen[$feld]);
            }
        }
    }

    public function reactQuaffeljäger($befehl)
    {
        if ($this->isComputerCaptain()) {
            $hasTeamQuaffel = ($this->quidditch->quaffel->besitzer
                && $this->quidditch->quaffel->besitzer->team == $this->team);
            if ($hasTeamQuaffel) {
                $team = $this->team->name;
                $nummer = mt_rand(1, 3);
                $this->delay(2, $team . "Jäger" . $nummer);
            }
        }
    }

    public function isComputerCaptain()
    {
        return $this->team && $this->team->isComputer
            && $this == $this->team->kapitaen;
    }

    protected function dice($isAllowed = true)
    {
        $this->erfolgswurf = mt_rand(1, 6);
        $this->die2 = mt_rand(1, 6);
        $this->kampfwurf = $this->erfolgswurf + $this->die2;

        $this->quidditch->chat->rollDice($this, $this->erfolgswurf, $this->die2, $isAllowed);
    }

    protected function write($message, $isAllowed = true)
    {
        $this->quidditch->chat->rollDice($this, $message, $isAllowed);
    }

    protected function delay($delay, $befehl, $param = null)
    {
        $befehl = new Befehl($this, $befehl, $param, $this->quidditch);
        $this->quidditch->addStackItem($befehl, $delay);
    }
}
