<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\util\excel;
use const metadigit\core\{TRACE, TRACE_DEFAULT};
use function metadigit\core\trace;
/**
 * Excel Writer
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class ExcelWriter {
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

	/**
	 *
	 * Add a column
	 * @param string $label column label
	 * @param mixed $dataIndex data store index, can be numeric or string
	 * @param callback $callback function to render column value
	 * @return ExcelWriter (fluent interface)
	 */
	function addColumn($label, $dataIndex, $callback=null) {
		$this->_labels[] = $label;
		$this->_indexes[] = $dataIndex;
		$this->_callbacks[] = $callback;
		return $this;
	}

	/**
	 * Set data store (replace)
	 * @param array $data Data store
	 * @return ExcelWriter (fluent interface)
	 */
	function setData(array $data) {
		$this->_data = $data;
		$this->iteratorMode = (isset($data[0]) && is_object($data[0])) ? self::ITERATE_OBJECT : self::ITERATE_ARRAY;
		return $this;
	}

	/**
	 * Write Excel to file
	 * @param string $file output file
	 */
	function write($file) {
		TRACE and trace(LOG_DEBUG, TRACE_DEFAULT, 'output file: '.$file);
		$fh = fopen($file, 'w');
		// header
		$output = '<table>'.chr(10);
		// labels
		$output .= '<tr>'.chr(10);
		foreach($this->_labels as $label) {
			$output .=  chr(9).'<th nowrap>'.$label.'</th>'.chr(10);
		}
		$output .= '</tr>'.chr(10);
		fwrite($fh, $output);
		// data
		$length = count($this->_labels);
		$outputFunc = ($this->iteratorMode == self::ITERATE_ARRAY) ? 'outputArray' : 'outputObject';
		foreach($this->_data as $data) {
			$output = '<tr>'.chr(10);
			for($i = 0; $i<$length; $i++) {
				$output .=  chr(9).'<td nowrap>'.$this->$outputFunc($data, $i).'</td>'.chr(10);
			}
			$output .= '</tr>'.chr(10);
			fwrite($fh, $output);
		}
		// footer
		fwrite($fh, '</table>'.chr(10));
		fclose($fh);
	}

	protected function outputArray($data, $i) {
		$value = $data[$this->_indexes[$i]];
		if(!is_null($cb = $this->_callbacks[$i])) $value = call_user_func($cb, $value);
		return $value;
	}

	protected function outputObject($data, $i) {
		$key = $this->_indexes[$i];
		$value = (is_callable(array($data, $key))) ? $data->$key() : $data->$key;
		if(!is_null($cb = $this->_callbacks[$i])) $value = call_user_func($cb, $value);
		return $value;
	}
}
