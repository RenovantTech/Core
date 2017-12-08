<?php
namespace test\http\view;
use metadigit\core\http\Request,
	metadigit\core\http\Response,
	metadigit\core\http\view\PhpTALView;

class PhpTALViewTest extends \PHPUnit\Framework\TestCase {

	function testConstructor() {
		$PhpTALView = new PhpTALView;
		$this->assertInstanceOf('metadigit\core\http\view\PhpTALView', $PhpTALView);
		return $PhpTALView;
	}

	/**
	 * @depends testConstructor
	 * @param PhpTALView $PhpTALView
	 */
	function testRender(PhpTALView $PhpTALView) {
		$this->expectOutputRegex('/<title>index<\/title>/');
		$Req = new Request;
		$Res = new Response;
		$PhpTALView->render($Req, $Res, TEST_DIR.'/http/templates/index');
		$Res->send();
	}
}
