<?php
namespace renovant\core\http\view;

use renovant\core\sys;
use renovant\core\http\{Exception, Request, Response, ViewInterface};

use const renovant\core\trace\T_INFO;

class FileView implements ViewInterface {
	/**
	 * @param Request $Req
	 * @param Response $Res
	 * @param null $resource
	 * @param array|null $options
	 * @throws Exception
	 */
	public function render(Request $Req, Response $Res, $resource = null, array $options = null) {
		if (!file_exists($resource)) {
			throw new Exception(201, ['File', $resource]);
		}
		sys::trace(LOG_DEBUG, T_INFO, 'file: ' . $resource, null, 'sys.http.FileView->render');
		$fileName = $options['fileName'] ?? pathinfo($resource, PATHINFO_FILENAME);
		$Res->contentType((new \finfo(FILEINFO_MIME_TYPE))->file($resource));
		header('Content-Disposition: attachment; filename=' . $fileName . '.' . pathinfo($resource, PATHINFO_EXTENSION));
		header('Content-Length: ' . filesize($resource));
		readfile($resource);
	}
}
