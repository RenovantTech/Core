<?php
namespace test\web\view;
use metadigit\core\http\Request,
	metadigit\core\http\Response,
	metadigit\core\web\view\PhpView;

class PhpViewTest extends \PHPUnit_Framework_TestCase {

	function testConstructor() {
		$PhpView = new PhpView;
		$this->assertInstanceOf('metadigit\core\web\view\PhpView', $PhpView);
		return $PhpView;
	}

	/**
	 * @depends testConstructor
	 */
	function testRender(PhpView $PhpView) {
		$this->expectOutputRegex('/<title>index<\/title>/');
		$Req = new Request;
		$Res = new Response;
		$PhpView->render($Req, $Res, MOCK_DIR.'/web/templates/index');
		$matcher = ['tag' => 'title', 'content' => 'index'];
		$this->assertTag($matcher, $Res->getContent(), '->assertTag() <title>index</title>');
		$Res->send();
	}
}