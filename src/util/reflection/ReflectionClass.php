<?php
namespace renovant\core\util\reflection;

class ReflectionClass extends \ReflectionClass {

	/** DocComment instance
	 * @var DocComment */
	protected $DocComment;

	/**
	 * Replacement for the original getMethods() method which makes sure
	 * that org\renovant\util\reflection\ReflectionMethod objects are returned instead of the
	 * original ReflectionMethod instances.
	 * @throws \ReflectionException
	 */
	function getMethods(?int $filter = NULL): array {
		$extendedMethods = [];
		$methods = parent::getMethods($filter);
		foreach ($methods as $method) {
			$extendedMethods[] = new ReflectionMethod($this->getName(), $method->getName());
		}
		return $extendedMethods;
	}

	/**
	 * Replacement for the original getMethod() method which makes sure
	 * that renovant\core\util\reflection\ReflectionMethod objects are returned instead of the
	 * original ReflectionMethod instances.
	 * @throws \ReflectionException
	 */
	function getMethod(string $name): ReflectionMethod {
		$parentMethod = parent::getMethod($name);
		return new ReflectionMethod($this->getName(), $parentMethod->getName());
	}

	/**
	 * Replacement for the original getProperties() method which makes sure
	 * that org\renovant\util\reflection\ReflectionProperty objects are returned instead of the
	 * original ReflectionProperty instances.
	 * @throws \ReflectionException
	 */
	function getProperties(?int $filter = NULL): array {
		$extendedProperties = [];
		$properties = parent::getProperties($filter);
		foreach ($properties as $property) {
			$extendedProperties[] = new ReflectionProperty($this->getName(), $property->getName());
		}
		return $extendedProperties;
	}

	/**
	 * Checks if the doc comment of this method is tagged with the specified tag
	 */
	function hasTag(string $tagName): bool {
		return $this->getDocComment()->hasTag($tagName);
	}

	/**
	 * Returns an array of tags and their values
	 */
	function getAllTags(): array {
		return $this->getDocComment()->getAllTags();
	}

	/**
	 * Return tag values at specified index, can be NULL if not exists
	 * @return mixed|null
	 */
	function getTag(string$tagName, ?int $index=0) {
		return $this->getDocComment()->getTag($tagName, $index);
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
