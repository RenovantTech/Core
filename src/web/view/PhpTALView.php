<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\web\view;
use metadigit\core\http\Request,
	metadigit\core\http\Response,
	metadigit\core\web\Exception;
/**
 * PhpTAL View
 * View engine that use the PhpTAL template engine.
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class PhpTALView implements \metadigit\core\web\ViewInterface {
	use \metadigit\core\CoreTrait;

	/** template suffixes */
	const TEMPLATE_SUFFIXS = '.html|.xml';
	/** customizable PhpTAL pre-filter class, must implements PHPTAL_Filter
	 * @var string */
	protected $preFilterClass = null;
	/** customizable PhpTAL post-filter class, must implements PHPTAL_Filter
	 * @var string */
	protected $postFilterClass = null;

	function render(Request $Req, Response $Res, $resource) {
		$template = null;
		$suffixs = explode('|', static::TEMPLATE_SUFFIXS);
		foreach($suffixs as $suffix) {
			if(file_exists($template = $Req->getAttribute('RESOURCES_DIR').$resource.$suffix)) {
				$this->trace(LOG_DEBUG, 1, __FUNCTION__, 'template: '.$template);
				$this->execTemplate($template, $Res);
				return;
			}
		}
		throw new Exception(201, 'PHPTal Template', $Req->getAttribute('RESOURCES_DIR').$resource.static::TEMPLATE_SUFFIXS);
	}

	function setPreFilter($class) {
		$this->preFilterClass = $class;
	}

	function setPostFilter($class) {
		$this->postFilterClass = $class;
	}

	private function execTemplate($template, Response $Res) {
		// build PHPTAL object & set options
		$PhpTAL = new \PHPTAL($template);
		$PhpTAL->setEncoding('UTF-8');
		$PhpTAL->setOutputMode(\PHPTAL::HTML5);
		if(!file_exists(\metadigit\core\CACHE_DIR.'phptal')) mkdir(\metadigit\core\CACHE_DIR.'phptal', 0750);
		//chown(CACHE_DIR.'phptal','apache');
		$PhpTAL->setPhpCodeDestination(\metadigit\core\CACHE_DIR.'phptal');
		if(!is_null($class = $this->preFilterClass)) {
			$this->trace(LOG_DEBUG, 1, __FUNCTION__, 'set preFilter: '.$class);
			$PhpTAL->setPreFilter(new $class);
		}
		if(!is_null($class = $this->postFilterClass)) {
			$this->trace(LOG_DEBUG, 1, __FUNCTION__, 'set postFilter: '.$class);
			$PhpTAL->setPostFilter(new $class);
		}
		// assign Model values
		foreach($Res->getData() as $k => $v) {
			$PhpTAL->set($k, $v);
		}
		// execute
		$PhpTAL->echoExecute();
	}
}