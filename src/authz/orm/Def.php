<?php
namespace renovant\core\authz\orm;
class Def {

	/** @validate(min=0) */
	public int $id;
	/** @validate(enum="ROLE, PERMISSION, ACL") */
	public string $type;
	/** @validate(minLength=3, regex="/^[\w\:\.-]+$/") */
	public string $code;
	/** @validate(minLength=0) */
	public string $label = '';
	/** @validate(minLength=0, null) */
	public ?string $query = null;

	function __construct(array $data) {
		$this->id = (int) ($data['id'] ?? 0);
		$this->type = $data['type'];
		$this->code = $data['code'];
		$this->label = $data['label'] ?? '';
		$this->query = $data['query'] ?? null;
	}
}
