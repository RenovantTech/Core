<?php
namespace test\console\view;
use metadigit\core\cli\Request,
	metadigit\core\cli\Response,
	metadigit\core\console\view\PhpView;

class PhpViewTest extends \PHPUnit_Framework_TestCase {

	function testConstructor() {
		$PhpView = new PhpView;
		$this->assertInstanceOf('metadigit\core\console\view\PhpView', $PhpView);
		return $PhpView;
	}

	/**
	 * @depends testConstructor
	 */
	function testRender(PhpView $PhpView) {
		$this->expectOutputRegex('/<title>index<\/title>/');
		$Req = new Request;
		$Res = new Response;
		$PhpView->render($Req, $Res, MOCK_DIR.'/console/templates/index');
		$matcher = ['tag' => 'title', 'content' => 'index'];
		$this->assertTag($matcher, $Res->getContent(), '->assertTag() <title>index</title>');
		$Res->send();
	}
}