<?php
namespace renovant\core\console\controller;
use renovant\core\console\Exception,
	renovant\core\util\reflection\ReflectionClass;
/**
 * Utility class for AbstractController
 * @internal
 */
class AbstractControllerReflection {

	/**
	 * Return Controller's actions metadata
	 * @param AbstractController $Controller
	 * @return array
	 * @throws \renovant\core\console\Exception|\ReflectionException
	 */
	static function analyzeHandle(AbstractController $Controller) {
		$config = [];
		$RefClass = new ReflectionClass($Controller);
		$refMethods = $RefClass->getMethods();
		foreach($refMethods as $RefMethod) {
			$methodName = $RefMethod->getName();
			$methodClass = $RefMethod->getDeclaringClass()->getName();
			// skip framework methods
			if(fnmatch('renovant\core\*', $methodClass, FNM_NOESCAPE)) continue;
			// check signature of preHandle & postHandle hooks
			if(in_array($methodName,['preHandle','postHandle'])) {
				if(!$RefMethod->isProtected()) throw new Exception(101, [$methodClass, $methodName]);
			// check signature of handling methods (skip protected/private methods, they can't be handler!)
			} elseif($RefMethod->isPublic() && $methodName=='doHandle') {
				foreach($RefMethod->getParameters() as $i => $RefParam) {
					$config['params'][$i]['name'] = $RefParam->getName();
					$config['params'][$i]['class'] = ($RefParam->getType() && !$RefParam->getType()->isBuiltin()) ? (new ReflectionClass($RefParam->getType()->getName()))->getName() : null;
					$config['params'][$i]['type'] = ($RefParam->getType() && $RefParam->getType()->isBuiltin()) ? $RefParam->getType()->getName() : null;
					$config['params'][$i]['optional'] = $RefParam->isOptional();
					$config['params'][$i]['default'] = $RefParam->isDefaultValueAvailable() ? $RefParam->getDefaultValue() : null;
				}
			}
		}
		return $config;
	}
}
