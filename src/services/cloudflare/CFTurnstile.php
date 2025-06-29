<?php
namespace renovant\core\services\cloudflare;

use renovant\core\sys;
use renovant\core\http\{Request, Response};

use const renovant\core\trace\{T_ERROR, T_INFO};

class CFTurnstile {
	use \renovant\core\CoreTrait;

	public const string API = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

	protected string $request_field = 'cf_response';

	protected string $site_key   = 'xxxxxxxxxxxxxx';
	protected string $secret_key = 'xxxxxxxxxxxxxx';

	public function verify(Request $Req, Response $Res): bool {
		try {
			if (!$cfResponse = $Req->get($this->request_field)) {
				sys::trace(LOG_DEBUG, T_ERROR, 'CloudFlare Turnstile: response MISSING');
				http_response_code(401);
				$Res->set('success', false);
				$Res->set('errors', ['CF_TURNSTILE' => 'MISSING']);
				return false;
			}

			sys::trace(LOG_DEBUG, 1, 'call CloudFlare Turnstile API');
			$curl = curl_init();
			curl_setopt_array($curl, [
				CURLOPT_URL            => self::API,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_TIMEOUT        => 15,
				CURLOPT_POST           => true,
				CURLOPT_POSTFIELDS     => http_build_query([
					'secret'   => $this->secret_key,
					'response' => $cfResponse,
					'remoteip' => $_SERVER['REMOTE_ADDR']
				]),
			]);
			$jsonResponse = curl_exec($curl);
			curl_close($curl);
			sys::trace(LOG_DEBUG, T_INFO, 'CloudFlare Turnstile response', $jsonResponse);

			$response = json_decode($jsonResponse, true);
			if (!$response || empty($response) || !$response['success']) {
				sys::trace(LOG_DEBUG, T_ERROR, 'CloudFlare Turnstile: ERROR');
				http_response_code(401);
				$Res->set('success', false);
				$Res->set('errors', ['CF_TURNSTILE' => 'ERROR']);
				return false;
			} else {
				sys::trace(LOG_DEBUG, T_INFO, 'CloudFlare Turnstile: OK');
				return true;
			}
		} catch (\Exception $Ex) {
			sys::trace(LOG_DEBUG, T_ERROR, 'CloudFlare Turnstile: EXCEPTION');
			http_response_code(500);
			$Res->set('success', false);
			$Res->set('errors', ['CF_TURNSTILE' => 'EXCEPTION']);
			return false;
		}
	}
}
