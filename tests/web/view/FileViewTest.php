<?php
namespace test\web\view;
use metadigit\core\http\Request,
	metadigit\core\http\Response,
	metadigit\core\web\view\FileView;

class FileViewTest extends \PHPUnit_Framework_TestCase {

	function testConstructor() {
		$FileView = new FileView;
		$this->assertInstanceOf('metadigit\core\web\view\FileView', $FileView);
		return $FileView;
	}

	/**
	 * @depends testConstructor
	 */
	function testRender(FileView $FileView) {
		$this->expectOutputRegex('/Hello America/');
		$Req = new Request;
		$Res = new Response;
		$FileView->render($Req, $Res, MOCK_DIR.'/web/templates/test.txt');
		$Res->send();
	}

	/**
	 * @depends testConstructor
	 * @expectedException \metadigit\core\web\Exception
	 * @expectedExceptionCode 201
	 */
	function testRenderException(FileView $FileView) {
		$Req = new Request;
		$Res = new Response;
		$FileView->render($Req, $Res, MOCK_DIR.'/web/templates/not-exists.txt');
		$Res->send();
	}
}