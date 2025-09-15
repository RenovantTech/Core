<?php
namespace renovant\core\mailer\parser;

use renovant\core\sys;

use const renovant\core\trace\{T_ERROR, T_INFO};

class PhpParser {
	use \renovant\core\CoreTrait;

	/** Model array */
	protected static array $model;
	/** php template path */
	protected static string $template;
	protected static string $templateDir;

	public function __construct(string $templateDir) {
		self::$templateDir = $templateDir;
	}

	public function parse(string $template, array $model = []): string {
		sys::trace(LOG_DEBUG, T_INFO, 'load template "' . $template . '.php"');
		self::$template = self::$templateDir . $template . '.php';
		self::$model    = $model;
		return self::execTemplate();
	}

	protected static function execTemplate(): string {
		sys::trace(LOG_DEBUG, T_INFO, 'start PHP rendering');
		ob_start(null, 0, PHP_OUTPUT_HANDLER_STDFLAGS);
		extract(self::$model, EXTR_REFS);
		include self::$template;
		return ob_get_clean();
	}
}
