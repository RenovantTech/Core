<?php
namespace mock\container;

class Mock1  {

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
