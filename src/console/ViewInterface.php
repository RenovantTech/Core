<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace renovant\core\console;
/**
 * MVC View for a CLI Request.
 * Implementations are responsible for rendering content, and exposing the model. A single view exposes multiple model attributes.
 *
 * View implementations may differ widely. An obvious implementation would be
 * PHP-based. Other implementations might be XSLT-based, or use an HTML generation library.
 * This interface is designed to avoid restricting the range of possible implementations.
 * @author Daniele Sciacchitano <dan@renovant.tech>
 */
interface ViewInterface {
	/**
	 * Render the View given the specified resource.
	 * @param Request $Req current Request
	 * @param Response $Res current Response
	 * @param string $resource optional View resource (may be a template)
	 * @throws \Exception if rendering failed
	 * @return void
	 */
	function render(Request $Req, Response $Res, $resource);
}
