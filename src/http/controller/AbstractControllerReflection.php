<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\http\controller;
use metadigit\core\http\Exception,
	metadigit\core\http\Request,
	metadigit\core\http\Response,
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
	 * @throws \metadigit\core\http\Exception
	 * @return array
	 */
	static function analyzeHandle(AbstractController $Controller) {
		// check implementation methods signature
		$config = [];
		$ReflClass = new ReflectionClass($Controller);
		$reflMethods = $ReflClass->getMethods();
		foreach($reflMethods as $ReflMethod) {
			$methodName = $ReflMethod->getName();
			$methodClass = $ReflMethod->getDeclaringClass()->getName();
			// skip framework methods
			if(fnmatch('metadigit\core\*', $methodClass, FNM_NOESCAPE)) continue;
			// check signature of preHanlde & postHanlde hooks
			if(in_array($methodName, ['preHandle','postHandle'])) {
				if(!$ReflMethod->isProtected()) throw new Exception(101, [$methodClass,$methodName]);
			// check signature of handling methods (skip protected/private methods, they can't be handler!)
			} elseif($ReflMethod->isPublic() && $methodName=='doHandle') {
				// routing
				$DocComment = $ReflMethod->getDocComment();
				if($DocComment->hasTag('routing')) {
					$route = $DocComment->getTag('routing');
					$route = str_replace('/', '\/', $route);
					$route = preg_replace('/<(\w+)>/', '(?<$1>[^\/]+)', $route);
					$route = preg_replace('/<(\w+):([^>]+)>/', '(?<$1>$2)', $route);
					$config['route'] = '/'.$route.'$/';
				}
				// parameters
				foreach($ReflMethod->getParameters() as $i => $ReflParam) {
					switch($i){
						case 0:
							if(!$ReflParam->getClass()->getName() == Request::class)
								throw new Exception(102, [$methodClass, $methodName, $i+1, Request::class]);
							break;
						case 1:
							if(!$ReflParam->getClass()->getName() == Response::class)
								throw new Exception(102, [$methodClass, $methodName, $i+1, Response::class]);
							break;
						default:
							$config['params'][$i]['name'] = $ReflParam->getName();
							$config['params'][$i]['class'] = (!is_null($ReflParam->getClass())) ? $ReflParam->getClass()->getName() : null;
							$config['params'][$i]['type'] = $ReflParam->getType();
							$config['params'][$i]['optional'] = $ReflParam->isOptional();
							$config['params'][$i]['default'] = ($ReflParam->isDefaultValueAvailable()) ? $ReflParam->getDefaultValue() : null;
					}
				}
			}
		}
		return $config;
	}
}
