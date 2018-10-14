<?php
namespace test\console\view;
use renovant\core\console\Request,
	renovant\core\console\Response,
	renovant\core\console\view\PhpView;

class PhpViewTest extends \PHPUnit\Framework\TestCase {

	function testConstructor() {
		$PhpView = new PhpView;
		$this->assertInstanceOf(PhpView::class, $PhpView);
		return $PhpView;
	}

	/**
	 * @depends testConstructor
	 * @param PhpView $PhpView
	 * @throws \renovant\core\console\Exception
	 */
	function testRender(PhpView $PhpView) {
		$this->expectOutputRegex('/<title>index<\/title>/');
		$Req = new Request;
		$Res = new Response;
		$PhpView->render($Req, $Res, TEST_DIR.'/console/templates/index');
		$Res->send();
	}
}
