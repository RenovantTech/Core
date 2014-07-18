<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\context;
/**
 * ContextAwareInterface
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
interface ContextAwareInterface {

	/**
	 * Inject owner Context
	 * @param Context $Context owner Context
	 */
	function setContext(Context $Context);
}