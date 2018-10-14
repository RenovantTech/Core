<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace metadigit\core\http\controller;
use metadigit\core\http\Exception,
	metadigit\core\util\reflection\ReflectionClass;
/**
 * Utility class for AbstractController
 * @internal
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class AbstractControllerReflection {

	/**
	 * Return Controller's actions metadata
	 * @param AbstractController $Controller
	 * @return array
	 * @throws \ReflectionException
	 * @throws \metadigit\core\http\Exception
	 */
	static function analyzeHandle(AbstractController $Controller) {
		$config = [];
		$RefClass = new ReflectionClass($Controller);
		$refMethods = $RefClass->getMethods();
		foreach($refMethods as $RefMethod) {
			$methodName = $RefMethod->getName();
			$methodClass = $RefMethod->getDeclaringClass()->getName();
			// skip framework methods
			if(fnmatch('metadigit\core\*', $methodClass, FNM_NOESCAPE)) continue;
			// check signature of preHandle & postHandle hooks
			if(in_array($methodName, ['preHandle','postHandle'])) {
				if(!$RefMethod->isProtected()) throw new Exception(101, [$methodClass,$methodName]);
			// check signature of handling methods (skip protected/private methods, they can't be handler!)
			} elseif($RefMethod->isPublic() && $methodName=='doHandle') {
				// routing
				$DocComment = $RefMethod->getDocComment();
				if($DocComment->hasTag('routing')) {
					$route = $DocComment->getTag('routing');
					$route = str_replace('/', '\/', $route);
					$route = preg_replace('/<(\w+)>/', '(?<$1>[^\/]+)', $route);
					$route = preg_replace('/<(\w+):([^>]+)>/', '(?<$1>$2)', $route);
					$config['route'] = '/'.$route.'$/';
				}
				// parameters
				foreach($RefMethod->getParameters() as $i => $RefParam) {
					$config['params'][$i]['name'] = $RefParam->getName();
					$config['params'][$i]['class'] = !is_null($RefParam->getClass()) ? $RefParam->getClass()->getName() : null;
					$config['params'][$i]['type'] = $RefParam->getType();
					$config['params'][$i]['optional'] = $RefParam->isOptional();
					$config['params'][$i]['default'] = $RefParam->isDefaultValueAvailable() ? $RefParam->getDefaultValue() : null;
				}
			}
		}
		return $config;
	}
}
