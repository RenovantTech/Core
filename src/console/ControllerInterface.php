<?php
namespace renovant\core\console;
interface ControllerInterface {
	/**
	 * Process Request and prepare Response output, optionally invoking the rendering of a View.
	 * @param Request $Req current Request
	 * @param Response $Res current Response
	 * @return ViewInterface|string|null View instance or view name (string) to render (can be NULL if handled directly)
	 */
	function handle(Request $Req, Response $Res);
}
