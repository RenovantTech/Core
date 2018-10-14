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
 * Writes logs to a buffer
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class BufferWriter implements \metadigit\core\log\LogWriterInterface {

	protected $buffer = [];

	/**
	 * {@inheritdoc}
	 */
	function write($time, $message, $level=LOG_INFO, $facility=null) {
		$this->buffer[] = sprintf("%s [%s] %s\n\r", date('r',$time), Logger::LABELS[$level], $message);
	}
}
