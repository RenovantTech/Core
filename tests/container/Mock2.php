<?php
namespace test\container;

class Mock2  {
	const ACL_SKIP = true;

	protected $Child;

	protected $name;

	function __construct($name, $Child) {
		$this->name = $name;
		$this->Child = $Child;
	}

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
