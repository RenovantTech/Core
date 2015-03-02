<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\web\view;
use metadigit\core\http\Request,
	metadigit\core\http\Response,
	metadigit\core\web\Exception;
/**
 * File View
 * It outputs a file to the client.
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class FileView implements \metadigit\core\web\ViewInterface {
	use \metadigit\core\CoreTrait;

	function render(Request $Req, Response $Res, $resource) {
		if(!file_exists($resource)) throw new Exception(201, ['File', $resource]);
		$this->trace(LOG_DEBUG, 1, __FUNCTION__, 'file: '.$resource);
		$saveName = $Res->get('saveName') ?: pathinfo($resource, PATHINFO_FILENAME);
		$Res->setContentType((new \finfo(FILEINFO_MIME_TYPE))->file($resource));
		header('Content-Disposition: attachment; filename='.$saveName.'.'.pathinfo($resource, PATHINFO_EXTENSION));
		header('Content-Length: '.filesize($resource));
		readfile($resource);
	}
}
