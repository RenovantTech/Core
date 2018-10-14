<?php
namespace test\http\view;
use renovant\core\http\Request,
	renovant\core\http\Response,
	renovant\core\http\view\FileView;

class FileViewTest extends \PHPUnit\Framework\TestCase {

	function testConstructor() {
		$FileView = new FileView;
		$this->assertInstanceOf('renovant\core\http\view\FileView', $FileView);
		return $FileView;
	}

	/**
	 * @depends testConstructor
	 * @param FileView $FileView
	 * @throws \renovant\core\http\Exception
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
	 * @expectedException \renovant\core\http\Exception
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
