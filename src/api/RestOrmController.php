<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\api;
use const metadigit\core\http\ENGINE_JSON;
use const metadigit\core\trace\{T_ERROR, T_INFO};
use metadigit\core\sys,
	metadigit\core\db\orm\Exception,
	metadigit\core\db\orm\Repository,
	metadigit\core\http\Request,
	metadigit\core\http\Response;
/**
 * REST ActionController proxy for ORM Repositories
 */
class RestOrmController extends \metadigit\core\http\controller\ActionController {

	/** JSON data param
	 * @var string */
	protected $responseData = 'data';
	/** JSON errors param
	 * @var string */
	protected $responseErrors = 'errors';
	/** JSON total param
	 * @var string */
	protected $responseTotal = 'total';
	/** default View engine
	 * @var string */
	protected $viewEngine = ENGINE_JSON;
	/** Urls to resources mappings
	 * @var array */
	protected $routes = [];

	/**
	 * CREATE action (HTTP POST)
	 * @routing(method="POST", pattern="^<resource>$")
	 * @param Request $Req
	 * @param Response $Res
	 * @param string $resource
	 * @param string|null $subset
	 * @throws \metadigit\core\context\ContextException
	 * @throws \metadigit\core\event\EventDispatcherException
	 * @throws \Exception
	 */
	function createAction(Request $Req, Response $Res, $resource, $subset=null) {
		$Repository = $this->getRepository($resource);
		$data = (array) json_decode($Req->getRawData());
		try {
			$data = $Repository->insert(null, $data, true, Repository::FETCH_JSON, $subset);
			$Res->set($this->responseData, $data);
		} catch(Exception $Ex) {
			switch($Ex->getCode()) {
				case 100:
					http_response_code(500);
					$Ex->trace();
					trigger_error(get_class($Repository).' EXCEPTION', E_USER_ERROR);
					break;
				case 500:
					http_response_code(400);
					$Res->set($this->responseErrors, $Ex->getData());
					sys::trace(LOG_ERR, T_INFO, 'validation error', $Ex->getData());
					break;
			}
			throw $Ex;
		}
	}

	/**
	 * DELETE action (HTTP DELETE)
	 * @routing(method="DELETE", pattern="^<resource>/<id>$")
	 * @param Response $Res
	 * @param string $resource
	 * @param mixed $id
	 * @param string|null $subset
	 * @throws \metadigit\core\context\ContextException
	 * @throws \metadigit\core\event\EventDispatcherException
	 * @throws \Exception
	 */
	function deleteAction(Response $Res, $resource, $id, $subset=null) {
		$Repository = $this->getRepository($resource);
		$id = (strpos($id,'+')>0) ? explode('+',$id) : $id;
		try {
			if($data = $Repository->delete($id, Repository::FETCH_JSON, $subset)) {
				$Res->set($this->responseData, $data);
			} else $Res->set('errCode', 'UNKNOWN_ENTITY');
		} catch(Exception $Ex) {
			http_response_code(500);
			$Ex->trace();
			trigger_error(get_class($Repository).' EXCEPTION', E_USER_ERROR);
			throw $Ex;
		}
	}

	/**
	 * READ action (HTTP GET)
	 * @routing(method="GET", pattern="^<resource>$")
	 * @param Response $Res
	 * @param string $resource
	 * @param string $criteriaExp
	 * @param string $orderExp
	 * @param integer $page
	 * @param integer $pageSize
	 * @param string|null $subset
	 * @throws \metadigit\core\context\ContextException
	 * @throws \metadigit\core\event\EventDispatcherException
	 * @throws \Exception
	 */
	function readAllAction(Response $Res, $resource, $criteriaExp=null, $orderExp=null, $page, $pageSize, $subset=null) {
		$Repository = $this->getRepository($resource);
		try {
			$data = $Repository->fetchAll($page, $pageSize, $orderExp, $criteriaExp, Repository::FETCH_JSON, $subset);
			$total = $Repository->count($criteriaExp);
			$Res->set($this->responseTotal, $total)
				->set($this->responseData, $data);
		} catch(Exception $Ex) {
			http_response_code(500);
			$Ex->trace();
			trigger_error(get_class($Repository).' EXCEPTION', E_USER_ERROR);
		}
	}

	/**
	 * READ action (HTTP GET)
	 * @routing(method="GET", pattern="^<resource>/<id>$")
	 * @param Response $Res
	 * @param string $resource
	 * @param mixed $id Entity id
	 * @param string|null $subset
	 * @throws \metadigit\core\context\ContextException
	 * @throws \metadigit\core\event\EventDispatcherException
	 * @throws \Exception
	 */
	function readAction(Response $Res, $resource, $id, $subset=null) {
		$Repository = $this->getRepository($resource);
		$id = (strpos($id,'+')>0) ? explode('+',$id) : $id;
		try {
			$data = $Repository->fetch($id, Repository::FETCH_JSON, $subset);
			$Res->set($this->responseData, $data);
		} catch(Exception $Ex) {
			http_response_code(500);
			$Ex->trace();
			trigger_error(get_class($Repository).' EXCEPTION', E_USER_ERROR);
		}
	}

	/**
	 * UPDATE action (HTTP PUT)
	 * @routing(method="PUT", pattern="^<resource>/<id>$")
	 * @param Request $Req
	 * @param Response $Res
	 * @param string $resource
	 * @param mixed $id Entity id
	 * @param string|null $subset
	 * @throws \metadigit\core\context\ContextException
	 * @throws \metadigit\core\event\EventDispatcherException
	 * @throws \Exception
	 */
	function updateAction(Request $Req, Response $Res, $resource, $id, $subset=null) {
		$Repository = $this->getRepository($resource);
		$data = (array) json_decode($Req->getRawData());
		$id = (strpos($id,'+')>0) ? explode('+',$id) : $id;
		try {
			$data = $Repository->update($id, $data, true, Repository::FETCH_JSON, $subset);
			$Res->set($this->responseData, $data);
		} catch(Exception $Ex) {
			switch($Ex->getCode()) {
				case 300:
					http_response_code(500);
					$Ex->trace();
					trigger_error(get_class($Repository).' EXCEPTION', E_USER_ERROR);
					break;
				case 500:
					http_response_code(400);
					$Res->set($this->responseErrors, $Ex->getData());
					sys::trace(LOG_DEBUG, T_ERROR, 'validation error', print_r($Ex->getData(),true));
					break;
			}
		}
	}

	/**
	 * @param string $resource mapped URL
	 * @return \metadigit\core\db\orm\Repository
	 * @throws \metadigit\core\context\ContextException
	 * @throws \metadigit\core\event\EventDispatcherException
	 */
	private function getRepository($resource) {
		$repositoryID = $this->routes[$resource];
		sys::trace(LOG_DEBUG, T_INFO, 'ORM Repository: '.$repositoryID);
		/** @var \metadigit\core\db\orm\Repository $Repository */
		$Repository = sys::context()->get($repositoryID);
		return $Repository;
	}
}
