<?php
namespace renovant\core\util\reflection;

class ReflectionMethod extends \ReflectionMethod {

	/** DocComment instance
	 * @var DocComment */
	protected $DocComment;

	/**
	 * Replacement for the original getParameters() method which makes sure
	 * that org\renovant\util\reflection\ReflectionParameter objects are returned instead of the
	 * original ReflectionParameter instances.
	 * @throws \ReflectionException
	 */
	function getParameters(): array {
		$extendedParameters = [];
		foreach (parent::getParameters() as $parameter) {
			$extendedParameters[] = new ReflectionParameter([$this->getDeclaringClass()->getName(), $this->getName()], $parameter->getName());
		}
		return $extendedParameters;
	}

	/**
	 * Checks if the doc comment of this method is tagged with the specified tag
	 */
	function hasTag(string $tagName): bool {
		return $this->getDocComment()->hasTag($tagName);
	}

	/**
	 * Returns an array of tags and their values
	 * @return array Tags and values
	 */
	function getAllTags(): array {
		return $this->getDocComment()->getAllTags();
	}

	/**
	 * Returns the values of the specified tag
	 * @throws \Exception
	 */
	function getTagValues(string $tagName): array {
		return $this->getDocComment()->getTagValues($tagName);
	}

	/**
	 * Returns an instance of the DocComment
	 */
	#[\ReturnTypeWillChange]
	function getDocComment(): DocComment {
		if (!is_object($this->DocComment)) {
			$this->DocComment = new DocComment(parent::getDocComment());
		}
		return $this->DocComment;
	}
}
