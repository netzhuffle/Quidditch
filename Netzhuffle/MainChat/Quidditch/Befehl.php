<?php

namespace Netzhuffle\MainChat\Quidditch;

class Befehl {
	public $wer;
	public $befehl;
	public $param;

	public function __construct($wer, $befehl, $param = null) {
		$this->wer = $this->getSpieler($wer);
		$this->befehl = $befehl;
		$this->param = $param;
	}

	private function getSpieler($name) {
		if($name instanceof Spieler) {
			return $name;
		} else {
			return Quidditch::getInstance()->getSpieler($name);
		}
	}

	public function execute() {
		if($this->wer) {
			$quidditch = Quidditch::getInstance();
			if($this->wer->canDoCommand($this)) {
				$this->wer->doCommand($this);
				foreach($quidditch->getAllSpieler() as $spieler) {
					if($spieler->name != $this->wer->name) {
						$spieler->react($this);
					}
				}
			} elseif($this->befehl == "Dice" && $this->param == null) {
				mysql_query("INSERT INTO ".DB_PREFIX."action (typ, text, userid, time) or trigger_error(mysql_error(), E_USER_ERROR) VALUES (".CODE_MESSAGE.", '/dice 2w6', $wer->id, ".floor(microtime(true) * 1000).")") or trigger_error(mysql_error(), E_USER_ERROR);
			} elseif(true) { // keine Berechtigung für Befehl
				$fehler = new Befehl($quidditch->schiedsrichter, "Write", "(Befehl $this->befehl von {$this->wer->name} momentan nicht erlaubt..)");
				$quidditch->addStackItem($fehler, 0);
			}
		}
	}
}
?>