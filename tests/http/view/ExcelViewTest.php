<?php
namespace test\http\view;
use renovant\core\http\Request,
	renovant\core\http\Response,
	renovant\core\http\view\ExcelView;

class ExcelViewTest extends \PHPUnit\Framework\TestCase {

	function testConstructor() {
		$ExcelView = new ExcelView;
		$this->assertInstanceOf('renovant\core\http\view\ExcelView', $ExcelView);
		return $ExcelView;
	}

	/**
	 * @depends testConstructor
	 * @param ExcelView $ExcelView
	 * @throws \renovant\core\http\Exception
	 */
	function testRender(ExcelView $ExcelView) {
		ob_start();
		$Req = new Request;
		$Res = new Response;
		$Req->setAttribute('RESOURCES_DIR', TEST_DIR);
		$Res->set('data', [
			['name'=>'John', 'surname'=>'Red', 'age'=>23],
			['name'=>'Robert', 'surname'=>'Brown', 'age'=>18],
			['name'=>'Alistar', 'surname'=>'Green', 'age'=>24]
		]);
		$ExcelView->render($Req, $Res, '/http/templates/excel-test');
		$output = preg_replace('/\s+/', '', ob_get_clean());
		$this->assertRegExp('/<thnowrap>Surname<\/th><thnowrap>Age<\/th>/', $output);
		$this->assertRegExp('/<tdnowrap>GREEN<\/td><tdnowrap>24<\/td>/', $output);
	}

	/**
	 * @depends testConstructor
	 * @expectedException \renovant\core\http\Exception
	 * @expectedExceptionCode 201
	 * @param ExcelView $ExcelView
	 */
	function testRenderException1(ExcelView $ExcelView) {
		$Req = new Request;
		$Res = new Response;
		$Req->setAttribute('RESOURCES_DIR', TEST_DIR);
		$ExcelView->render($Req, $Res, '/http/templates/not-exists');
	}

	/**
	 * @depends testConstructor
	 * @expectedException \renovant\core\http\Exception
	 * @expectedExceptionCode 202
	 * @param ExcelView $ExcelView
	 */
	function testRenderException2(ExcelView $ExcelView) {
		$Req = new Request;
		$Res = new Response;
		$Req->setAttribute('RESOURCES_DIR', TEST_DIR);
		$ExcelView->render($Req, $Res, '/http/templates/excel-test');
	}

	/**
	 * @depends testConstructor
	 * @expectedException \renovant\core\http\Exception
	 * @expectedExceptionCode 203
	 * @param ExcelView $ExcelView
	 */
	function testRenderException3(ExcelView $ExcelView) {
		$Req = new Request;
		$Res = new Response;
		$Req->setAttribute('RESOURCES_DIR', TEST_DIR);
		$Res->set('data', 'foo');
		$ExcelView->render($Req, $Res, '/http/templates/excel-test');
	}
}
