<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\db;
use const metadigit\core\TMP_DIR;
/**
 * PDO
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class PDO extends \PDO {

	const DEFAULT_ATTRS = [
		PDO::ATTR_STATEMENT_CLASS => ['metadigit\core\db\PDOStatement']
	];

	/**
	 * Create new Query object
	 * @param string $dsn      the Data Source Name, or DSN, contains the information required to connect to the database
	 * @param string $username the user name for the DSN string, optional for some PDO drivers
	 * @param string $password the password for the DSN string, optional for some PDO drivers
	 * @param array	 $options  a key=>value array of driver-specific connection options
	 */
	function __construct($dsn, $username=null, $password=null, array $options=null) {
		$options = ($options) ? array_merge($options, self::DEFAULT_ATTRS) : self::DEFAULT_ATTRS;
		parent::__construct($dsn, $username, $password, $options);
		$this->setAttribute(\PDO::ATTR_ERRMODE,\PDO::ERRMODE_EXCEPTION);
		// SqLite specific settings
		if('sqlite'==$this->getAttribute(\PDO::ATTR_DRIVER_NAME)) {
			// @TODO if(file_exists(TMP_DIR.$id.'.vacuum')) unlink(TMP_DIR.$id.'.vacuum') && $this->exec('VACUUM');
			$this->exec('PRAGMA journal_mode = WAL');
			$this->exec('PRAGMA temp_store = MEMORY');
			$this->exec('PRAGMA synchronous = OFF');
			$this->exec('PRAGMA foreign_keys = ON');
		}
	}
}
