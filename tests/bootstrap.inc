<?php
namespace metadigit\core;
// directories
const PUBLIC_DIR	= '/tmp/metadigit-core/httpdocs/';
const BASE_DIR		= __DIR__.'/';
const DATA_DIR		= '/tmp/metadigit-core/';
const ASSETS_DIR	= '/tmp/metadigit-core/assets/';
const BACKUP_DIR	= '/tmp/metadigit-core/backup/';
const CACHE_DIR		= '/tmp/metadigit-core/cache/';
const LOG_DIR		= '/tmp/metadigit-core/log/';
const TMP_DIR		= '/tmp/metadigit-core/tmp/';
const UPLOAD_DIR	= '/tmp/metadigit-core/upload/';
// CLI paths
const CLI_BOOTSTRAP	= __FILE__;
const CLI_PHP_BIN	= '/usr/bin/php';
// environment
const SYS_YAML		= BASE_DIR.'sys.yml';
const ENVIRONMENT	= 'PHPUNIT';

define('TEST_DIR', __DIR__);
define('XSENDFILE_PATH', __DIR__.'/http/templates/');
define('XSENDFILE_URL', '/web/templates/');

require __DIR__.'/../src/constants.inc';
require __DIR__.'/../src/CoreProxy.php';
require __DIR__.'/../src/CoreTrait.php';
require __DIR__.'/../src/sys.php';
require __DIR__.'/../vendor/autoload.php';

// clean DATA_DIR
exec('rm -fr '.DATA_DIR);
@mkdir(DATA_DIR, 0777, true);
register_shutdown_function(function() {
	exec('rm -fr '.DATA_DIR);
});
sys::init();
