<?php
namespace renovant\core\log\writer;
use renovant\core\log\Logger;
class BufferWriter implements \renovant\core\log\LogWriterInterface {

	protected $buffer = [];

	/**
	 * {@inheritdoc}
	 */
	function write($time, $message, $level=LOG_INFO, $facility=null) {
		$this->buffer[] = sprintf("%s [%s] %s\n\r", date('r',$time), Logger::LABELS[$level], $message);
	}
}
