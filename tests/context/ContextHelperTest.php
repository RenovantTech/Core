<?php
namespace test\context;
use renovant\core\context\ContextHelper;

class ContextHelperTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @throws \renovant\core\container\ContainerException
	 * @throws \renovant\core\event\EventDispatcherException
	 * @throws \renovant\core\context\ContextException
	 */
	function testGetAllContexts() {
		$contexts = ContextHelper::getAllContexts();
		$this->assertCount(12, $contexts);
	}
}
