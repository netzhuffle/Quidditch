<?php

namespace Netzhuffle\MainChat\Quidditch;

class Jaeger extends Spieler {
	protected function act($befehl) {
		parent::act($befehl);
	}

	public function react($befehl) {
		parent::react($befehl);
	}
	
	protected function hasQuaffel() {
		return Quidditch::getInstance()->quaffelSpieler == $this->name;
	}
}
?>