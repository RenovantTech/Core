<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\console;
use metadigit\core\cli\Request,
	metadigit\core\cli\Response;
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
	 * @return ViewInterface|string|null View instance or view name (string) to render (can be NULL if handled directly)
	 */
	function handle(Request $Req, Response $Res);
}