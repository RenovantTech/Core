<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace renovant\core\http;
/**
 * MVC View for an HTTP Request.
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
	 * @param Request $Req current request
	 * @param Response $Res current response we are building
	 * @param string|null $resource template or similar resource needed by view implementation
	 * @param array|null $options rendering options
	 * @throws \Exception if rendering failed
	 */
	function render(Request $Req, Response $Res, $resource=null, array $options=null);
}
