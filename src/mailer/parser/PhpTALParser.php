<?php
namespace renovant\core\mailer\parser;

use renovant\core\sys;

use const renovant\core\trace\{T_ERROR, T_INFO};

class PhpTALParser {
	use \renovant\core\CoreTrait;

	protected string $cacheDir;

	protected string $templateDir;

	public function __construct(string $templateDir, string $cacheDir) {
		$this->templateDir = $templateDir;
		$this->cacheDir    = $cacheDir;
		if (!file_exists($this->cacheDir)) {
			mkdir($this->cacheDir, 0750);
		}
	}

	public function parse(string $template, array $model = []): string {
		try {
			// setup PhpTAL
			$PhpTAL = new \PHPTAL();
			$PhpTAL->setEncoding('UTF-8');
			$PhpTAL->setOutputMode(\PHPTAL::HTML5);
			$PhpTAL->setPhpCodeDestination($this->cacheDir);

			sys::trace(LOG_DEBUG, T_INFO, 'load template "' . $template . '.html"');
			$template = file_get_contents($this->templateDir . $template . '.html');

			// fix HTML syntax
			$template = str_replace('crossorigin', 'crossorigin=""', $template);
			$template = preg_replace('/<meta ([^>]+)">/', '<meta $1" />', $template);
			$template = preg_replace('/<link ([^>]+)">/', '<link $1" />', $template);
			$template = preg_replace('/<img ([^>]+) alt ([^>]+)">/', '<img $1 alt="" $2">', $template);
			$template = preg_replace('/<img ([^>]+)">/', '<img $1" />', $template);
			$template = preg_replace('/<input ([^>]+)">/', '<input $1" />', $template);
			$template = preg_replace('/<br(\s+)">/', '<br />', $template);

			// execute
			sys::trace(LOG_DEBUG, T_INFO, 'start PhpTAL rendering ');
			$PhpTAL->setSource($template);
			foreach ($model as $k => $v) {
				$PhpTAL->set($k, $v);
			}
			return $PhpTAL->execute();
		} catch (\Exception $Ex) {
			sys::trace(LOG_DEBUG, T_ERROR, 'PhpTAL EXCEPTION: ' . $Ex->getMessage(), $Ex->getMessage());
			trigger_error($Ex->getMessage(), E_USER_WARNING);
			throw $Ex;
		}
	}
}
