<?php
namespace renovant\core\util\reflection;
class DocComment {

	const TAG_REGEX = '/(([\w\:\.-]+)="([^"]+)" | ([\w\:\.-]+)=(\w+) | ([\w\:\.-]+))/x';

	/** @var string The description as found in the doc comment */
	protected $description = '';
	/** @var array An array of tag names and their values (multiple values are possible) */
	protected $tags = [];

	/**
	 * Parses the given doc comment and saves the result (description and tags) in the object properties.
	 * @param string $docComment A doc comment as returned by the reflection getDocComment() method
	 */
	function __construct(string $docComment) {
		$lines = explode(chr(10), $docComment);
		foreach ($lines as $line) {
			if (strlen($line) > 0 && strpos($line, '@') !== FALSE) {
				$this->parseTag(substr($line, strpos($line, '@')));
			} else if (count($this->tags) === 0) {
				$this->description .= preg_replace('/\s*\\/?[\\\\*]*(.*)$/', '$1', $line) . chr(10);
			}
		}
		$this->description = trim($this->description);
	}

	/**
	 * Returns the description which has been previously parsed
	 */
	function getDescription(): string {
		return $this->description;
	}

	/**
	 * Returns all tags which have been previously parsed
	 */
	function getAllTags(): array {
		return $this->tags;
	}

	/**
	 * Return number of tag values
	 */
	function countTag(string $tagName): int {
		return (isset($this->tags[$tagName])) ? count($this->tags[$tagName]) : 0;
	}

	/**
	 * Return tag values at specified index, can be NULL if not exists
	 * @return mixed|null
	 */
	function getTag(string $tagName, int $index=0) {
		return (isset($this->tags[$tagName][$index])) ? $this->tags[$tagName][$index] : null;
	}

	/**
	 * Returns the values of the specified tag.
	 * @throws \Exception
	 */
	function getTagValues(string $tagName): array {
		if (!$this->hasTag($tagName)) throw new \Exception('Tag "' . $tagName . '" does not exist.', 1169128255);
		return $this->tags[$tagName];
	}

	/**
	 * Checks if a tag with the given name exists
	 */
	function hasTag(string $tagName): bool {
		return (isset($this->tags[$tagName]));
	}

	/**
	 * Parses a line of a doc comment for a tag and its value.
	 */
	protected function parseTag(string $line): void {
		list($tag, $value) = preg_split('/[\s\(]/', $line.' ', 2);
		$tag = substr($tag, 1);
		if (empty($value)) {
			$this->tags[$tag] = [];
		} elseif(strpos($value, ')') === false) {
			$this->tags[$tag][] = trim($value);
		} else {
			preg_match_all(self::TAG_REGEX, $value, $matches, PREG_SET_ORDER);
			$values = [];
			foreach($matches as $match) {
				switch(count($match)) {
					case 7: $values[$match[6]] = true; break;
					case 6: $values[$match[4]] = (int)$match[5]; break;
					case 4: $values[$match[2]] = $match[3]; break;
				}
			}
			$this->tags[$tag][] = $values;
		}
	}
}
