<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace renovant\core\console;
/**
 * CLI Request.
 * @author Daniele Sciacchitano <dan@renovant.tech>
 */
class Request {

	/** Request named attributes.
	 * @var	array */
	protected $attrs = [];
	/** Request command passed by command line (plain args).
	 * @var	array */
	protected $cmd = [];
	/** Request parameters passed by command line.
	 * @var	array */
	protected $data = [];

	/**
	 * Constructor: create a new CLI Request
	 * @param array|null $args Console arguments. If not supplied, $_SERVER['argv'] will be used
	 * @param array|null $env Environment data. If not supplied, $_ENV will be used
	 */
	function __construct(array $args=null, array $env=null) {
		if(is_null($args)) $args = $_SERVER['argv'];
		array_shift($args);
		foreach($args as $arg) {
			// --foo --bar=baz
			if(substr($arg,0,2) == '--') {
				$eqPos = strpos($arg,'=');
				// --foo
				if($eqPos === false) {
					$key	= substr($arg, 2);
					$value	= isset($this->data[$key]) ? $this->data[$key] : true;
					$this->data[$key]	= $value;
				}
				// --bar=baz
				else {
					$key	= substr($arg, 2, $eqPos-2);
					$value	= substr($arg, $eqPos+1);
					$this->data[$key]	= $value;
				}
			}
			// -k=value -abc
			elseif(substr($arg,0,1) == '-') {
				// -k=value
				if(substr($arg,2,1) == '=') {
					$key			= substr($arg,1,1);
					$value			= substr($arg,3);
					$this->data[$key]	= $value;
				}
				// -abc
				else{
					$chars = str_split(substr($arg,1));
					foreach($chars as $char) {
						$key		= $char;
						$value		= isset($this->data[$key]) ? $this->data[$key] : true;
						$this->data[$key]	= $value;
					}
				}
			}
			// plain-arg
			else {
				$this->cmd[] = $arg;
			}
		}
	}

	/**
	 * Return Request command (plain args)
	 * @param int|null $i arg index, NULL to have the full command string
	 * @return string
	 */
	function CMD($i=null) {
		if(is_null($i)) return implode(' ', $this->cmd);
		else return $this->cmd[$i];
	}

	/**
	 * Return Request param
	 * @param string $p parameter name
	 * @return mixed|null
	 */
	function get($p) {
		return isset($this->data[$p]) ? $this->data[$p] : null;
	}

	function getAttribute($k) {
		return isset($this->attrs[$k]) ? $this->attrs[$k] : null;
	}

	/**
	 * Set Request param
	 * @param string $p parameter name
	 * @param mixed $v parameter value
	 */
	function set($p, $v) {
		$this->data[$p]=$v;
	}

	function setAttribute($k, $v) {
		$this->attrs[$k] = $v;
	}
}
