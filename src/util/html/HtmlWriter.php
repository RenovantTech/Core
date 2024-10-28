<?php
namespace renovant\core\util\html;

use renovant\core\sys;

class HtmlWriter {
	use \renovant\core\CoreTrait;

	public const ITERATE_ARRAY  = 1;
	public const ITERATE_OBJECT = 2;
	/** Data Iterator mode */
	protected int $iteratorMode = self::ITERATE_ARRAY;
	/** Data store */
	protected array $_data = [];
	/** Columns labels */
	protected array $_labels = [];
	/** Data store indexes for each column */
	protected array $_indexes = [];
	/** Callback functions to render each column */
	protected array $_callbacks = [];
	/** Data array */
	private static array $data;
	/** php template path */
	private static string $template;

	/**
	 * Set template data
	 * @param array $data template data
	 * @return HtmlWriter (fluent interface)
	 */
	public function setData(array $data) {
		self::$data = $data;
		return $this;
	}

	/**
	 * Set template path
	 * @param string $template template path
	 * @return HtmlWriter
	 * @throws HtmlException
	 */
	public function setTemplate($template) {
		if (!file_exists($template)) {
			throw new HtmlException(1, $template);
		}
		self::$template = $template;
		return $this;
	}

	/**
	 * Write HTML to file
	 * @param string $file output file
	 * @throws HtmlException
	 */
	public function write($file) {
		sys::trace(LOG_DEBUG, 1, __FUNCTION__, 'template: ' . self::$template . ' - output file: ' . $file);
		$html = self::execTemplate();
		if (!$fh = fopen($file, 'w')) {
			throw new HtmlException(3, $file);
		}
		fwrite($fh, $html);
		fclose($fh);
	}

	/**
	 * Push templates variables into scope
	 * and include php template
	 * @return string HTML output
	 * @throws HtmlException
	 */
	private static function execTemplate() {
		ob_start();
		try {
			extract(self::$data, EXTR_REFS);
			include self::$template;
			$html = ob_get_contents();
			return $html;
		} catch (\Exception $Ex) {
			throw new HtmlException(2, $Ex->getMessage());
		} finally {
			ob_end_clean();
		}
	}
}
