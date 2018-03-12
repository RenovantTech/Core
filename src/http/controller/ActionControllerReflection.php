<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\http\controller;
use metadigit\core\http\Exception,
	metadigit\core\util\reflection\ReflectionClass;
/**
 * Utility class for ActionController
 * @internal
 * @author Daniele Sciacchitano <dan@metadigit.it>
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
			// check signature of preHandle & postHandle hooks
			if(in_array($methodName, ['preHandle','postHandle'])) {
				if(!$RefMethod->isProtected()) throw new Exception(101, [$methodClass,$methodName]);
			// check signature of handling methods (skip protected/private methods, they can't be handler!)
			} elseif($RefMethod->isPublic() && substr($methodName,-6)=='Action') {
				$action = substr($methodName,0,-6);
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
					$config[$action]['params'][$i]['class'] = !is_null($RefParam->getClass()) ? $RefParam->getClass()->getName() : null;
					$config[$action]['params'][$i]['type'] = $RefParam->getType();
					$config[$action]['params'][$i]['optional'] = $RefParam->isOptional();
					$config[$action]['params'][$i]['default'] = $RefParam->isDefaultValueAvailable() ? $RefParam->getDefaultValue() : null;
				}
			}
		}
		return $config;
	}
}
