<?php
namespace renovant\core\http\view;

use renovant\core\sys;
use renovant\core\http\{Exception, Request, Response, ViewInterface};
use renovant\core\util\excel\ExcelWriter;

use const renovant\core\trace\T_INFO;

class ExcelView implements ViewInterface {
	public const CONTENT_TYPE = 'application/vnd.ms-excel';
	/** template suffix */
	public const TEMPLATE_SUFFIX = '.xls.php';
	/** php template path
	 * @var string */
	private static $template;

	/**
	 * @param Request $Req
	 * @param Response $Res
	 * @param null $resource
	 * @param array|null $options
	 * @throws Exception
	 */
	public function render(Request $Req, Response $Res, $resource = null, array $options = null) {
		self::$template = $Req->getAttribute('RESOURCES_DIR') . $resource . static::TEMPLATE_SUFFIX;
		if (!file_exists(self::$template)) {
			throw new Exception(201, ['EXCEL Template', self::$template]);
		}
		sys::trace(LOG_DEBUG, T_INFO, 'template: ' . self::$template, null, 'sys.http.ExcelView->render');
		$fileName = $options['fileName'] ?? pathinfo($resource, PATHINFO_FILENAME);
		$Res->contentType(self::CONTENT_TYPE);
		header('Content-Disposition: attachment; filename=' . $fileName . '.xls');
		$ExcelWriter = new ExcelWriter();
		// prepare data
		if (is_null($Res->get('data'))) {
			throw new Exception(202, ['data']);
		}
		if (!is_array($data = $Res->get('data'))) {
			throw new Exception(203, ['data', 'Array']);
		}
		$ExcelWriter->setData($data);
		// prepare columns definitions
		$columns = self::execTemplate();
		foreach ($columns as $col) {
			call_user_func_array([$ExcelWriter, 'addColumn'], $col);
		}
		// send output
		$ExcelWriter->write('php://output');
	}

	/**
	 * Push templates variables into scope
	 * and include php template
	 * @throws \renovant\core\http\Exception
	 * @return array
	 */
	private static function execTemplate() {
		$columns = null;
		include self::$template;
		if (is_null($columns)) {
			throw new Exception(202, ['columns']);
		}
		if (!is_array($columns)) {
			throw new Exception(203, ['columns', 'Array']);
		}
		return $columns;
	}
}
