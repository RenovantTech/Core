<?php
namespace renovant\core\http\view;

use renovant\core\sys;
use renovant\core\http\{Exception, Request, Response, ViewInterface};

use const renovant\core\trace\T_INFO;

class PhpView implements ViewInterface {
	/** template suffix */
	public const TEMPLATE_SUFFIX = '.phtml';
	/** Model array
	 * @var array */
	private static $model;
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
			throw new Exception(201, ['PHP Template', self::$template]);
		}
		sys::trace(LOG_DEBUG, T_INFO, 'template: ' . self::$template, null, 'sys.http.PhpView->render');
		self::$model = $Res->getData();
		self::execTemplate();
	}

	/**
	 * Push templates variables into scope
	 * and include php template
	 * @return void
	 */
	private static function execTemplate() {
		extract(self::$model, EXTR_REFS);
		include self::$template;
	}
}
