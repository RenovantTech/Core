<?php

$routes = [];
foreach ($this->routes as $url => $controllerID) {
	$url = str_replace('/', '\/', $url);
	$url = preg_replace('/<(\w+)>/', '(?<$1>[^\/]+)', $url);
	$url = preg_replace('/<(\w+):([^>]+)>/', '(?<$1>$2)', $url);
	$url = '/'.$url.'/';
	$routes[$url] = $controllerID;
}
$this->routes = $routes;
