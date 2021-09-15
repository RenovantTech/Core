<?php
namespace renovant\core\http\view;
use const renovant\core\trace\T_INFO;
use renovant\core\sys,
	renovant\core\http\Request,
	renovant\core\http\Response,
	renovant\core\http\Exception,
	renovant\core\http\ViewInterface;
class FileView implements ViewInterface {

	/**
	 * @param Request $Req
	 * @param Response $Res
	 * @param null $resource
	 * @param array|null $options
	 * @throws Exception
	 */
	function render(Request $Req, Response $Res, $resource=null, array $options=null) {
		if(!file_exists($resource)) throw new Exception(201, ['File', $resource]);
		sys::trace(LOG_DEBUG, T_INFO, 'file: '.$resource, null, 'sys.http.FileView->render');
		$fileName = $options['fileName'] ?? pathinfo($resource, PATHINFO_FILENAME);
		$Res->contentType((new \finfo(FILEINFO_MIME_TYPE))->file($resource));
		header('Content-Disposition: attachment; filename='.$fileName.'.'.pathinfo($resource, PATHINFO_EXTENSION));
		header('Content-Length: '.filesize($resource));
		readfile($resource);
	}
}
