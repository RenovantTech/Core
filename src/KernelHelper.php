<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core;
use const metadigit\core\trace\T_INFO;
/**
 * KernelHelper
 * @internal
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class KernelHelper extends Kernel {

	static function boot() {
		trace(LOG_DEBUG, T_INFO, null, null, __METHOD__);
		self::log('kernel bootstrap', LOG_INFO, 'kernel');
		// directories
		if(!defined('\metadigit\core\PUBLIC_DIR') && PHP_SAPI!='cli') die(KernelException::ERR21);
		if(!defined('\metadigit\core\BASE_DIR')) die(KernelException::ERR22);
		if(!defined('\metadigit\core\DATA_DIR')) die(KernelException::ERR23);
		if(!is_writable(DATA_DIR)) die(KernelException::ERR24);
		// php.ini settings
		if(get_magic_quotes_gpc()) die(KernelException::ERR29);
		// DATA_DIR
		if(!file_exists(ASSETS_DIR)) mkdir(ASSETS_DIR, 0770, true);
		if(!file_exists(BACKUP_DIR)) mkdir(BACKUP_DIR, 0770, true);
		if(!file_exists(CACHE_DIR)) mkdir(CACHE_DIR, 0770, true);
		if(!file_exists(LOG_DIR)) mkdir(LOG_DIR, 0770, true);
		if(!file_exists(TMP_DIR)) mkdir(TMP_DIR, 0770, true);
		if(!file_exists(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0770, true);
		file_put_contents(DATA_DIR.'.metadigit-core', date('Y-m-d H:i:s'));
	}
}
