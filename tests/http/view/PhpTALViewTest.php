<?php
namespace test\http\view;
use renovant\core\http\Request,
	renovant\core\http\Response,
	renovant\core\http\view\PhpTALView;

class PhpTALViewTest extends \PHPUnit\Framework\TestCase {

	function testConstructor() {
		$PhpTALView = new PhpTALView;
		$this->assertInstanceOf('renovant\core\http\view\PhpTALView', $PhpTALView);
		return $PhpTALView;
	}

	/**
	 * @depends testConstructor
	 * @param PhpTALView $PhpTALView
	 * @throws \renovant\core\http\Exception
	 * @throws \PHPTAL_ConfigurationException
	 */
	function testRender(PhpTALView $PhpTALView) {
		ob_start();
		$Req = new Request;
		$Res = new Response;
		$PhpTALView->render($Req, $Res, TEST_DIR.'/http/templates/index');
		$output = ob_get_clean();
		$this->assertMatchesRegularExpression('/<title>index<\/title>/', $output);
	}
}
