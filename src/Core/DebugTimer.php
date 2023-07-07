<?php

namespace FlexForm\Core;

class DebugTimer {

	/**
	 * @var string
	 */
	private string $timerStart;

	public function __construct() {
		$this->timerStart = microtime( true ) * 1000;
	}

	/**
	 * @return string
	 */
	public function getDuration(): string {
		return ( microtime( true ) * 1000 - $this->timerStart );
	}

}