<?php
namespace test\console\view;
use metadigit\core\cli\Request,
	metadigit\core\cli\Response,
	metadigit\core\console\view\PhpView;

class PhpViewTest extends \PHPUnit\Framework\TestCase {

	function testConstructor() {
		$PhpView = new PhpView;
		$this->assertInstanceOf('metadigit\core\console\view\PhpView', $PhpView);
		return $PhpView;
	}

	/**
	 * @depends testConstructor
	 * @param PhpView $PhpView
	 */
	function testRender(PhpView $PhpView) {
		$this->expectOutputRegex('/<title>index<\/title>/');
		$Req = new Request;
		$Res = new Response;
		$PhpView->render($Req, $Res, MOCK_DIR.'/console/templates/index');
		$Res->send();
	}
}
