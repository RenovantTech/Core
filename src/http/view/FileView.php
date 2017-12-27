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
	metadigit\core\http\Exception,
	metadigit\core\http\ViewInterface;
/**
 * File View
 * It outputs a file to the client.
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class FileView implements ViewInterface {
	use \metadigit\core\CoreTrait;

	/**
	 * @param Request $Req
	 * @param Response $Res
	 * @param null $resource
	 * @param array|null $options
	 * @throws Exception
	 */
	function render(Request $Req, Response $Res, $resource=null, array $options=null) {
		if(!file_exists($resource)) throw new Exception(201, ['File', $resource]);
		sys::trace(LOG_DEBUG, T_INFO, 'file: '.$resource);
		$fileName = $options['fileName'] ?? pathinfo($resource, PATHINFO_FILENAME);
		$Res->contentType((new \finfo(FILEINFO_MIME_TYPE))->file($resource));
		header('Content-Disposition: attachment; filename='.$fileName.'.'.pathinfo($resource, PATHINFO_EXTENSION));
		header('Content-Length: '.filesize($resource));
		readfile($resource);
	}
}
