<?php
namespace test\web\view;
use metadigit\core\http\Request,
	metadigit\core\http\Response,
	metadigit\core\web\view\XSendFileView;

class XSendFileViewTest extends \PHPUnit\Framework\TestCase {

	function testConstructor() {
		$XSendFileView = new XSendFileView;
		$this->assertInstanceOf('metadigit\core\web\view\XSendFileView', $XSendFileView);
		return $XSendFileView;
	}

	/**
	 * @depends testConstructor
	 * @param XSendFileView $XSendFileView
	 */
	function testRender(XSendFileView $XSendFileView) {
		header_remove();
		$Req = new Request;
		$Res = new Response;
		$XSendFileView->render($Req, $Res, 'xsendfile.txt');
		$headers = headers_list();
//		$this->assertContains('X-Accel-Redirect: '.MOCK_DIR.'/web/templates/test.txt', $headers);
//		$this->assertContains('X-Sendfile: '.MOCK_DIR.'/web/templates/test.txt', $headers);
		$this->assertEquals(0, $Res->getSize());
		header_remove();
	}

	/**
	 * @depends testConstructor
	 * @expectedException \metadigit\core\web\Exception
	 * @expectedExceptionCode 201
	 * @param XSendFileView $XSendFileView
	 */
	function testRenderException(XSendFileView $XSendFileView) {
		$Req = new Request;
		$Res = new Response;
		$XSendFileView->render($Req, $Res, 'not-exists.txt');
	}
}
