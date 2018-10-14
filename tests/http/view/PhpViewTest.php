<?php
namespace test\http\view;
use renovant\core\http\Request,
	renovant\core\http\Response,
	renovant\core\http\view\PhpView;

class PhpViewTest extends \PHPUnit\Framework\TestCase {

	function testConstructor() {
		$PhpView = new PhpView;
		$this->assertInstanceOf('renovant\core\http\view\PhpView', $PhpView);
		return $PhpView;
	}

	/**
	 * @depends testConstructor
	 * @param PhpView $PhpView
	 * @throws \renovant\core\http\Exception
	 */
	function testRender(PhpView $PhpView) {
		ob_start();
		$Req = new Request;
		$Res = new Response;
		$PhpView->render($Req, $Res, TEST_DIR.'/http/templates/index');
		$output = ob_get_clean();
		$this->assertRegExp('/<title>index<\/title>/', $output);
	}
}
