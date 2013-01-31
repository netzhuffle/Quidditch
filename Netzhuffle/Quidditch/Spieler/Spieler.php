<?php

namespace Netzhuffle\Quidditch\Spieler;
use Netzhuffle\Quidditch\Quidditch;
use Netzhuffle\Quidditch\Befehl;
use Netzhuffle\MainChat\Database;

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
    
    public function __construct($name, $team = null)
    {
        $this->name = $name;
        $this->id = $this->getID();
        $this->team = $team;
        $this->commands = array();
    }
    
    private function getID()
    {
        $result = Database::getInstance()
            ->query(
                "SELECT u_id FROM user WHERE u_nick = '$this->name' LIMIT 1")
            or trigger_error(mysql_error(), E_USER_ERROR);
        if (!($id = $result->fetch_assoc())) {
            trigger_error("Kein User mit Name $this->name", E_USER_ERROR);
        }
        
        return $id["u_id"];
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
        if (isset($this->lastCommand) && method_exists($this, "actDice" . $this->lastCommand->befehl)) {
            call_user_func(array($this, "actDice" . $this->lastCommand->befehl), $befehl);
        }
    }
    
    protected function actDrittel($befehl)
    {
        $this->feld = array_search($befehl->befehl, $quidditch->feldernamen);
    }
    
    public function reactDice($befehl)
    {
        if (isset($befehl->wer->lastCommand) && method_exists($this, "actDice" . $befehl->wer->lastCommand->befehl)) {
            call_user_func(array($this, "actDice" . $befehl->wer->lastCommand->befehl), $befehl);
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
            $quidditch = Quidditch::getInstance();
            $hasTeamQuaffel = ($quidditch->quaffel->besitzer
                && $quidditch->quaffel->besitzer->team == $this->team);
            if (!$hasTeamQuaffel) {
                $quidditch = Quidditch::getInstance();
                $feld = mt_rand(0, 1) * 2; // = $feld entweder 0 oder 2
                $this->delay(2, $quidditch->feldernamen[$feld]);
            }
        }
    }
    
    public function reactQuaffeljäger($befehl)
    {
        if ($this->isComputerCaptain()) {
            $quidditch = Quidditch::getInstance();
            $hasTeamQuaffel = ($quidditch->quaffel->besitzer
                && $quidditch->quaffel->besitzer->team == $this->team);
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
        global $t;
        
        $die1 = mt_rand(1, 6);
        $die2 = mt_rand(1, 6);
        $summe = $die1 + $die2;
        $message = $t['chat_msg34'];
        $message = str_replace("%user%", $this->name, $message);
        $message = str_replace("%wuerfel%", "2 großen 6-seitigen Würfeln",
            $message);
        $message .= " $die1 $die2. Summe=$summe.";
        if (!$isAllowed) {
            $message = "<s>" . $message . "</s>";
        }
        $farbe = isset($this->team) ? $this->team->farbe : "";
        $quidditch = Quidditch::getInstance();
        hidden_msg($this->name, $this->getID(), $farbe, $quidditch->room,
            $message);
        $this->erfolgswurf = $die1;
        $this->kampfwurf = $summe;
        $this->die2 = $die2;
    }
    
    protected function write($message, $isAllowed = true)
    {
        require_once 'functions.php-func-html_parse.php';
        $quidditch = Quidditch::getInstance();
        $f = array();
        $f['c_text'] = html_parse(false, htmlspecialchars($message));
        if (!$isAllowed) {
            $f['c_text'] = "<s>" . $f['c_text'] . "</s>";
        }
        $f['c_von_user'] = $this->name;
        $f['c_von_user_id'] = $this->getID();
        $f['c_raum'] = $quidditch->room;
        $f['c_typ'] = "N";
        if (isset($this->team)) { // Schiedsrichter hat kein Team
            $f['c_farbe'] = $this->team->farbe;
        }
        schreibe_chat($f);
    }
    
    protected function delay($delay, $befehl, $param = null)
    {
        $befehl = new Befehl($this, $befehl, $param);
        Quidditch::getInstance()->addStackItem($befehl, $delay);
    }
}
