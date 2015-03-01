<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\console\controller;
use metadigit\core\console\Exception,
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
	 * @throws \metadigit\core\console\Exception
	 * @return array
	 */
	static function analyzeHandle(AbstractController $Controller) {
		// check implementation methods signature
		$handle = [];
		$ReflClass = new ReflectionClass($Controller);
		$reflMethods = $ReflClass->getMethods();
		foreach($reflMethods as $ReflMethod) {
			$methodName = $ReflMethod->getName();
			$methodClass = $ReflMethod->getDeclaringClass()->getName();
			// skip framework methods
			if(fnmatch('metadigit\core\*', $methodClass, FNM_NOESCAPE)) continue;
			// check signature of preHanlde & postHanlde hooks
			if(in_array($methodName,['preHandle','postHandle'])) {
				if(!$ReflMethod->isProtected()) throw new Exception(101, [$methodClass, $methodName]);
			// check signature of handling methods (skip protected/private methods, they can't be handler!)
			} elseif($ReflMethod->isPublic() && $methodName=='doHandle') {
				foreach($ReflMethod->getParameters() as $i => $ReflParam) {
					switch($i){
						case 0:
							if(!$ReflParam->getClass()->getName() == 'metadigit\core\cli\Request')
								throw new Exception(102, [$methodClass,$methodName,$i+1,'metadigit\core\cli\Request']);
							break;
						case 1:
							if(!$ReflParam->getClass()->getName() == 'metadigit\core\cli\Response')
								throw new Exception(102, [$methodClass,$methodName,$i+1,'metadigit\core\cli\Response']);
							break;
						default:
							$handle['params'][$i]['name'] = $ReflParam->getName();
							$handle['params'][$i]['class'] = (!is_null($ReflParam->getClass())) ? $ReflParam->getClass()->getName() : null;
							$handle['params'][$i]['type'] = $ReflParam->getType();
							$handle['params'][$i]['optional'] = $ReflParam->isOptional();
							$handle['params'][$i]['default'] = ($ReflParam->isDefaultValueAvailable()) ? $ReflParam->getDefaultValue() : null;
					}
				}
			}
		}
		return $handle;
	}
}
