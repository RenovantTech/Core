<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace renovant\core\console;
/**
 * Controller interface.
 * Is the interface that MVC controller must implements.
 * @author Daniele Sciacchitano <dan@renovant.tech>
 */
interface ControllerInterface {
	/**
	 * Process Request and prepare Response output, optionally invoking the rendering of a View.
	 * @param Request $Req current Request
	 * @param Response $Res current Response
	 * @return ViewInterface|string|null View instance or view name (string) to render (can be NULL if handled directly)
	 */
	function handle(Request $Req, Response $Res);
}
