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
 * Writes logs to a buffer
 * @author Daniele Sciacchitano <dan@renovant.tech>
 */
class BufferWriter implements \renovant\core\log\LogWriterInterface {

	protected $buffer = [];

	/**
	 * {@inheritdoc}
	 */
	function write($time, $message, $level=LOG_INFO, $facility=null) {
		$this->buffer[] = sprintf("%s [%s] %s\n\r", date('r',$time), Logger::LABELS[$level], $message);
	}
}
