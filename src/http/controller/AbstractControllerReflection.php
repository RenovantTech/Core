<?php
namespace renovant\core\http\controller;
use renovant\core\http\Exception,
	renovant\core\util\reflection\ReflectionClass;
/**
 * @internal
 */
class AbstractControllerReflection {

	/**
	 * Return Controller's actions metadata
	 * @param AbstractController $Controller
	 * @return array
	 * @throws \ReflectionException
	 * @throws \renovant\core\http\Exception
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
//					$config['params'][$i]['class'] = !is_null($RefParam->getClass()) ? $RefParam->getClass()->getName() : null;
					$config['params'][$i]['class'] = ($RefParam->getType() && !$RefParam->getType()->isBuiltin()) ? (new ReflectionClass($RefParam->getType()->getName()))->getName() : null;
//					$config['params'][$i]['type'] = $RefParam->getType();
					$config['params'][$i]['type'] = ($RefParam->getType() && $RefParam->getType()->isBuiltin()) ? $RefParam->getType()->getName() : null;
					$config['params'][$i]['optional'] = $RefParam->isOptional();
					$config['params'][$i]['default'] = $RefParam->isDefaultValueAvailable() ? $RefParam->getDefaultValue() : null;
				}
			}
		}
		return $config;
	}
}
