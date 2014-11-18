<?php
namespace mock\util\validator;

class Class1 {

	/** @validate(min=5) */
	protected $id;
	/** @validate(true) */
	protected $active = false;
	/** @validate(minLength=2, maxLength=20) */
	protected $name;
	/** @validate(minLength=3, maxLength=30) */
	protected $surname;
	/** @validate(max=50) */
	protected $age = 20;
	/** @validate(null, email) */
	protected $email;

	function __set($k, $v) {
		$this->$k = $v;
	}
}
