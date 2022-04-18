<?php
namespace renovant\core\http\controller;
use renovant\core\http\Exception,
	renovant\core\util\reflection\ReflectionClass;
/**
 * @internal
 */
class ActionControllerReflection {

	/**
	 * Return Controller's actions metadata
	 * @param ActionController $Controller
	 * @return array
	 * @throws Exception
	 * @throws \ReflectionException
	 */
	static function analyzeActions(ActionController $Controller) {
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
			} elseif($RefMethod->isPublic() && $methodName!='handle' && substr($methodName,0,1)!='_') {
				$action = $methodName;
				$config[$action] = [];
				// routing
				$DocComment = $RefMethod->getDocComment();
				if($DocComment->hasTag('routing')) {
					$tag = $DocComment->getTag('routing');
					$config[$action]['method'] = isset($tag['method']) ? $tag['method'] : '*';
					if(isset($tag['pattern'])) {
						$pattern = str_replace('/', '\/', $tag['pattern']);
						$pattern = preg_replace('/<(\w+)>/', '(?<$1>[^\/]+)', $pattern);
						$pattern = preg_replace('/<(\w+):([^>]+)>/', '(?<$1>$2)', $pattern);
						$config[$action]['pattern'] = '/'.$pattern.'/';
					} else $config[$action]['pattern'] = '/'.$action.'/';
				} else {
					$config[$action]['method'] = '*';
					if($action == constant(get_class($Controller).'::DEFAULT_ACTION')) $config[$action]['pattern'] = '/^$/';
					else $config[$action]['pattern'] = '/^'.$action.'$/';
				}
				// parameters
				foreach($RefMethod->getParameters() as $i => $RefParam) {
					$config[$action]['params'][$i]['name'] = $RefParam->getName();
					$config[$action]['params'][$i]['class'] = ($RefParam->getType() && !$RefParam->getType()->isBuiltin()) ? (new ReflectionClass($RefParam->getType()->getName()))->getName() : null;
					$config[$action]['params'][$i]['type'] = ($RefParam->getType() && $RefParam->getType()->isBuiltin()) ? $RefParam->getType()->getName() : null;
					$config[$action]['params'][$i]['optional'] = $RefParam->isOptional();
					$config[$action]['params'][$i]['default'] = $RefParam->isDefaultValueAvailable() ? $RefParam->getDefaultValue() : null;
				}
			}
		}
		return $config;
	}
}
