<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\util\html;
use metadigit\core\sys;
/**
 * HTML Writer
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class HtmlWriter {
	use \metadigit\core\CoreTrait;

	const ITERATE_ARRAY = 1;
	const ITERATE_OBJECT = 2;
	/** Data Iterator mode
	 * @var integer */
	protected $iteratorMode = self::ITERATE_ARRAY;
	/** Data store
	 * @var array */
	protected $_data = [];
	/** Columns labels
	 * @var array */
	protected $_labels = [];
	/** Data store indexes for each column
	 * @var array */
	protected $_indexes = [];
	/** Callback functions to render each column
	 * @var array */
	protected $_callbacks = [];
	/** Data array
	 * @var array */
	static private $data;
	/** php template path
	 * @var string */
	static private $template;

	/**
	 * Set template data
	 * @param array $data template data
	 * @return HtmlWriter (fluent interface)
	 */
	function setData(array $data) {
		self::$data = $data;
		return $this;
	}

	/**
	 * Set template path
	 * @param string $template template path
	 * @return HtmlWriter
	 * @throws HtmlException
	 */
	function setTemplate($template) {
		if(!file_exists($template)) throw new HtmlException(1, $template);
		self::$template = $template;
		return $this;
	}

	/**
	 * Write HTML to file
	 * @param string $file output file
	 * @throws HtmlException
	 */
	function write($file) {
		sys::trace(LOG_DEBUG, 1, __FUNCTION__, 'template: '.self::$template.' - output file: '.$file);
		$html = self::execTemplate();
		if(!$fh = fopen($file, 'w')) throw new HtmlException(3, $file);
		fwrite($fh, $html);
		fclose($fh);
	}

	/**
	 * Push templates variables into scope
	 * and include php template
	 * @return string HTML output
	 * @throws HtmlException
	 */
	static private function execTemplate() {
		ob_start();
		try {
			extract(self::$data, EXTR_REFS);
			include(self::$template);
			$html = ob_get_contents();
			ob_end_clean();
			return $html;
		} catch (\Exception $Ex) {
			ob_end_clean();
			throw new HtmlException(2, $Ex->getMessage());
		}
	}
}
