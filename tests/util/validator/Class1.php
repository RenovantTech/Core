<?php
namespace test\util\validator;

class Class1 {

	/** @validate(min=5, max=15) */
	protected $id;
	/** @validate(true) */
	protected $active = false;
	/** @validate(minLength=2, maxLength=20) */
	protected $name;
	/** @validate(minLength=3, maxLength=30) */
	protected $surname;
	/** @validate(enum="PENDING, COMPLETED") */
	protected $flag;
	/** @validate(max=50) */
	protected $age = 20;
	/** @validate(email) */
	protected $email1;
	/** @validate(null, email) */
	protected $email2;
	/** @validate(empty, email) */
	protected $email3;
	/** @validate(date) */
	protected $date1;
	/** @validate(null, date) */
	protected $date2;
	/** @validate(datetime) */
	protected $datetime;
	/** @validate(time) */
	protected $time;

	function __set($k, $v) {
		$this->$k = $v;
	}
}
