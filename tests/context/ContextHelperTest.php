<?php
namespace test\context;
use metadigit\core\context\ContextHelper;

class ContextHelperTest extends \PHPUnit_Framework_TestCase {

	function testGetAllContexts() {
		$contexts = ContextHelper::getAllContexts();
		$this->assertCount(7, $contexts);
	}
}