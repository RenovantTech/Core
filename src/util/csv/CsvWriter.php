<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace renovant\core\util\csv;
use const renovant\core\trace\T_INFO;
use renovant\core\sys;
/**
 * CSV Writer
 * @author Daniele Sciacchitano <dan@renovant.tech>
 */
class CsvWriter {
	use \renovant\core\CoreTrait;

	const ITERATE_ARRAY = 1;
	const ITERATE_OBJECT = 2;
	/** CSV delimiter
	 * @var string */
	protected $delimiter = ',';
	/** CSV enclosure
	 * @var string */
	protected $enclosure = '"';
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
	 * @return CsvWriter (fluent interface)
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
	 * @return CsvWriter (fluent interface)
	 */
	function setData(array $data) {
		$this->_data = $data;
		$this->iteratorMode = (is_object($data[0])) ? self::ITERATE_OBJECT : self::ITERATE_ARRAY;
		return $this;
	}

	/**
	 * Set CSV delimiter
	 * @param string $delimiter
	 * @return CsvWriter (fluent interface)
	 */
	function setDelimiter($delimiter) {
		$this->delimiter = $delimiter;
		return $this;
	}

	/**
	 * Set CSV enclosure
	 * @param string $enclosure
	 * @return CsvWriter (fluent interface)
	 */
	function setEnclosure($enclosure) {
		$this->enclosure = $enclosure;
		return $this;
	}

	/**
	 * Write CSV to file
	 * @param string $file output file
	 */
	function write($file) {
		sys::trace(LOG_DEBUG, T_INFO, 'output file: '.$file);
		$fh = fopen($file, 'w');
		// labels
		$output = '';
		foreach($this->_labels as $label) {
			$output .=  $this->enclosure.$label.$this->enclosure.$this->delimiter;
		}
		fwrite($fh, substr($output,0,-1).chr(10));
		// data
		$length = count($this->_labels);
		$outputFunc = ($this->iteratorMode == self::ITERATE_ARRAY) ? 'outputArray' : 'outputObject';
		foreach($this->_data as $data) {
			$output = '';
			for($i = 0; $i<$length; $i++) {
				$output .=  $this->enclosure.$this->$outputFunc($data, $i).$this->enclosure.$this->delimiter;
			}
			fwrite($fh, substr($output,0,-1).chr(10));
		}
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
