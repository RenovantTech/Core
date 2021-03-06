<?php
namespace test\web\view;
use metadigit\core\http\Request,
	metadigit\core\http\Response,
	metadigit\core\web\view\ExcelView;

class ExcelViewTest extends \PHPUnit\Framework\TestCase {

	function testConstructor() {
		$ExcelView = new ExcelView;
		$this->assertInstanceOf('metadigit\core\web\view\ExcelView', $ExcelView);
		return $ExcelView;
	}

	/**
	 * @depends testConstructor
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
		$ExcelView->render($Req, $Res, '/web/templates/excel-mock');
		$output = preg_replace('/\s+/', '', $Res->getContent());
		$this->assertRegExp('/<thnowrap>Surname<\/th><thnowrap>Age<\/th>/', $output);
		$this->assertRegExp('/<tdnowrap>GREEN<\/td><tdnowrap>24<\/td>/', $output);
	}

	/**
	 * @depends testConstructor
	 * @expectedException \metadigit\core\web\Exception
	 * @expectedExceptionCode 201
	 */
	function testRenderException1(ExcelView $ExcelView) {
		$Req = new Request;
		$Res = new Response;
		$Req->setAttribute('RESOURCES_DIR', MOCK_DIR);
		$ExcelView->render($Req, $Res, '/web/templates/not-exists');
	}

	/**
	 * @depends testConstructor
	 * @expectedException \metadigit\core\web\Exception
	 * @expectedExceptionCode 202
	 */
	function testRenderException2(ExcelView $ExcelView) {
		$Req = new Request;
		$Res = new Response;
		$Req->setAttribute('RESOURCES_DIR', MOCK_DIR);
		$ExcelView->render($Req, $Res, '/web/templates/excel-mock');
	}

	/**
	 * @depends testConstructor
	 * @expectedException \metadigit\core\web\Exception
	 * @expectedExceptionCode 203
	 */
	function testRenderException3(ExcelView $ExcelView) {
		$Req = new Request;
		$Res = new Response;
		$Req->setAttribute('RESOURCES_DIR', MOCK_DIR);
		$Res->set('data', 'foo');
		$ExcelView->render($Req, $Res, '/web/templates/excel-mock');
	}
}
