<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace renovant\core\log\writer;
use renovant\core\log\Logger;
/**
 * Writes logs to file tree
 * @author Daniele Sciacchitano <dan@renovant.tech>
 */
class FileTreeWriter implements \renovant\core\log\LogWriterInterface {

	const DEFAULT_FILENAME = 'system.log';
	/** tree base directory
	 * @var string */
	protected $directory;
	/** log file path
	 * @var string */
	protected $filename;
	/** File handle
	 * @var resource */
	private $_fh;

	/**
	 * @param string $filename
	 * @param string $directory tree root directory, default to renovant\core\LOG_DIR
	 */
	function __construct($filename=self::DEFAULT_FILENAME, $directory=\renovant\core\LOG_DIR) {
		$directory = rtrim($directory,'/');
		if(!file_exists($directory)) mkdir($directory, 0770, true);
		$this->directory = $directory;
		$this->filename = $filename;
	}

	function __destruct() {
		if(!is_null($this->_fh)) fclose($this->_fh);
	}

	/**
	 * {@inheritdoc}
	 */
	function write($time, $message, $level=LOG_INFO, $facility=null) {
		if(is_null($this->_fh)) {
			if(!file_exists($this->directory.date('/Y/m/d'))) mkdir($this->directory.date('/Y/m/d'), 0770, true);
			$this->_fh = fopen($this->directory.date('/Y/m/d/').$this->filename, 'a', 0);
		}
		if($facility) $message = $facility.': '.$message;
		fwrite($this->_fh, sprintf('%s [%s] %s'.EOL, date('r',$time), Logger::LABELS[$level], $message));
	}
}
