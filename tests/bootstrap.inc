<?php
define('ABORT_TRACE',	true);
define('metadigit\core\BOOTSTRAP',	__FILE__);
define('metadigit\core\PUBLIC_DIR',	'/tmp/metadigit-core/httpdocs/');
define('metadigit\core\BASE_DIR',	__DIR__.'/');
define('metadigit\core\DATA_DIR',	'/tmp/metadigit-core/');
define('metadigit\core\ASSETS_DIR',	'/tmp/metadigit-core/assets/');
define('metadigit\core\BACKUP_DIR',	'/tmp/metadigit-core/backup/');
define('metadigit\core\CACHE_DIR',	'/tmp/metadigit-core/cache/');
define('metadigit\core\LOG_DIR',	'/tmp/metadigit-core/log/');
define('metadigit\core\TMP_DIR',	'/tmp/metadigit-core/tmp/');
define('metadigit\core\UPLOAD_DIR',	'/tmp/metadigit-core/upload/');

define('MOCK_DIR', __DIR__.'/_mock');

require __DIR__.'/../src/Kernel.php';
require __DIR__.'/../vendor/autoload.php';

// clean DATA_DIR
exec('rm -fr '.metadigit\core\DATA_DIR);
@mkdir(metadigit\core\DATA_DIR,0777,true);
register_shutdown_function(function() {
	exec('rm -fr '.metadigit\core\DATA_DIR);
});
metadigit\core\Kernel::init();