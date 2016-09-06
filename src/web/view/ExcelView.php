<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\web\view;
use const metadigit\core\{TRACE, TRACE_DEFAULT};
use function metadigit\core\trace;
use metadigit\core\http\Request,
	metadigit\core\http\Response,
	metadigit\core\util\excel\ExcelWriter,
	metadigit\core\web\Exception;
/**
 * Excel View
 * It outputs a XLS file to the client.
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class ExcelView implements \metadigit\core\web\ViewInterface {
	use \metadigit\core\CoreTrait;

	const CONTENT_TYPE = 'application/vnd.ms-excel';
	/** template suffix */
	const TEMPLATE_SUFFIX = '.xls.php';
	/** php template path
	 * @var string */
	static private $template;

	function render(Request $Req, Response $Res, $resource) {
		self::$template = $Req->getAttribute('RESOURCES_DIR').$resource.static::TEMPLATE_SUFFIX;
		if(!file_exists(self::$template)) throw new Exception(201, ['EXCEL Template', self::$template]);
		TRACE and trace(LOG_DEBUG, TRACE_DEFAULT, 'template: '.self::$template);
		$saveName = $Res->get('saveName') ?: pathinfo($resource, PATHINFO_FILENAME);
		$Res->setContentType(self::CONTENT_TYPE);
		header('Content-Disposition: attachment; filename='.$saveName.'.xls');
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
	 * @throws \metadigit\core\web\Exception
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
