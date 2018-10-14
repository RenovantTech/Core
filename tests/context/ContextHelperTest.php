<?php
namespace test\context;
use renovant\core\context\ContextHelper;

class ContextHelperTest extends \PHPUnit\Framework\TestCase {

	function testGetAllContexts() {
		$contexts = ContextHelper::getAllContexts();
		$this->assertCount(11, $contexts);
	}
}
