<?php
namespace renovant\core\http\view;
use const renovant\core\trace\T_INFO;
use renovant\core\sys,
	renovant\core\http\Request,
	renovant\core\http\Response,
	renovant\core\http\Exception,
	renovant\core\http\ViewInterface;
class PhpTALView implements ViewInterface {

	/** template suffixes */
	const TEMPLATE_SUFFIXES = '.html|.xml';
	/** customizable PhpTAL pre-filter class, must implements PHPTAL_Filter
	 * @var string */
	protected $preFilterClass = null;
	/** customizable PhpTAL post-filter class, must implements PHPTAL_Filter
	 * @var string */
	protected $postFilterClass = null;

	/**
	 * @param Request $Req
	 * @param Response $Res
	 * @param null $resource
	 * @param array|null $options
	 * @throws Exception
	 * @throws \PHPTAL_ConfigurationException
	 */
	function render(Request $Req, Response $Res, $resource=null, array $options=null) {
		$template = null;
		$suffixes = explode('|', static::TEMPLATE_SUFFIXES);
		foreach($suffixes as $suffix) {
			if(file_exists($template = $Req->getAttribute('RESOURCES_DIR').$resource.$suffix)) {
				sys::trace(LOG_DEBUG, T_INFO, 'template: '.$template, null, 'sys.http.PhpTALView->render');
				$this->execTemplate($template, $Res);
				return;
			}
		}
		throw new Exception(201, ['PHPTal Template', $Req->getAttribute('RESOURCES_DIR').$resource.static::TEMPLATE_SUFFIXES]);
	}

	function setPreFilter($class) {
		$this->preFilterClass = $class;
	}

	function setPostFilter($class) {
		$this->postFilterClass = $class;
	}

	/**
	 * @param $template
	 * @param Response $Res
	 * @throws \PHPTAL_ConfigurationException
	 */
	private function execTemplate($template, Response $Res) {
		// build PHPTAL object & set options
		$PhpTAL = new \PHPTAL($template);
		$PhpTAL->setEncoding('UTF-8');
		$PhpTAL->setOutputMode(\PHPTAL::HTML5);
		if(!file_exists(\renovant\core\CACHE_DIR.'phptal')) mkdir(\renovant\core\CACHE_DIR.'phptal', 0750);
		$PhpTAL->setPhpCodeDestination(\renovant\core\CACHE_DIR.'phptal');
		if(!is_null($class = $this->preFilterClass)) {
			sys::trace(LOG_DEBUG, T_INFO, 'set preFilter: '.$class, null, 'sys.http.PhpTALView->execTemplate');
			$PhpTAL->addPreFilter(new $class);
		}
		if(!is_null($class = $this->postFilterClass)) {
			sys::trace(LOG_DEBUG, T_INFO, 'set postFilter: '.$class, null, 'sys.http.PhpTALView->execTemplate');
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
