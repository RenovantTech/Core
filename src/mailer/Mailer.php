<?php
namespace renovant\core\mailer;

use renovant\core\sys,
PHPMailer\PHPMailer\PHPMailer;

use const renovant\core\{CACHE_DIR, DATA_DIR};
use const renovant\core\trace\{T_ERROR, T_INFO};

/**
 * Wrapper for PHPMailer
 *
 * Usage example:
 * {@example mail/Mailer.php 2}
 * Configuration file example: project/services/context.xml
 * {@example mail/Mailer.xml}
 * @link https://github.com/PHPMailer/PHPMailer
 */
class Mailer {
	use \renovant\core\CoreTrait;

	public const string CACHE_DIR    = CACHE_DIR . 'mailer/';
	public const string TEMPLATE_DIR = DATA_DIR . 'mailer-tpl/';
	/** default transport type to be used */
	public const string TRANSPORT_SMTP    = 'smtp';
	public const string TPL_ENGINE_PHP    = 'PHP';
	public const string TPL_ENGINE_PHPTAL = 'PHPTAL';

	/** debug info*/
	protected array $debug = [];
	/** Array of failed recipients after a call to Mailer->send() or Mailer->batchSend() */
	protected array $failedRecipients = [];
	/** PHPMailer instance
	 * @var \PHPMailer\PHPMailer\PHPMailer */
	protected $Mailer;
	protected string $cacheDir    = self::CACHE_DIR;
	protected string $templateDir = self::TEMPLATE_DIR;
	/** Template engine to parse HTML body */
	protected string $templateEngineHTML = self::TPL_ENGINE_PHP;
	/** Template engine to parse TXT body */
	protected string $templateEngineTXT = self::TPL_ENGINE_PHP;

	/** SMTP params array */
	protected array $transportOptions = [
		'server'     => 'localhost',
		'port'       => 25,
		'encryption' => false,
		'user'       => null,
		'password'   => null
	];
	/** mail transport type to be used, can be: mail | smtp | sendmail (default: mail) */
	protected string $transportType = self::TRANSPORT_SMTP;

	/**
	 * @param string $transportType
	 * @param array|null $transportOptions
	 */
	public function __construct($transportType = self::TRANSPORT_SMTP, array $transportOptions = null) {
		$this->transportType = $transportType;
		if ($transportType == 'smtp' && !is_null($transportOptions)) {
			$this->transportOptions = $transportOptions;
		}
		$this->_init();
	}

	public function __call($method, $args) {
		if (is_callable([$this->Mailer, $method])) {
			return call_user_func_array([$this->Mailer, $method], $args);
		}
		if (substr($method, 0, 3) == 'set') {
			$prop                = substr($method, 3);
			$this->Mailer->$prop = $args[0];
			return null;
		}
		return null;
	}

	public function __sleep() {
		return ['_', 'templateEngineHTML', 'templateEngineTXT', 'transportOptions', 'transportType'];
	}

	public function __wakeup() {
		$this->_init();
	}

	/**
	 * Initialization method.
	 */
	protected function _init() {
		$this->Mailer = new PHPMailer(true);
		switch ($this->transportType) {
			case 'sendmail':

				break;
			case 'smtp':
				$this->Mailer->CharSet   = PHPMailer::CHARSET_UTF8;
				$this->Mailer->SMTPDebug = 0;
				$this->Mailer->isSMTP();
				$this->Mailer->Host       = $this->transportOptions['server'];
				$this->Mailer->SMTPAuth   = true;
				$this->Mailer->Username   = $this->transportOptions['user'];
				$this->Mailer->Password   = $this->transportOptions['password'];
				$this->Mailer->SMTPSecure = $this->transportOptions['encryption'];
				$this->Mailer->Port       = (isset($this->transportOptions['port'])) ? (int) $this->transportOptions['port'] : 25;
				break;
		}
	}

	public function setFrom(string $email, string $name): self {
		$this->debug['from'] = [$email, $name];
		$this->Mailer->setFrom($email, $name);
		return $this;
	}

	public function setSubject(string $subject): self {
		$this->debug['subject'] = $subject;
		$this->Mailer->Subject  = $subject;
		return $this;
	}

