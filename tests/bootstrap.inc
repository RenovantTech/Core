<?php
namespace renovant\core;

// directories
const PUBLIC_DIR = '/tmp/renovant-core/httpdocs/';
const BASE_DIR   = __DIR__ . '/';
const BIN_DIR    = __DIR__ . '/bin/';
const DATA_DIR   = '/tmp/renovant-core/';
const ASSETS_DIR = '/tmp/renovant-core/assets/';
const BACKUP_DIR = '/tmp/renovant-core/backup/';
const CACHE_DIR  = '/tmp/renovant-core/cache/';
const LOG_DIR    = '/tmp/renovant-core/log/';
const RUN_DIR    = '/tmp/renovant-core/run/';
const TMP_DIR    = '/tmp/renovant-core/tmp/';
const UPLOAD_DIR = '/tmp/renovant-core/upload/';
// environment
const SYS_YAML    = 'sys.yml';
const ENVIRONMENT = 'PHPUNIT';
const DEBUG_MODE  = true;
const DEBUG_LOG   = LOG_DIR . 'debug.log';

define('TEST_DIR', __DIR__);
define('XSENDFILE_PATH', __DIR__ . '/http/templates/');
define('XSENDFILE_URL', '/web/templates/');

require __DIR__ . '/../src/constants.inc.php';
require __DIR__ . '/../src/CoreProxy.php';
require __DIR__ . '/../src/CoreTrait.php';
require __DIR__ . '/../src/sys.php';
require __DIR__ . '/../vendor/autoload.php';

// prepare BIN_DIR
chmod(BIN_DIR . 'sys', 755);

// clean DATA_DIR
exec('rm -fr ' . DATA_DIR);
@mkdir(DATA_DIR, 0777, true);
register_shutdown_function(function () {
	//	exec('nohup sleep 2 && rm -fr '.DATA_DIR);
});
sys::init('sys', 'system');
