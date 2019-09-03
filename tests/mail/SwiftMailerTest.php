<?php
namespace test\mail;
use renovant\core\mail\SwiftMailer;

class SwiftMailerTest extends \PHPUnit\Framework\TestCase {

	function testConstructor() {
		$Mailer = new SwiftMailer(SWIFT_DIR, 'smtp', [
			'server'	=> 'localhost',
			'port'		=> 25,
			'encryption'=> '',
			'user'		=> '',
			'password'	=> ''
		]);
		$this->assertInstanceOf('renovant\core\mail\SwiftMailer', $Mailer);
		return $Mailer;
	}

	/**
	 * @depends testConstructor
	 */
	function __testNewMessage(SwiftMailer $Mailer) {
		$Message = $Mailer->newMessage();
		$this->assertInstanceOf('Swift_Message', $Message);
		return $Mailer;
	}

	/**
	 * @depends testNewMessage
	 */
	function __testSend(SwiftMailer $Mailer) {
		$Message = $Mailer->newMessage();
		$Message->setSubject('Message 4 you')
			->setFrom('dan@renovant.tech')
			->setTo('daniele.sciacchitano@gmail.com')
			->setBody('message content')
		;
		$r = $Mailer->send($Message);
		$this->assertEquals(1, $r);
	}
}
