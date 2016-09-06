<?php
namespace mock;

class GlobalMock  {
	const ACL_SKIP = true;

	protected $Child;

	protected $name;

	function hello() {
		return 'Hello';
	}

	function name() {
		return $this->name;
	}

	function getChild() {
		return $this->Child;
	}

	function increment(&$var) {
		$var++;
	}

	function onEvent($Ev) {
		global $var;
		$var++;
	}
}
