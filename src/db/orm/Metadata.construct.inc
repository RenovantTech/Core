<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
use metadigit\core\db\orm\Exception,
	metadigit\core\util\reflection\ReflectionClass;

$RefClass = new ReflectionClass($entityClass);

// SQL source / target / functions
$DocComment = $RefClass->getDocComment();
if(!$tags = $DocComment->getTagValues('orm')) throw new Exception(602, [$entityClass]);
$tag = [];
foreach($tags as $t) $tag = array_merge($tag, $t);
if(isset($tag['source'])) {
	$this->sql['source'] = $this->sql['target'] = $tag['source'];
}
if(isset($tag['target'])) {
	$this->sql['target'] = $tag['target'];
}
if(isset($tag['insertFn'])) {
	$this->sql['insertFn'] = $tag['insertFn'];
}
if(isset($tag['updateFn'])) {
	$this->sql['updateFn'] = $tag['updateFn'];
}
if(isset($tag['deleteFn'])) {
	$this->sql['deleteFn'] = $tag['deleteFn'];
}
foreach($this->sql as $k=>$v) {
	if(is_null($v)) throw new Exception(603, [$entityClass, $k]);
}

// ORM criteria
if($DocComment->hasTag('orm-criteria')) {
	$tagValues = $DocComment->getTagValues('orm-criteria');
	foreach($tagValues as $value) {
		foreach($value as $k => $v) $this->criteria[$k] = $v;
	}
}

// ORM order by
if($DocComment->hasTag('orm-order-by')) {
	$tagValues = $DocComment->getTagValues('orm-order-by');
	foreach($tagValues as $value) {
		foreach($value as $k => $v) $this->order[$k] = $v;
	}
}

// ORM subset
if($DocComment->hasTag('orm-subset')) {
	$tagValues = $DocComment->getTagValues('orm-subset');
	foreach($tagValues as $value) {
		foreach($value as $k => $v) $this->subset[$k] = explode(',',str_replace(' ','',$v));
	}
}

// properties configuration
foreach($RefClass->getProperties() as $RefProperty) {
	$prop = $RefProperty->getName();
	$DocComment = $RefProperty->getDocComment();
	if(!$DocComment->hasTag('orm')) continue;
	$this->properties[$prop] = ['type'=>'string', 'null'=>false, 'readonly'=>false];
	if($tag = $DocComment->getTag('orm')) {
		if(isset($tag['type']) && !in_array($tag['type'], ['string','integer','float','boolean','date','datetime','object','array'])) throw new Exception(604, [$entityClass, $prop, $tag['type']]);
		$this->properties[$prop] = array_merge($this->properties[$prop], (array)$tag);
		if(isset($tag['primarykey'])) $this->pkeys[] = $prop;
	}
}

// build PK criteria
$criteria = [];
foreach($this->pkeys as $i=>$k) {
	$criteria[] = $k.',EQ,?';
}
$this->pkCriteria = implode('|',$criteria);
