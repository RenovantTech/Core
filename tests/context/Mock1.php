<?php
namespace test\context;

class Mock1 {
	const ACL_SKIP = true;

	protected $prop1;
	protected $prop2;
	protected $Child;

	function __construct($prop1) {
		$this->prop1 = $prop1;
	}

	function onEvent1($Event) {
		global $var;
		$var++;
	}
	function onEvent2($Event) {
		global $var;
		$var--;
	}
	function onEvent2bis($Event) {
		global $var;
		$var--;
	}

	function getChild() {
		return $this->Child;
	}

	function getProp1() {
		return $this->prop1;
	}

	function getProp2() {
		return $this->prop2;
	}
}
