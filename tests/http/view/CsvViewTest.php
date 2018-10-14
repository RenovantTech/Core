<?php
namespace test\http\view;
use renovant\core\http\Request,
	renovant\core\http\Response,
	renovant\core\http\view\CsvView;

class CsvViewTest extends \PHPUnit\Framework\TestCase {

	function testConstructor() {
		$CsvView = new CsvView;
		$this->assertInstanceOf(CsvView::class, $CsvView);
		return $CsvView;
	}

	/**
	 * @depends testConstructor
	 * @param CsvView $CsvView
	 * @throws \renovant\core\http\Exception
	 */
	function testRender(CsvView $CsvView) {
		ob_start();
		$Req = new Request;
		$Res = new Response;
		$Req->setAttribute('RESOURCES_DIR', TEST_DIR);
		$Res->set('data', [
			['name'=>'John', 'surname'=>'Red', 'age'=>23],
			['name'=>'Robert', 'surname'=>'Brown', 'age'=>18],
			['name'=>'Alistar', 'surname'=>'Green', 'age'=>24]
		]);
		$CsvView->render($Req, $Res, '/http/templates/csv-test');
		$output = ob_get_clean();
		$this->assertRegExp('/"Surname","Age"/', $output);
		$this->assertRegExp('/"GREEN","24"/', $output);
	}

	/**
	 * @depends testConstructor
	 * @expectedException \renovant\core\http\Exception
	 * @expectedExceptionCode 201
	 * @param CsvView $CsvView
	 */
	function testRenderException1(CsvView $CsvView) {
		$Req = new Request;
		$Res = new Response;
		$Req->setAttribute('RESOURCES_DIR', TEST_DIR);
		$CsvView->render($Req, $Res, '/http/templates/not-exists');
	}

	/**
	 * @depends testConstructor
	 * @expectedException \renovant\core\http\Exception
	 * @expectedExceptionCode 202
	 * @param CsvView $CsvView
	 */
	function testRenderException2(CsvView $CsvView) {
		$Req = new Request;
		$Res = new Response;
		$Req->setAttribute('RESOURCES_DIR', TEST_DIR);
		$CsvView->render($Req, $Res, '/http/templates/csv-test');
	}

	/**
	 * @depends testConstructor
	 * @expectedException \renovant\core\http\Exception
	 * @expectedExceptionCode 203
	 * @param CsvView $CsvView
	 */
	function testRenderException3(CsvView $CsvView) {
		$Req = new Request;
		$Res = new Response;
		$Req->setAttribute('RESOURCES_DIR', TEST_DIR);
		$Res->set('data', 'foo');
		$CsvView->render($Req, $Res, '/http/templates/csv-test');
	}
}
