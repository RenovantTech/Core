<?php
namespace test\container;

class Mock1  {
	const ACL_SKIP = true;

	protected $Child;

	protected $name;

	protected $numbers = [];

	protected $preferences = [];

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
