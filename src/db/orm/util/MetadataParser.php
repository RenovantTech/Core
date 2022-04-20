<?php
namespace renovant\core\db\orm\util;
use renovant\core\db\orm\Exception,
	renovant\core\util\reflection\DocComment,
	renovant\core\util\reflection\ReflectionClass;
class MetadataParser {

	/**
	 * @throws Exception
	 * @throws \ReflectionException
	 * @throws \Exception
	 */
	static function parse(string $entityClass): array {
		$RefClass = new ReflectionClass($entityClass);

		// SQL source / target / functions
		$sql = [];
		$DocComment = $RefClass->getDocComment();
		if(!$tags = $DocComment->getTagValues('orm')) throw new Exception(602, [$entityClass]);
		$tag = [];
		foreach($tags as $t) $tag = array_merge($tag, $t);
		if(isset($tag['source'])) {
			$sql['source'] = $sql['target'] = $tag['source'];
		}
		if(isset($tag['target'])) {
			$sql['target'] = $tag['target'];
		}
		if(isset($tag['insertFn'])) {
			$sql['insertFn'] = $tag['insertFn'];
		}
		if(isset($tag['updateFn'])) {
			$sql['updateFn'] = $tag['updateFn'];
		}
		if(isset($tag['deleteFn'])) {
			$sql['deleteFn'] = $tag['deleteFn'];
		}
		foreach($sql as $k=>$v) {
			if(is_null($v)) throw new Exception(603, [$entityClass, $k]);
		}

		// ORM events
		$events = [];
		if($DocComment->hasTag('orm-events')) {
			$tagValues = $DocComment->getTagValues('orm-events');
			foreach($tagValues as $value) {
				foreach($value as $k => $v) $events['orm:'.strtolower($k)] = $v;
			}
		}

		// ORM criteria
		$criteria = [];
		if($DocComment->hasTag('orm-criteria')) {
			$tagValues = $DocComment->getTagValues('orm-criteria');
			foreach($tagValues as $value) {
				foreach($value as $k => $v) $criteria[$k] = $v;
			}
		}

		// ORM order by
		$fetchOrderBy = [];
		if($DocComment->hasTag('orm-order-by')) {
			$tagValues = $DocComment->getTagValues('orm-order-by');
			foreach($tagValues as $value) {
				foreach($value as $k => $v) $fetchOrderBy[$k] = $v;
			}
		}

		// ORM fetch subsets
		$fetchSubsets = [];
		if($DocComment->hasTag('orm-fetch-subset')) {
			$tagValues = $DocComment->getTagValues('orm-fetch-subset');
			foreach($tagValues as $value) {
				foreach($value as $k => $v) $fetchSubsets[$k] = trim($v);
			}
		}

		// ORM validate subsets
		$validateSubsets = [];
		if($DocComment->hasTag('orm-validate-subset')) {
			$tagValues = $DocComment->getTagValues('orm-validate-subset');
			foreach($tagValues as $value) {
				foreach($value as $k => $v) $validateSubsets[$k] = trim(str_replace(' ','',$v));
			}
		}

		// properties configuration
		$pKeys = $properties = [];
		foreach($RefClass->getProperties() as $RefProperty) {
			$prop = $RefProperty->getName();
			/** @var DocComment $DocComment */
			$DocComment = $RefProperty->getDocComment();
			if(!$DocComment->hasTag('orm')) continue;
			$properties[$prop] = ['type'=>'string', 'null'=>false, 'readonly'=>false];
			if($tag = $DocComment->getTag('orm')) {
				if(isset($tag['type']) && !in_array($tag['type'], ['string','integer','float','boolean','date','time','datetime','microdatetime','object','array'])) throw new Exception(604, [$entityClass, $prop, $tag['type']]);
				$properties[$prop] = array_merge($properties[$prop], (array)$tag);
				if(isset($tag['primarykey'])) $pKeys[] = $prop;
			}
		}

		// build PK criteria
		$pkCriteria = [];
		foreach($pKeys as $k) {
			$pkCriteria[] = $k.',EQ,?';
		}
		$pkCriteria = implode('|',$pkCriteria);

		return [$events, $criteria, $fetchSubsets, $fetchOrderBy, $validateSubsets, $pKeys, $pkCriteria, $properties, $sql];
	}
}
