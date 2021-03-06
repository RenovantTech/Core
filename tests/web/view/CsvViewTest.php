<?php
namespace test\web\view;
use metadigit\core\http\Request,
	metadigit\core\http\Response,
	metadigit\core\web\view\CsvView;

class CsvViewTest extends \PHPUnit\Framework\TestCase {

	function testConstructor() {
		$CsvView = new CsvView;
		$this->assertInstanceOf('metadigit\core\web\view\CsvView', $CsvView);
		return $CsvView;
	}

	/**
	 * @depends testConstructor
	 */
	function testRender(CsvView $CsvView) {
		$Req = new Request;
		$Res = new Response;
		$Req->setAttribute('RESOURCES_DIR', MOCK_DIR);
		$Res->set('data', [
			['name'=>'John', 'surname'=>'Red', 'age'=>23],
			['name'=>'Robert', 'surname'=>'Brown', 'age'=>18],
			['name'=>'Alistar', 'surname'=>'Green', 'age'=>24]
		]);
		$CsvView->render($Req, $Res, '/web/templates/csv-mock');
		$output = $Res->getContent();
		$this->assertRegExp('/"Surname","Age"/', $output);
		$this->assertRegExp('/"GREEN","24"/', $output);
	}

	/**
	 * @depends testConstructor
	 * @expectedException \metadigit\core\web\Exception
	 * @expectedExceptionCode 201
	 */
	function testRenderException1(CsvView $CsvView) {
		$Req = new Request;
		$Res = new Response;
		$Req->setAttribute('RESOURCES_DIR', MOCK_DIR);
		$CsvView->render($Req, $Res, '/web/templates/not-exists');
	}

	/**
	 * @depends testConstructor
	 * @expectedException \metadigit\core\web\Exception
	 * @expectedExceptionCode 202
	 */
	function testRenderException2(CsvView $CsvView) {
		$Req = new Request;
		$Res = new Response;
		$Req->setAttribute('RESOURCES_DIR', MOCK_DIR);
		$CsvView->render($Req, $Res, '/web/templates/csv-mock');
	}

	/**
	 * @depends testConstructor
	 * @expectedException \metadigit\core\web\Exception
	 * @expectedExceptionCode 203
	 */
	function testRenderException3(CsvView $CsvView) {
		$Req = new Request;
		$Res = new Response;
		$Req->setAttribute('RESOURCES_DIR', MOCK_DIR);
		$Res->set('data', 'foo');
		$CsvView->render($Req, $Res, '/web/templates/csv-mock');
	}
}
