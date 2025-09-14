<?php
namespace renovant\core\mailer\parser;

use renovant\core\sys;

use const renovant\core\trace\{T_ERROR, T_INFO};

class PhpParser {
	use \renovant\core\CoreTrait;

	protected string $cacheDir;

	protected string $templateDir;

	public function parse(string $template, array $model = []): string {
		return '';
	}
}
