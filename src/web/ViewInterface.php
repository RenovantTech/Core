<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\web;
use metadigit\core\http\Request,
	metadigit\core\http\Response;
/**
 * MVC View for a CLI Request.
 * Implementations are responsible for rendering content, and exposing the model. A single view exposes multiple model attributes.
 *
 * View implementations may differ widely. An obvious implementation would be
 * PHP-based. Other implementations might be XSLT-based, or use an HTML generation library.
 * This interface is designed to avoid restricting the range of possible implementations.
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
interface ViewInterface {
	/**
	 * Render the View given the specified resource.
	 * @param Request $Req current request
	 * @param Response $Res current response we are building
	 * @param string $resource
	 * @throws \Exception if rendering failed
	 * @return void
	 */
	function render(Request $Req, Response $Res, $resource);
}