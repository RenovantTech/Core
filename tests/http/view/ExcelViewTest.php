<?php
namespace test\http\view;
use metadigit\core\http\Request,
	metadigit\core\http\Response,
	metadigit\core\http\view\ExcelView;

class ExcelViewTest extends \PHPUnit\Framework\TestCase {

	function testConstructor() {
		$ExcelView = new ExcelView;
		$this->assertInstanceOf('metadigit\core\http\view\ExcelView', $ExcelView);
		return $ExcelView;
	}

	/**
	 * @depends testConstructor
	 * @param ExcelView $ExcelView
	 */
	function testRender(ExcelView $ExcelView) {
		$Req = new Request;
		$Res = new Response;
		$Req->setAttribute('RESOURCES_DIR', MOCK_DIR);
		$Res->set('data', [
			['name'=>'John', 'surname'=>'Red', 'age'=>23],
			['name'=>'Robert', 'surname'=>'Brown', 'age'=>18],
			['name'=>'Alistar', 'surname'=>'Green', 'age'=>24]
		]);
		$ExcelView->render($Req, $Res, '/http/templates/excel-mock');
		$output = preg_replace('/\s+/', '', $Res->getContent());
		$this->assertRegExp('/<thnowrap>Surname<\/th><thnowrap>Age<\/th>/', $output);
		$this->assertRegExp('/<tdnowrap>GREEN<\/td><tdnowrap>24<\/td>/', $output);
	}

	/**
	 * @depends testConstructor
	 * @expectedException \metadigit\core\http\Exception
	 * @expectedExceptionCode 201
	 * @param ExcelView $ExcelView
	 */
	function testRenderException1(ExcelView $ExcelView) {
		$Req = new Request;
		$Res = new Response;
		$Req->setAttribute('RESOURCES_DIR', MOCK_DIR);
		$ExcelView->render($Req, $Res, '/http/templates/not-exists');
	}

	/**
	 * @depends testConstructor
	 * @expectedException \metadigit\core\http\Exception
	 * @expectedExceptionCode 202
	 * @param ExcelView $ExcelView
	 */
	function testRenderException2(ExcelView $ExcelView) {
		$Req = new Request;
		$Res = new Response;
		$Req->setAttribute('RESOURCES_DIR', MOCK_DIR);
		$ExcelView->render($Req, $Res, '/http/templates/excel-mock');
	}

	/**
	 * @depends testConstructor
	 * @expectedException \metadigit\core\http\Exception
	 * @expectedExceptionCode 203
	 * @param ExcelView $ExcelView
	 */
	function testRenderException3(ExcelView $ExcelView) {
		$Req = new Request;
		$Res = new Response;
		$Req->setAttribute('RESOURCES_DIR', MOCK_DIR);
		$Res->set('data', 'foo');
		$ExcelView->render($Req, $Res, '/http/templates/excel-mock');
	}
}