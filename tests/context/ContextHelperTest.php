<?php
namespace test\context;
use metadigit\core\context\ContextHelper;

class ContextHelperTest extends \PHPUnit\Framework\TestCase {

	function testGetAllContexts() {
		$contexts = ContextHelper::getAllContexts();
		$this->assertCount(9, $contexts);
	}
}
