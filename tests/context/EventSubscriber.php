<?php
namespace test\context;

class EventSubscriber implements \metadigit\core\event\EventSubscriberInterface {


	static function getSubscribedEvents() {
		return [
			'event1' => [ ['onEvent1',1] ],
			'event2' => [ ['onEvent2',1] ]
		];
	}

	function onEvent1($Event) {

	}

	function onEvent2($Event) {

	}
}
