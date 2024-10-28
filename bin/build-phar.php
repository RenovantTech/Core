<?php

class PharUtil {
	public const EXT = '.phar';

	private static $source_dir   = '';
	private static $phar_name    = '';
	private static $phar_version = '';
	private static $phar_file    = '';

	public static function run() {
		$Req = self::parseCliArgv();
		if (
			@is_null($Req['name']) ||
			@is_null($Req['version']) ||
			@is_null($Req['sourcedir']) ||
			!is_dir($Req['sourcedir']) ||
			!is_writable($Req['sourcedir']) ||
			@is_null($Req['outputdir']) ||
			!is_dir($Req['outputdir']) ||
			!is_writable($Req['outputdir'])
		) {
			self::help();
			return;
		}

		self::build($Req);
	}

	protected static function build($Req) {
		self::$source_dir   = realpath($Req['sourcedir']);
		self::$phar_name    = $Req['name'];
		self::$phar_version = $Req['version'];

		self::$phar_file = $Req['outputdir'] . '/' . self::$phar_name . '-' . self::$phar_version . self::EXT;

		echo PHP_EOL;
		echo str_pad('=== Creating PHAR ' . self::$phar_name . '-' . self::$phar_version . self::EXT . ' ', 80, '=') . PHP_EOL;

		@unlink(self::$phar_file);

		$Phar = new Phar(self::$phar_file, 0, self::$phar_name . self::EXT);
		$Phar->startBuffering();

		$files = [];
		echo 'Scanning source directory: ' . self::$source_dir . ' ...' . PHP_EOL;
		$rd = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(self::$source_dir));
		foreach ($rd as $file) {
			$filepath = substr($file->getPath() . DIRECTORY_SEPARATOR . $file->getFilename(), strlen(self::$source_dir));
			if (
				0 === preg_match('/\/\.bzr\//', $filepath) &&
				0 === preg_match('/\/\.git\//', $filepath) &&
				0 === preg_match('/\/docs\//', $filepath) &&
				0 === preg_match('/\/example\//', $filepath) &&
				0 === preg_match('/\/examples\//', $filepath) &&
				0 === preg_match('/\/TO-DO\//', $filepath) &&
				0 === preg_match('/\/test\//', $filepath) &&
				0 === preg_match('/\/tests\//', $filepath) &&
				0 === preg_match('/\/.old$\//', $filepath) &&
				$file->getFilename() != '..' &&
				$file->getFilename() != '.'
			) {
				echo '- ' . $filepath . "\n";
				$files[$filepath] = $file->getPath() . DIRECTORY_SEPARATOR . $file->getFilename();
			}
		}

		echo 'Creating Phar ...' . PHP_EOL;
		$Phar->buildFromIterator(new ArrayIterator($files));

		self::createStub($Phar);

		$Phar->stopBuffering();
		echo 'OK: Phar writed to ' . self::$phar_file . PHP_EOL;
		echo str_pad('', 80, '=') . PHP_EOL . PHP_EOL;

		//if($Phar->canCompress(\Phar::BZ2)) $Phar->compress(\Phar::BZ2);
	}

	protected static function createStub(Phar $Phar) {
		echo 'Creating Phar STUB...' . PHP_EOL;
		//self::echo('STUB: '.$Phar->createDefaultStub('cli.php', 'mvc/index.php'));

		if (file_exists(self::$source_dir . '/phar-stub.php')) {
			$stub = file_get_contents(self::$source_dir . '/phar-stub.php');
		} else {
			$stub = '<?php
Phar::mapPhar();
__HALT_COMPILER();';
		}

		$Phar->setStub($stub);
	}

	// =========================== helper methods ====================================================

	protected static function help() {
		echo 'Usage:' . PHP_EOL;
		echo 'php ' . __FILE__ . ' --name= --version= --sourcedir= --outputdir=' . PHP_EOL;
	}

	protected static function parseCliArgv(): array {
		$argv  = $_SERVER['argv'];
		$_ARGV = [];
		array_shift($argv);
		foreach ($argv as $arg) {
			// --foo --bar=baz
			if (substr($arg, 0, 2) == '--') {
				$eqPos = strpos($arg, '=');
				// --foo
				if ($eqPos === false) {
					$key         = substr($arg, 2);
					$value       = isset($_ARGV[$key]) ? $_ARGV[$key] : true;
					$_ARGV[$key] = $value;
				}
				// --bar=baz
				else {
					$key         = substr($arg, 2, $eqPos - 2);
					$value       = substr($arg, $eqPos + 1);
					$_ARGV[$key] = $value;
				}
			}
			// -k=value -abc
			elseif (substr($arg, 0, 1) == '-') {
				// -k=value
				if (substr($arg, 2, 1) == '=') {
					$key         = substr($arg, 1, 1);
					$value       = substr($arg, 3);
					$_ARGV[$key] = $value;
				}
				// -abc
				else {
					$chars = str_split(substr($arg, 1));
					foreach ($chars as $char) {
						$key         = $char;
						$value       = isset($_ARGV->$key) ? $_ARGV->$key : true;
						$_ARGV[$key] = $value;
					}
				}
			}
			// plain-arg
			else {
			}
		}
		return $_ARGV;
	}
}
PharUtil::run();
