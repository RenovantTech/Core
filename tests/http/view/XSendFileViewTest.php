<?php
namespace test\http\view;
use renovant\core\http\Request,
	renovant\core\http\Response,
	renovant\core\http\view\XSendFileView;

class XSendFileViewTest extends \PHPUnit\Framework\TestCase {

	function testConstructor() {
		$XSendFileView = new XSendFileView;
		$this->assertInstanceOf('renovant\core\http\view\XSendFileView', $XSendFileView);
		return $XSendFileView;
	}

	/**
	 * @depends testConstructor
	 * @param XSendFileView $XSendFileView
	 * @throws \renovant\core\http\Exception
	 */
	function testRender(XSendFileView $XSendFileView) {
		header_remove();
		$Req = new Request;
		$Res = new Response;
		$XSendFileView->render($Req, $Res, 'xsendfile.txt');
		$headers = headers_list();
//		$this->assertContains('X-Accel-Redirect: '.TEST_DIR.'/http/templates/test.txt', $headers);
//		$this->assertContains('X-Sendfile: '.TEST_DIR.'/http/templates/test.txt', $headers);
		$this->assertEquals(0, $Res->getSize());
		header_remove();
	}

	/**
	 * @depends testConstructor
	 * @param XSendFileView $XSendFileView
	 */
	function testRenderException(XSendFileView $XSendFileView) {
		$this->expectExceptionCode(201);
		$this->expectException(\renovant\core\http\Exception::class);
		$Req = new Request;
		$Res = new Response;
		$XSendFileView->render($Req, $Res, 'not-exists.txt');
	}
}
