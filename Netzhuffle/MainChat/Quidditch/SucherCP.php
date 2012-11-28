<?php

namespace Netzhuffle\MainChat\Quidditch;

class SucherCP extends Sucher {
	protected function act($befehl) {
		parent::act($befehl);
	}

	public function react($befehl) {
		parent::react($befehl);
		$quidditch = Quidditch::getInstance();
		if($befehl->befehl == "Positionsucher") {
			$feld = mt_rand(0, 1) * 2; // = $feld entweder 0 oder 2
			$this->delay(2, $quidditch->feldernamen[$feld]);
		}
	}
}
?>