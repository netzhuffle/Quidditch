<?php

namespace Netzhuffle\MainChat\Quidditch;

abstract class Spieler
{
    public $name = "Spieler";
    public $id = 1;
    public $team;
    private $commands = array();
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
    }

    private function getID()
    {
        $result_id = mysql_query("SELECT u_id FROM user WHERE u_nick = '$this->name' LIMIT 1") or trigger_error(mysql_error(), E_USER_ERROR);
        if (!($id = mysql_fetch_assoc($result_id))) {
        //	require_once "register.php";
        //	register($this->name, $this->name, "jannis@gmx.ch", false);
        //	$result_id = mysql_query("SELECT id FROM ".DB_PREFIX."user WHERE name = '$this->name' LIMIT 1") or trigger_error(mysql_error(), E_USER_ERROR);
        //	$id = mysql_fetch_assoc($result_id);
            trigger_error("Kein User mit Name $this->name", E_USER_ERROR);
        }

        return $id["u_id"];
    }

    public function doCommand($befehl)
    {
        if ($befehl->befehl != "Write") {
            if ($this->canDoCommand($befehl)) {
                $this->deleteCommands();
                $this->act($befehl);
            } elseif ($befehl->befehl == "Dice") {
                $this->dice();
            }
        } else {
            $this->write($befehl->param);
        }
    }

    public function canDoCommand($befehl)
    {
        if ($befehl->befehl == "Write") {
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

    protected function act($befehl)
    {
        $quidditch = Quidditch::getInstance();
        switch ($befehl->befehl) {
            case $quidditch->feldernamen[0]:
            case $quidditch->feldernamen[1]:
            case $quidditch->feldernamen[2]:
                $this->feld = array_search($befehl->befehl, $quidditch->feldernamen);
                break;
            case "Dice":
                $this->dice();
                break;
        }
    }

    public function react($befehl)
    {
        if ($this->team && $this->team->isComputer && $this->name == $this->team->kapitaen->name) {
            $quidditch = Quidditch::getInstance();
            $hasTeamQuaffel = ($quidditch->quaffelSpieler && $quidditch->quaffelSpieler->team->name == $this->team->name);
            if ($befehl->befehl == "Quaffeldice") {
                $this->delay(2, "Dice");
            } elseif ($befehl->befehl == "Tordrittel" && !$hasTeamQuaffel) {
                $feld = mt_rand(0, 1) * 2; // = $feld entweder 0 oder 2
                $this->delay(2, $quidditch->feldernamen[$feld]);
            } elseif ($befehl->befehl == "Quaffeljäger" && $hasTeamQuaffel) {
                $team = $this->team->name;
                $nummer = mt_rand(1, 3);
                $this->delay(2, $team."Jäger".$nummer);
            }
        }
    }

    protected function dice()
    {
        global $t;

        $die1 = mt_rand(1, 6);
        $die2 = mt_rand(1, 6);
        $summe = $die1 + $die2;
        $message = $t['chat_msg34'];
        $message = str_replace("%user%", $this->name, $message);
        $message = str_replace("%wuerfel%", "2 großen 6-seitigen Würfeln", $message);
        $message .= " $die1 $die2. Summe=$summe.";
        $farbe = isset($this->team) ? $this->team->farbe : "";
        $quidditch = Quidditch::getInstance();
        hidden_msg($this->name, $this->getID(), $farbe, $quidditch->room, $message);
        $this->erfolgswurf = $die1;
        $this->kampfwurf = $summe;
        $this->die2 = $die2;
    }

    protected function write($message)
    {
        require_once 'functions.php-func-html_parse.php';
        $quidditch = Quidditch::getInstance();
        $f = array();
        $f['c_text'] = html_parse(false, htmlspecialchars($message));
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
    	if (!($this instanceof Schiedsrichter)) {
        	$delay = 1; // TODO remove: Temporary overwrite for faster testing
        }
        $befehl = new Befehl($this, $befehl, $param);
        Quidditch::getInstance()->addStackItem($befehl, $delay);
    }
}
