<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\http\view;
use const metadigit\core\trace\T_INFO;
use metadigit\core\sys,
	metadigit\core\http\Request,
	metadigit\core\http\Response,
	metadigit\core\util\excel\ExcelWriter,
	metadigit\core\http\Exception;
/**
 * Excel View
 * It outputs a XLS file to the client.
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class ExcelView implements \metadigit\core\http\ViewInterface {
	use \metadigit\core\CoreTrait;

	const CONTENT_TYPE = 'application/vnd.ms-excel';
	/** template suffix */
	const TEMPLATE_SUFFIX = '.xls.php';
	/** php template path
	 * @var string */
	static private $template;

	function render(Request $Req, Response $Res, $resource=null, array $options=null) {
		self::$template = $Req->getAttribute('RESOURCES_DIR').$resource.static::TEMPLATE_SUFFIX;
		if(!file_exists(self::$template)) throw new Exception(201, ['EXCEL Template', self::$template]);
		sys::trace(LOG_DEBUG, T_INFO, 'template: '.self::$template);
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
	 * @throws \metadigit\core\http\Exception
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
