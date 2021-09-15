<?php
namespace renovant\core\util\reflection;

class ReflectionParameter extends \ReflectionParameter {

	/** DocComment instance
	 * @var DocComment */
	protected $DocComment;

	/**
	 * Return Parameter type as defined in DocComment @param, if available
	 * @return mixed Parameter type (boolean, string, array, object), FALSE if unavailable
	 * @throws \ReflectionException
	 */
	function getType() {
		if($tag = $this->getDocComment()->getTag('param', $this->getPosition())) {
			$exploded = explode(' ', $tag);
			if (count($exploded) >= 2) return ltrim($exploded[0], '\\');
		}
		return false;
	}

	/**
	 * Returns an instance of the DocComment
	 * @return DocComment
	 * @throws \ReflectionException
	 */
	function getDocComment() {
		if (!is_object($this->DocComment)) {
			$ReflMethod = new ReflectionMethod($this->getDeclaringClass()->getName(), $this->getDeclaringFunction()->getName());
			$this->DocComment = $ReflMethod->getDocComment();
		}
		return $this->DocComment;
	}
}
