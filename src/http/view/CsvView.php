<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\http\view;
use const metadigit\core\trace\T_INFO;
use function metadigit\core\trace;
use metadigit\core\http\Request,
	metadigit\core\http\Response,
	metadigit\core\util\csv\CsvWriter,
	metadigit\core\http\Exception;
/**
 * CSV View
 * It outputs a CSV file to the client.
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class CsvView implements \metadigit\core\http\ViewInterface {
	use \metadigit\core\CoreTrait;

	const CONTENT_TYPE = 'text/csv';
	/** template suffix */
	const TEMPLATE_SUFFIX = '.csv.php';
	/** php template path
	 * @var string */
	static private $template;

	function render(Request $Req, Response $Res, $resource) {
		self::$template = $Req->getAttribute('RESOURCES_DIR').$resource.static::TEMPLATE_SUFFIX;
		if(!file_exists(self::$template)) throw new Exception(201, ['CSV Template', self::$template]);
		trace(LOG_DEBUG, T_INFO, 'template: '.self::$template);
		$saveName = $Res->get('saveName') ?: pathinfo($resource, PATHINFO_FILENAME);
		$Res->setContentType(self::CONTENT_TYPE);
		header('Content-Disposition: attachment; filename='.$saveName.'.csv');
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
