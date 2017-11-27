<?php
namespace test\web\view;
use metadigit\core\http\Request,
	metadigit\core\http\Response,
	metadigit\core\web\view\PhpTALView;

class PhpTALViewTest extends \PHPUnit\Framework\TestCase {

	function testConstructor() {
		$PhpTALView = new PhpTALView;
		$this->assertInstanceOf('metadigit\core\web\view\PhpTALView', $PhpTALView);
		return $PhpTALView;
	}

	/**
	 * @depends testConstructor
	 */
	function testRender(PhpTALView $PhpTALView) {
		$this->expectOutputRegex('/<title>index<\/title>/');
		$Req = new Request;
		$Res = new Response;
		$PhpTALView->render($Req, $Res, MOCK_DIR.'/web/templates/index');
		$Res->send();
	}
}
