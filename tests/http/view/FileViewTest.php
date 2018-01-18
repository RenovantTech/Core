<?php
namespace test\http\view;
use metadigit\core\http\Request,
	metadigit\core\http\Response,
	metadigit\core\http\view\FileView;

class FileViewTest extends \PHPUnit\Framework\TestCase {

	function testConstructor() {
		$FileView = new FileView;
		$this->assertInstanceOf('metadigit\core\http\view\FileView', $FileView);
		return $FileView;
	}

	/**
	 * @depends testConstructor
	 * @param FileView $FileView
	 * @throws \metadigit\core\http\Exception
	 */
	function testRender(FileView $FileView) {
		ob_start();
		$Req = new Request;
		$Res = new Response;
		$FileView->render($Req, $Res, TEST_DIR.'/http/templates/test.txt');
		$output = ob_get_clean();
		$this->assertRegExp('/Hello America/', $output);
	}

	/**
	 * @depends testConstructor
	 * @expectedException \metadigit\core\http\Exception
	 * @expectedExceptionCode 201
	 * @param FileView $FileView
	 */
	function testRenderException(FileView $FileView) {
		$Req = new Request;
		$Res = new Response;
		$FileView->render($Req, $Res, TEST_DIR.'/http/templates/not-exists.txt');
		$Res->send();
	}
}
