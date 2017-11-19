<?php
namespace test\mail;
use metadigit\core\mail\Mailer;

class MailerTest extends \PHPUnit\Framework\TestCase {

	function testConstructor() {
		$Mailer = new Mailer(SWIFT_DIR, 'smtp', [
			'server'	=> 'localhost',
			'port'		=> 25,
			'encryption'=> '',
			'user'		=> '',
			'password'	=> ''
		]);
		$this->assertInstanceOf('metadigit\core\mail\Mailer', $Mailer);
		return $Mailer;
	}

	/**
	 * @depends testConstructor
	 */
	function __testNewMessage(Mailer $Mailer) {
		$Message = $Mailer->newMessage();
		$this->assertInstanceOf('Swift_Message', $Message);
		return $Mailer;
	}

	/**
	 * @depends testNewMessage
	 */
	function __testSend(Mailer $Mailer) {
		$Message = $Mailer->newMessage();
		$Message->setSubject('Message 4 you')
			->setFrom('dan@metadigit.it')
			->setTo('daniele.sciacchitano@gmail.com')
			->setBody('message content')
		;
		$r = $Mailer->send($Message);
		$this->assertEquals(1, $r);
	}
}
