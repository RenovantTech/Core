<?php
namespace renovant\core\util\reflection;

class ReflectionParameter extends \ReflectionParameter {

	/** DocComment instance
	 * @var DocComment */
	protected $DocComment;

	/**
	 * Return Parameter type as defined in DocComment @param, if available
	 * @return string|bool Parameter type (boolean, string, array, object), FALSE if unavailable
	 * @throws \ReflectionException
	 */
	function getDocType(): string|bool {
		if($tag = $this->getDocComment()->getTag('param', $this->getPosition())) {
			$exploded = explode(' ', $tag);
			if (count($exploded) >= 2) return ltrim($exploded[0], '\\');
		}
		return false;
	}

	/**
	 * Returns an instance of the DocComment
	 * @throws \ReflectionException
	 */
	function getDocComment(): DocComment {
		if (!is_object($this->DocComment)) {
			$RefMethod = new ReflectionMethod($this->getDeclaringClass()->getName(), $this->getDeclaringFunction()->getName());
			$this->DocComment = $RefMethod->getDocComment();
		}
		return $this->DocComment;
	}
}