	public function setReplyTo(string $email, string $name): self {
		$this->debug['replyTo'] = [$email, $name];
		$this->Mailer->addReplyTo($email, $name);
		return $this;
	}

	public function setTo(string $email, ?string $name = null): self {
		$this->debug['to'] = [[$email, $name]];
		$this->Mailer->clearAddresses();
		$this->Mailer->addAddress($email, $name);
		return $this;
	}

	public function addTo(string $email, ?string $name = null): self {
		$this->debug['to'][] = [$email, $name];
		$this->Mailer->addAddress($email, $name);
		return $this;
	}

	public function setCC(string $email, ?string $name = null): self {
		$this->debug['cc'] = [[$email, $name]];
		$this->Mailer->clearCCs();
		$this->Mailer->addCC($email, $name);
		return $this;
	}

	public function addCC(string $email, ?string $name = null): self {
		$this->debug['cc'][] = [$email, $name];
		$this->Mailer->addCC($email, $name);
		return $this;
	}

	public function setBCC(string $email, ?string $name = null): self {
		$this->debug['bcc'] = [[$email, $name]];
		$this->Mailer->clearBCCs();
		$this->Mailer->addBCC($email, $name);
		return $this;
	}

	public function addBCC(string $email, ?string $name = null): self {
		$this->debug['bcc'][] = [$email, $name];
		$this->Mailer->addBCC($email, $name);
		return $this;
	}

	public function setBodyHTML(string $bodyHtml): self {
		$this->Mailer->isHTML(true);
		$this->Mailer->Body = $bodyHtml;
		return $this;
	}

	public function setBodyTXT(string $bodyTxt): self {
		$this->Mailer->AltBody = $bodyTxt;
		return $this;
	}

	public function addImage(string $path, string $name, string $ext = 'png'): void {
		if ($path[0] != '/') {
			$src  = $path;
			$path = $this->templateDir . 'images/' . $path;
		} else {
			$src = $name . '.' . $ext;
		}
		$this->Mailer->addEmbeddedImage($path, $name, $name);
		$this->replaceHTML([$src => 'cid:' . $name]);
	}

	/**
	 * Wrapper for PHPMailer->send().
	 * It add debug support.
	 * @return boolean
	 * @see PHPMailer::send()
	 */
	public function send(): bool {
		try {
			if ($this->Mailer->send()) {
				sys::trace(LOG_DEBUG, T_INFO, '[OK] Mail successfully sent!');
				return true;
			} else {
				sys::trace(LOG_DEBUG, T_ERROR, '[ERROR] Mail not sent: ' . $this->Mailer->ErrorInfo, $this->Mailer->ErrorInfo);
				trigger_error($this->Mailer->ErrorInfo, E_USER_WARNING);
				return false;
			}
		} catch (\Exception $Ex) {
			sys::trace(LOG_DEBUG, T_ERROR, '[EXCEPTION] Mail not sent: ' . $Ex->getMessage(), $Ex->getMessage());
			trigger_error($Ex->getMessage(), E_USER_WARNING);
			return false;
		}
	}

	public function parseHTML(string $template, array $model = [], ?string $engine = null) {
		$this->Mailer->isHTML(true);
		$engine ??= $this->templateEngineHTML;

		$this->Mailer->Body = match ($engine) {
			self::TPL_ENGINE_PHP    => (new parser\PhpParser($this->templateDir))->parse($template, $model),
			self::TPL_ENGINE_PHPTAL => (new parser\PhpTALParser($this->templateDir, $this->cacheDir))->parse($template, $model),
		};
	}

	public function parseTXT(string $template, array $model = [], ?string $engine = null) {
		$engine ??= $this->templateEngineTXT;

		$this->Mailer->AltBody = match ($engine) {
			self::TPL_ENGINE_PHP    => (new parser\PhpParser($this->templateDir))->parse($template, $model),
			self::TPL_ENGINE_PHPTAL => (new parser\PhpTALParser($this->templateDir, $this->cacheDir))->parse($template, $model),
		};
	}

	public function replaceHTML(array $data): void {
		$this->Mailer->Body = str_replace(array_keys($data), array_values($data), $this->Mailer->Body);
	}

	public function replaceTXT(array $data): void {
		$this->Mailer->AltBody = str_replace(array_keys($data), array_values($data), $this->Mailer->AltBody);
	}
}
