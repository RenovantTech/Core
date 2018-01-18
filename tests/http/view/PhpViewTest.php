<?php
namespace test\http\view;
use metadigit\core\http\Request,
	metadigit\core\http\Response,
	metadigit\core\http\view\PhpView;

class PhpViewTest extends \PHPUnit\Framework\TestCase {

	function testConstructor() {
		$PhpView = new PhpView;
		$this->assertInstanceOf('metadigit\core\http\view\PhpView', $PhpView);
		return $PhpView;
	}

	/**
	 * @depends testConstructor
	 * @param PhpView $PhpView
	 * @throws \metadigit\core\http\Exception
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
