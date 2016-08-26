<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\web\view;
use function metadigit\core\trace;
use metadigit\core\http\Request,
	metadigit\core\http\Response,
	metadigit\core\web\Exception;
/**
 * XSendFile View
 * View engine to output a file using Apache/Nginx X-Sendfile special header.
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class XSendFileView implements \metadigit\core\web\ViewInterface {
	use \metadigit\core\CoreTrait;

	function render(Request $Req, Response $Res, $resource) {
		if(!file_exists($resource)) throw new Exception(201, ['X-SendFile', $resource]);
		TRACE and trace(LOG_DEBUG, TRACE_DEFAULT, 'file: '.$resource);
		$saveName = $Res->get('saveName') ?: pathinfo($resource, PATHINFO_FILENAME);
		$Res->reset();
		$Res->setContentType((new \finfo(FILEINFO_MIME_TYPE))->file($resource));
		header('Content-Disposition: attachment; filename='.$saveName.'.'.pathinfo($resource, PATHINFO_EXTENSION));
		switch($_SERVER['SERVER_SOFTWARE']) {
			case (strpos($_SERVER['SERVER_SOFTWARE'], 'nginx')!==false):
				$resource = str_replace(\metadigit\core\PUBLIC_DIR, '/', $resource);
				$resource = str_replace(\metadigit\core\DATA_DIR, '/', $resource);
				break;
		}
		TRACE and trace(LOG_DEBUG, TRACE_DEFAULT, 'X-Sendfile: '.$resource);
		header('X-Accel-Redirect: '.$resource);
		header('X-Sendfile: '.$resource);
	}
}
