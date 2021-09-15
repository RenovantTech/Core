<?php
namespace renovant\core\mail;
use const renovant\core\trace\T_ERROR;
use const renovant\core\trace\T_INFO;
use renovant\core\sys;
/**
 * Wrapper for PHPMailer
 *
 * Usage example:
 * {@example mail/Mailer.php 2}
 * Configuration file example: project/services/context.xml
 * {@example mail/Mailer.xml}
 * @link https://github.com/PHPMailer/PHPMailer
 */
class PhpMailer {
	use \renovant\core\CoreTrait;
	const ACL_SKIP = true;

	/** default transport type to be used */
	const DEFAULT_TRANSPORT = 'smtp';
	/** Array of failed recipients after a call to Mailer->send() or Mailer->batchSend()
	 * @var array */
	protected $failedRecipients = [];
	/** PHPMailer instance
	 * @var \PHPMailer\PHPMailer\PHPMailer */
	protected $Mailer;
	/** SMTP params array
	 * @var array */
	protected $transportOptions = [
		'server'	=> 'localhost',
		'port'		=> 25,
		'encryption'=> false,
		'user'		=> null,
		'password'	=> null
	];
	/** mail transport type to be used, can be: mail | smtp | sendmail (default: mail)
	 * @var string */
	protected $transportType = self::DEFAULT_TRANSPORT;

	/**
	 * @param string $transportType
	 * @param array|null $transportOptions
	 */
	function __construct($transportType=self::DEFAULT_TRANSPORT, array $transportOptions=null) {
		$this->transportType = $transportType;
		if($transportType == 'smtp' && !is_null($transportOptions)) $this->transportOptions = $transportOptions;
	}

	function __call($method, $args) {
		if(is_null($this->Mailer)) $this->initMailer();
		if (is_callable([$this->Mailer, $method]))
			return call_user_func_array([$this->Mailer, $method], $args);
		if(substr($method, 0,3) == 'set') {
			$prop = substr($method, 3);
			$this->Mailer->$prop = $args[0];
			return null;
		}
		return null;
	}

	function __sleep() {
		return ['_', 'transportOptions', 'transportType'];
	}

	/**
	 * Initialization method.
	 */
	protected function initMailer() {
		sys::trace(LOG_DEBUG, T_INFO);
		$this->Mailer = new \PHPMailer\PHPMailer\PHPMailer(true);
		switch($this->transportType) {
			case 'sendmail':

				break;
			case 'smtp':
				$this->Mailer->SMTPDebug = 2;
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

	/**
	 * Wrapper for PHPMailer->send().
	 * It add debug support.
	 * @return boolean
	 * @see PHPMailer::send()
	 */
	function send() {
		try {
			if(is_null($this->Mailer)) $this->initMailer();
			$this->Mailer->send();
			sys::trace(LOG_DEBUG, T_INFO, 'OK: Mail successfully sent!');
			return true;
		} catch (\Exception $Ex) {
			sys::trace(LOG_DEBUG, T_ERROR, 'ERROR: Mail not sent!', $Ex->getMessage());
			trigger_error($Ex->getMessage(), E_USER_WARNING);
			return false;
		}
	}
}
