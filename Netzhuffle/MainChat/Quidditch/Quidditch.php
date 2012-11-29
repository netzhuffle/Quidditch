<?php

namespace Netzhuffle\MainChat\Quidditch;

class Quidditch
{
    private static $instance;
    public $room;
    public $runde = 0;
    public $schiedsrichter;
    public $team1;
    public $team2;
    public $quaffelSpieler;
    public $multipleJaegerFly = false;
    public $feldernamen = array("T", "M", "H");
    private $stack = array();

    private function __construct() {}

    public static function getInstance()
    {
        if (!self::$instance) {
            //mysql_query("LOCK TABLES quidditch WRITE, user READ LOCAL") or trigger_error(mysql_error(), E_USER_ERROR);
            $result = mysql_query("SELECT data FROM quidditch") or trigger_error(mysql_error(), E_USER_ERROR);
            $data = mysql_fetch_assoc($result);
            if (trim($data["data"])) {
                self::$instance = unserialize($data["data"]);
            }
            if (!self::$instance) { // z.B. wenn fehlerhafter Wert in Datenbank
                self::$instance = new self;
            }
        }

        return self::$instance;
    }

    public function flush()
    {
        $data = mysql_real_escape_string(serialize($this));
        mysql_query("UPDATE quidditch SET data = '$data'") or trigger_error(mysql_error(), E_USER_ERROR);
        self::$instance = null;
        //mysql_query("UNLOCK TABLES") or trigger_error(mysql_error(), E_USER_ERROR);
    }

    public function getSpieler($name)
    {
        if ($this->runde) {
            if (trim($name) == $this->schiedsrichter->name) {
                return $this->schiedsrichter;
            } else {
                foreach ($this->getAllSpieler() as $spieler) {
                    if ($spieler->name == trim($name)) {
                        return $spieler;
                    }
                }

                return null;
            }
        } else {
            return null;
        }
    }

    public function getAllSpieler()
    {
        $array = array();
        if ($this->runde) {
            $array[] = $this->schiedsrichter;
            $array[] = $this->team1->jaeger1;
            $array[] = $this->team1->jaeger2;
            $array[] = $this->team1->jaeger3;
            $array[] = $this->team1->hueter;
            $array[] = $this->team1->treiber1;
            $array[] = $this->team1->treiber2;
            $array[] = $this->team1->sucher;
            $array[] = $this->team2->jaeger1;
            $array[] = $this->team2->jaeger2;
            $array[] = $this->team2->jaeger3;
            $array[] = $this->team2->hueter;
            $array[] = $this->team2->treiber1;
            $array[] = $this->team2->treiber2;
            $array[] = $this->team2->sucher;
        }

        return $array;
    }

    public function command($command, $u_nick)
    {
        $befehl = trim($command[0]);
        $param = trim($command[1] . " " . $command[2] . " " . $command[3]);
        if ($befehl == "Quidditchstart") {
            $this->start($param);
        } else {
            if ($befehl == "/dice" && $param = "2w6") {
                $befehl = "Dice";
                $param = null;
            }
            $befehl = new Befehl($u_nick, $befehl, $param);
            $befehl->execute();
        }
    }

    public function doStack()
    {
        foreach ($this->stack as $time => $befehle) {
            if ($time <= microtime(true)) {
                foreach ($befehle as $befehl) {
                    $befehl->execute();
                }
                unset($this->stack[$time]);
            }
        }
    }

    public function addStackItem($befehl, $delay)
    {
        mt_srand();
        $time = microtime(true) + $delay + mt_rand(1, 1000)/1000;
        if (!isset($this->stack[$time])) {
            $this->stack[$time] = array();
        }
        $this->stack[$time][] = $befehl;
    }

    private function start($modus)
    {
        self::$instance = null;
        $this->flush();

        if(!$modus) $modus = "S-C";
        $modus = explode("-", $modus, 3);
        if (count($modus) == 1) {
            $modus[1] = "C";
        }

        $this->runde = 0;
        $this->schiedsrichter = new Schiedsrichter("aSchiedsrichter", null);
        $this->team1 = new Team($modus[0]);
        $this->team2 = new Team($modus[1]);
        $this->team1->setGegner($this->team2);
        $this->team2->setGegner($this->team1);
        $this->quaffelSpieler = null;
        $this->stack = array();

        $this->addStackItem(new Befehl($this->schiedsrichter, "Runde", 1), 1);
        $this->flush();
    }
}
