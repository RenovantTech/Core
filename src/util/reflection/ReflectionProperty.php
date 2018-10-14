<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace metadigit\core\util\reflection;

class ReflectionProperty extends \ReflectionProperty {

	/** DocComment instance
	 * @var DocComment */
	protected $DocComment;

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
