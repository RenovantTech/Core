<?php
namespace renovant\core\http;
interface ControllerInterface {
	/**
	 * Process Request and prepare Response output, optionally invoking the rendering of a View.
	 * @param Request $Req current Request
	 * @param Response $Res current Response
	 */
	function handle(Request $Req, Response $Res);
}
