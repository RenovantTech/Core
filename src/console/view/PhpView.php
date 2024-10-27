<?php
namespace renovant\core\console\view;

use renovant\core\sys;
use renovant\core\console\{Exception, Request, Response};

use const renovant\core\trace\T_INFO;

class PhpView implements \renovant\core\console\ViewInterface {
	use \renovant\core\CoreTrait;

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
	 * @param string $resource
	 * @throws Exception
	 */
	public function render(Request $Req, Response $Res, $resource) {
		self::$template = $Req->getAttribute('RESOURCES_DIR') . $resource . static::TEMPLATE_SUFFIX;
		if (!file_exists(self::$template)) {
			throw new Exception(201, ['PHP Template', self::$template]);
		}
		sys::trace(LOG_DEBUG, T_INFO, 'render PHP template ' . self::$template, null, 'sys.console.PhpView->render');
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
