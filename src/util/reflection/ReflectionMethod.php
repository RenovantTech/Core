<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace metadigit\core\util\reflection;

class ReflectionMethod extends \ReflectionMethod {

	/** DocComment instance
	 * @var DocComment */
	protected $DocComment;

	/**
	 * Replacement for the original getParameters() method which makes sure
	 * that org\metadigit\util\reflection\ReflectionParameter objects are returned instead of the
	 * original ReflectionParameter instances.
	 * @return ReflectionParameter[] Parameter reflection objects of the parameters of this method
	 * @throws \ReflectionException
	 */
	function getParameters() {
		$extendedParameters = [];
		foreach (parent::getParameters() as $parameter) {
			$extendedParameters[] = new ReflectionParameter([$this->getDeclaringClass()->getName(), $this->getName()], $parameter->getName());
		}
		return $extendedParameters;
	}

	/**
	 * Checks if the doc comment of this method is tagged with the specified tag
	 * @param string $tagName Tag name to check for
	 * @return boolean TRUE if such a tag has been defined, otherwise FALSE
	 */
	function hasTag($tagName) {
		return $this->getDocComment()->hasTag($tagName);
	}

	/**
	 * Returns an array of tags and their values
	 * @return array Tags and values
	 */
	function getAllTags() {
		return $this->getDocComment()->getAllTags();
	}

	/**
	 * Returns the values of the specified tag
	 * @param string $tagName Tag name to check for
	 * @return array Values of the given tag
	 * @throws \Exception
	 */
	function getTagValues($tagName) {
		return $this->getDocComment()->getTagValues($tagName);
	}

	/**
	 * Returns an instance of the DocComment
	 * @return DocComment
	 */
	function getDocComment() {
		if (!is_object($this->DocComment)) {
			$this->DocComment = new DocComment(parent::getDocComment());
		}
		return $this->DocComment;
	}
}
