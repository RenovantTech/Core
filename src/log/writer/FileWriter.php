<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace metadigit\core\log\writer;
use metadigit\core\log\Logger;
/**
 * Writes logs to file
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class FileWriter implements \metadigit\core\log\LogWriterInterface {

	const DEFAULT_FILENAME = 'system.log';
	/** log file path
	 * @var string */
	protected $filename;
	/** File handle
	 * @var resource */
	private $_fh;

	/**
	 * @param string $filename log file path
	 */
	function __construct($filename=self::DEFAULT_FILENAME) {
		$this->filename = $filename;
		if('/'!=$this->filename[0]) $this->filename = \metadigit\core\LOG_DIR.$this->filename;
		if(!file_exists(dirname($this->filename))) mkdir(dirname($this->filename), 0700, true);
		if(!file_exists($this->filename)) touch($this->filename);
	}

	function __destruct() {
		if(!is_null($this->_fh)) fclose($this->_fh);
	}

	/**
	 * {@inheritdoc}
	 */
	function write($time, $message, $level=LOG_INFO, $facility=null) {
		if(is_null($this->_fh)) $this->_fh = fopen($this->filename, 'a', 0);
		if($facility) $message = $facility.': '.$message;
		fwrite($this->_fh, sprintf('%s [%s] %s'.EOL, date('r',$time), Logger::LABELS[$level], $message));
	}
}
