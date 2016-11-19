<?php
namespace metadigit\core;
// directories
const BOOTSTRAP = __FILE__;
const PUBLIC_DIR	= '/tmp/metadigit-core/httpdocs/';
const BASE_DIR		= __DIR__.'/';
const DATA_DIR		= '/tmp/metadigit-core/';
const ASSETS_DIR	= '/tmp/metadigit-core/assets/';
const BACKUP_DIR	= '/tmp/metadigit-core/backup/';
const CACHE_DIR		= '/tmp/metadigit-core/cache/';
const LOG_DIR		= '/tmp/metadigit-core/log/';
const TMP_DIR		= '/tmp/metadigit-core/tmp/';
const UPLOAD_DIR	= '/tmp/metadigit-core/upload/';
// environment
const CORE_YAML		= BASE_DIR.'core.yml';
const ENVIRONMENT	= 'PHPUNIT';

define('MOCK_DIR', __DIR__.'/_mock');

require __DIR__.'/../src/constants.inc';
require __DIR__.'/../src/helpers.inc';
require __DIR__.'/../src/trace/Tracer.php';
require __DIR__.'/../src/CoreProxy.php';
require __DIR__.'/../src/CoreTrait.php';
require __DIR__.'/../src/Kernel.php';
require __DIR__.'/../vendor/autoload.php';

// clean DATA_DIR
exec('rm -fr '.DATA_DIR);
@mkdir(DATA_DIR, 0777, true);
register_shutdown_function(function() {
	exec('rm -fr '.DATA_DIR);
});
Kernel::init();
