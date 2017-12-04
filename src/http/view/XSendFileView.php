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
	metadigit\core\http\Exception;
/**
 * XSendFile View
 * View engine to output a file using Apache/Nginx X-Sendfile special header.
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class XSendFileView implements \metadigit\core\http\ViewInterface {
	use \metadigit\core\CoreTrait;

	function render(Request $Req, Response $Res, $resource=null, array $options=null) {
		if(!defined('XSENDFILE_PATH')) throw new Exception(261);
		if(!defined('XSENDFILE_URL')) throw new Exception(262);
		if(!file_exists(XSENDFILE_PATH.$resource)) throw new Exception(201, ['X-SendFile', XSENDFILE_PATH.$resource]);
		sys::trace(LOG_DEBUG, T_INFO, 'file: '.XSENDFILE_PATH.$resource);
		$fileName = $options['fileName'] ?? pathinfo(XSENDFILE_PATH.$resource, PATHINFO_FILENAME);
		$Res->reset();
		header('Content-Type: '.((new \finfo(FILEINFO_MIME_TYPE))->file(XSENDFILE_PATH.$resource)));
		header('Content-Disposition: attachment; filename='.$fileName.'.'.pathinfo(XSENDFILE_PATH.$resource, PATHINFO_EXTENSION));
		// Apache / Nginx switch
		if(function_exists('apache_get_version'))
			$resource = XSENDFILE_PATH.$resource;
		else
			$resource = XSENDFILE_URL.$resource;
		sys::trace(LOG_DEBUG, T_INFO, 'X-Sendfile: '.$resource);
		header('X-Accel-Redirect: '.$resource);
		header('X-Sendfile: '.$resource);
	}
}
