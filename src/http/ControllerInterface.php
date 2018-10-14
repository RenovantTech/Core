<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace metadigit\core\http;
/**
 * Controller interface.
 * Is the interface that MVC controller must implements.
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
interface ControllerInterface {
	/**
	 * Process Request and prepare Response output, optionally invoking the rendering of a View.
	 * @param Request $Req current Request
	 * @param Response $Res current Response
	 */
	function handle(Request $Req, Response $Res);
}
