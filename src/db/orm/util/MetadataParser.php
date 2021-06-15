<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace renovant\core\db\orm\util;
use renovant\core\db\orm\Exception,
	renovant\core\db\orm\Repository,
	renovant\core\util\reflection\ReflectionClass;
/**
 * ORM Metadata Parser
 * @author Daniele Sciacchitano <dan@renovant.tech>
 */
class MetadataParser {

	/**
	 * @param $entityClass
	 * @return array
	 * @throws Exception
	 * @throws \ReflectionException
	 * @throws \Exception
	 */
	static function parse($entityClass): array {

		$metadata = [];

		$RefClass = new ReflectionClass($entityClass);

		// SQL source / target / functions
		$DocComment = $RefClass->getDocComment();
		if(!$tags = $DocComment->getTagValues('orm')) throw new Exception(602, [$entityClass]);
		$tag = [];
		foreach($tags as $t) $tag = array_merge($tag, $t);
		if(isset($tag['source'])) {
			$metadata[Repository::META_SQL]['source'] = $metadata[Repository::META_SQL]['target'] = $tag['source'];
		}
		if(isset($tag['target'])) {
			$metadata[Repository::META_SQL]['target'] = $tag['target'];
		}
		if(isset($tag['insertFn'])) {
			$metadata[Repository::META_SQL]['insertFn'] = $tag['insertFn'];
		}
		if(isset($tag['updateFn'])) {
			$metadata[Repository::META_SQL]['updateFn'] = $tag['updateFn'];
		}
		if(isset($tag['deleteFn'])) {
			$metadata[Repository::META_SQL]['deleteFn'] = $tag['deleteFn'];
		}
		foreach($metadata[Repository::META_SQL] as $k=>$v) {
			if(is_null($v)) throw new Exception(603, [$entityClass, $k]);
		}

		// ORM events
		$metadata[Repository::META_EVENTS] = [];
		if($DocComment->hasTag('orm-events')) {
			$tagValues = $DocComment->getTagValues('orm-events');
			foreach($tagValues as $value) {
				foreach($value as $k => $v) $metadata[Repository::META_EVENTS]['orm:'.strtolower($k)] = $v;
			}
		}

		// ORM criteria
		$metadata[Repository::META_CRITERIA] = [];
		if($DocComment->hasTag('orm-criteria')) {
			$tagValues = $DocComment->getTagValues('orm-criteria');
			foreach($tagValues as $value) {
				foreach($value as $k => $v) $metadata[Repository::META_CRITERIA][$k] = $v;
			}
		}

		// ORM order by
		$metadata[Repository::META_FETCH_ORDERBY] = [];
		if($DocComment->hasTag('orm-order-by')) {
			$tagValues = $DocComment->getTagValues('orm-order-by');
			foreach($tagValues as $value) {
				foreach($value as $k => $v) $metadata[Repository::META_FETCH_ORDERBY][$k] = $v;
			}
		}

		// ORM fetch subsets
		if($DocComment->hasTag('orm-fetch-subset')) {
			$tagValues = $DocComment->getTagValues('orm-fetch-subset');
			foreach($tagValues as $value) {
				foreach($value as $k => $v) $metadata[Repository::META_FETCH_SUBSETS][$k] = trim($v);
			}
		}

		// ORM validate subsets
		if($DocComment->hasTag('orm-validate-subset')) {
			$tagValues = $DocComment->getTagValues('orm-validate-subset');
			foreach($tagValues as $value) {
				foreach($value as $k => $v) $metadata[Repository::META_VALIDATE_SUBSETS][$k] = trim(str_replace(' ','',$v));
			}
		}

		// properties configuration
		foreach($RefClass->getProperties() as $RefProperty) {
			$prop = $RefProperty->getName();
			$DocComment = $RefProperty->getDocComment();
			if(!$DocComment->hasTag('orm')) continue;
			$metadata[Repository::META_PROPS][$prop] = ['type'=>'string', 'null'=>false, 'readonly'=>false];
			if($tag = $DocComment->getTag('orm')) {
				if(isset($tag['type']) && !in_array($tag['type'], ['string','integer','float','boolean','date','time','datetime','microdatetime','object','array'])) throw new Exception(604, [$entityClass, $prop, $tag['type']]);
				$metadata[Repository::META_PROPS][$prop] = array_merge($metadata[Repository::META_PROPS][$prop], (array)$tag);
				if(isset($tag['primarykey'])) $metadata[Repository::META_PKEYS][] = $prop;
			}
		}

		// build PK criteria
		$criteria = [];
		foreach($metadata[Repository::META_PKEYS] as $i=>$k) {
			$criteria[] = $k.',EQ,?';
		}
		$metadata[Repository::META_PKCRITERIA] = implode('|',$criteria);

		return $metadata;
	}
}
