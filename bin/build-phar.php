<?php
require_once 'functions.inc';

class PharUtil {

	const EXT = '.phar';

	private static $source_dir = '';
	private static $phar_name = '';
	private static $phar_version = '';
	private static $phar_file = '';

	static function run() {
		$Req = parse_cli();
		if(
			@is_null($Req->name) ||
			@is_null($Req->version) ||
			@is_null($Req->sourcedir) ||
			!is_dir($Req->sourcedir) ||
			!is_writable($Req->sourcedir) ||
			@is_null($Req->outputdir) ||
			!is_dir($Req->outputdir) ||
			!is_writable($Req->outputdir)
		){
			self::help();
			return;
		}

		self::build($Req);
	}

	static protected function build($Req) {

		self::$source_dir = realpath($Req->sourcedir);
		self::$phar_name = $Req->name;
		self::$phar_version = $Req->version;

		self::$phar_file = $Req->outputdir.'/'.self::$phar_name.'-'.self::$phar_version.self::EXT;

		cli_echo('=========== Creating PHAR '.self::$phar_name.'-'.self::$phar_version.self::EXT.' ===========================');

		@unlink(self::$phar_file);

		$Phar = new Phar(self::$phar_file, 0, self::$phar_name.self::EXT);
		$Phar->startBuffering();

		$files = [];
		cli_echo('Scanning source directory: '.self::$source_dir.' ...');
		$rd = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(self::$source_dir));
		foreach($rd as $file) {
			$filepath = substr($file->getPath().DIRECTORY_SEPARATOR.$file->getFilename(),strlen(self::$source_dir));
			if (
			0===preg_match('/\/\.bzr\//',$filepath) &&
			0===preg_match('/\/\.git\//',$filepath) &&
			0===preg_match('/\/docs\//',$filepath) &&
			0===preg_match('/\/example\//',$filepath) &&
			0===preg_match('/\/examples\//',$filepath) &&
			0===preg_match('/\/TO-DO\//',$filepath) &&
			0===preg_match('/\/test\//',$filepath) &&
			0===preg_match('/\/tests\//',$filepath) &&
			0===preg_match('/\/.old$\//',$filepath) &&
			$file->getFilename() != '..' &&
			$file->getFilename() != '.'
			)
			{
				echo '- '.$filepath."\n";
				$files[$filepath] = $file->getPath().DIRECTORY_SEPARATOR.$file->getFilename();
			}
		}

		cli_echo('Creating Phar ...');
		$Phar->buildFromIterator(new ArrayIterator($files));

		self::createStub($Phar);

		$Phar->stopBuffering();
		cli_echo('OK: Phar writed to '.self::$phar_file);
		cli_echo('=========== END ===========================');

		//if($Phar->canCompress(\Phar::BZ2)) $Phar->compress(\Phar::BZ2);
	}

	static protected function createStub(Phar $Phar) {
		cli_echo('Creating Phar STUB...');
		//cli_echo('STUB: '.$Phar->createDefaultStub('cli.php', 'mvc/index.php'));

		if(file_exists(self::$source_dir.'/phar-stub.php')) $stub = file_get_contents(self::$source_dir.'/phar-stub.php');
		else $stub = '<?php
Phar::mapPhar();
__HALT_COMPILER();';

		$Phar->setStub($stub);
	}

	// =========================== helper methods ====================================================

	static private function help() {
		cli_echo('Usage:');
		cli_echo('php '.__FILE__.' --name= --version= --sourcedir= --outputdir=');
	}
}
PharUtil::run();