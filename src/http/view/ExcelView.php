<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace renovant\core\http\view;
use const renovant\core\trace\T_INFO;
use renovant\core\sys,
	renovant\core\http\Request,
	renovant\core\http\Response,
	renovant\core\http\Exception,
	renovant\core\http\ViewInterface,
	renovant\core\util\excel\ExcelWriter;
/**
 * Excel View
 * It outputs a XLS file to the client.
 * @author Daniele Sciacchitano <dan@renovant.tech>
 */
class ExcelView implements ViewInterface {

	const CONTENT_TYPE = 'application/vnd.ms-excel';
	/** template suffix */
	const TEMPLATE_SUFFIX = '.xls.php';
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
		if(!file_exists(self::$template)) throw new Exception(201, ['EXCEL Template', self::$template]);
		sys::trace(LOG_DEBUG, T_INFO, 'template: '.self::$template, null, 'sys.http.ExcelView->render');
		$fileName = $options['fileName'] ?? pathinfo($resource, PATHINFO_FILENAME);
		$Res->contentType(self::CONTENT_TYPE);
		header('Content-Disposition: attachment; filename='.$fileName.'.xls');
		$ExcelWriter = new ExcelWriter();
		// prepare data
		if(is_null($Res->get('data'))) throw new Exception(202, ['data']);
		if(!is_array($data = $Res->get('data'))) throw new Exception(203, ['data', 'Array']);
		$ExcelWriter->setData($data);
		// prepare columns definitions
		$columns = self::execTemplate();
		foreach($columns as $col) call_user_func_array([$ExcelWriter, 'addColumn'], $col);
		// send output
		$ExcelWriter->write('php://output');
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
