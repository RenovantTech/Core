<?php
namespace renovant\core\trace;

use renovant\core\auth\Auth;

use const renovant\core\LOG_DIR;

class TracerLog {
	public static function write($Req, $Res, array $trace, int $errorLevel) {
		if (defined('renovant\webconsole\ABORT_TRACE') && constant('renovant\webconsole\ABORT_TRACE')) {
			return;
		}

		$trace[] = [round(microtime(1) - $_SERVER['REQUEST_TIME_FLOAT'], 5), memory_get_usage(), LOG_DEBUG, T_INFO, '\\\trace\Tracer::shutdown', null, null];

		$hr  = str_pad('', 160, '-', STR_PAD_RIGHT);
		$log = '';

		// build HEADER

		list($legend, $header) = self::buildHeader($Req, $Res);
		$log .= $hr . PHP_EOL;
		$log .= $legend . PHP_EOL;
		$log .= $header . PHP_EOL;
		$log .= $hr . PHP_EOL;

		// build TRACE

		foreach ($trace as $t) {
			$log .= str_pad(number_format($t[0], 6, '.', ''), 10, ' ', STR_PAD_LEFT) . ' ' . str_pad($t[1], 9, ' ', STR_PAD_LEFT) . '  ' . self::level($t[2]) . '  ' . self::type($t[3]) . '  ' . str_pad($t[4], 60) . str_replace(["\n", "\t"], '', $t[5] ?? '') . PHP_EOL;
			if (!empty($t[6])) {
				$data = unserialize($t[6]);
				if (!empty($data)) {
					$log .= self::indentData($data) . PHP_EOL;
				}
			}
		}
		$log .= PHP_EOL;
		file_put_contents(LOG_DIR . 'debug.log', $log, FILE_APPEND);
	}

	protected static function buildHeader($Req, $Res): array {
		if (PHP_SAPI != 'cli') {
			$url = $Req->URI();
		} else {
			$args = $_SERVER['argv'];
			array_shift($args);
			$url = implode(' ', $args);
		}

		$legend = str_pad('Date', 21);
		$header = str_pad(date('Y-m-d H:i:s  '), 21);

		$legend .= str_pad('Time', 9);
		$header .= str_pad(number_format(TRACE_END_TIME - $_SERVER['REQUEST_TIME_FLOAT'], 3, '.', ''), 9);

		$legend .= str_pad('Mem', 8);
		$header .= str_pad(number_format(memory_get_peak_usage(true) / 1000000, 3, '.', ''), 8);

		$legend .= str_pad('Host', 10);
		$header .= str_pad(gethostname(), 10);

		$legend .= str_pad('App', 10);
		$header .= str_pad($Req ? ((string)$Req->getAttribute('APP')) : '', 10);

		$legend .= str_pad('Module', 10);
		$header .= str_pad($Req ? ((string)$Req->getAttribute('APP_MOD')) : '', 10);

		$legend .= str_pad('API', 6);
		$header .= str_pad((PHP_SAPI == 'cli') ? 'CLI' : $_SERVER['REQUEST_METHOD'], 6);

		$legend .= str_pad('Url', 50);
		$header .= str_pad($Req ? $url : '', 50);

		$legend .= str_pad('Code', 5);
		$header .= str_pad(http_response_code(), 5);

		$legend .= str_pad('Size', 6);
		$header .= str_pad($Req ? (int) $Res->getSize() : 0, 6);

		$legend .= str_pad('Geo', 4);
		$header .= str_pad($_SERVER['HTTP_CF_IPCOUNTRY'] ?? '', 4);

		$legend .= str_pad('IP', 17);
		$header .= str_pad($_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '', 17);

		$legend .= 'UID';
		$header .= Auth::instance()->UID() ?? null;

		return [$legend, $header];
	}

	protected static function indentData(mixed $data): string {
		$data = print_r($data, true);
		return str_pad('', 50) . str_replace("\n", "\n" . str_pad('', length: 50), $data);
	}

	protected static function level(int $level): string {
		return match ($level) {
			0 => '* EMERG *',
			1 => '* ALERT *',
			2 => '* CRIT  *',
			3 => '*  ERR  *',
			4 => ' WARNING ',
			5 => ' NOTICE  ',
			6 => '  INFO   ',
			7 => '  DEBUG  '
		};
	}

	protected static function type(int $type): string {
		return match ($type) {
			0 => 'T_ERROR   ',
			1 => 'T_INFO    ',
			2 => 'T_AUTOLOAD',
			3 => 'T_DB      ',
			4 => 'T_DEPINJ  ',
			5 => 'T_CACHE   ',
			6 => 'T_EVENT   '
		};
	}
}
