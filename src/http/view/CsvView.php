<?php
namespace renovant\core\http\view;
use const renovant\core\trace\T_INFO;
use renovant\core\sys,
	renovant\core\http\Request,
	renovant\core\http\Response,
	renovant\core\http\Exception,
	renovant\core\http\ViewInterface,
	renovant\core\util\csv\CsvWriter;
class CsvView implements ViewInterface {

	const CONTENT_TYPE = 'text/csv';
	/** template suffix */
	const TEMPLATE_SUFFIX = '.csv.php';
	/** php template path
	 * @var string */
	static private $template;

	/**
	 * @param Request $Req
	 * @param Response $Res
	 * @param null $resource
	 * @param array|null $options
	 * @throws Exception
	 */
	function render(Request $Req, Response $Res, $resource=null, array $options=null) {
		self::$template = $Req->getAttribute('RESOURCES_DIR').$resource.static::TEMPLATE_SUFFIX;
		if(!file_exists(self::$template)) throw new Exception(201, ['CSV Template', self::$template]);
		sys::trace(LOG_DEBUG, T_INFO, 'template: '.self::$template, null, 'sys.http.CsvView->render');
		$fileName = $options['fileName'] ?? pathinfo($resource, PATHINFO_FILENAME);
		$Res->contentType(self::CONTENT_TYPE);
		header('Content-Disposition: attachment; filename='.$fileName.'.csv');
		$CsvWriter = new CsvWriter();
		// prepare data
		if(is_null($Res->get('data'))) throw new Exception(202, ['data']);
		if(!is_array($data = $Res->get('data'))) throw new Exception(203, ['data', 'Array']);
		$CsvWriter->setData($data);
		// prepare columns definitions
		$columns = self::execTemplate();
		foreach($columns as $col) call_user_func_array([$CsvWriter, 'addColumn'], $col);
		// send output
		$CsvWriter->write('php://output');
	}

	/**
	 * Push templates variables into scope
	 * and include php template
	 * @throws \renovant\core\http\Exception
	 * @return array
	 */
	static private function execTemplate() {
		$columns = null;
		include(self::$template);
		if(is_null($columns)) throw new Exception(202, ['columns']);
		if(!is_array($columns)) throw new Exception(203, ['columns', 'Array']);
		return $columns;
	}
}
