<?php
namespace mock\util\reflection;
/**
 * MockClass used for testing metadigit\core\util\reflection\*
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class Mock {

	/*
	 * Constant TEST
	 */
	const TEST = 1;
	/** Var 1
	 * var 1 description
	 * @var string */
	public $var1;
	/** Var 2
	 * @var string */
	protected $var2;
	/** Var 3
	 * @var string */
	private $var3;
	/**
	 * Test method 1
	 * @param integer $id
	 * @param string $name
	 */
	function method1($id, $name='') {

	}

	/** Method 2
	 * method 2 line 2
	 *
	 * method 2 line 4
	 * @param $id
	 * @param $test
	 */
	function method2($id, $test) {

	}

	/**
	 * @tag1 foo bar
	 * @tag1(foo=13, bar=289)
	 * @tag2(key="id", name="John Doo", level=5, active)
	 * @tag3(regex="/[0-9]{1,}/")
	 */
	function functionTag() {

	}
}