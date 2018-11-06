<?php

namespace Bootstrap;

use App\Exceptions\HttpNotFoundException;
use App\Libs\Session;
use App\Libs\Request;
use App\Libs\Settings;
use App\Libs\Router;
use App\Libs\Loader;
use App\Libs\View;
use App\Exceptions\ControllerNotFoundException;
use Exception;

class FrontController {
	private $controller;
	private $action;
	private $parameters = [];
	private $lang = "en";

	private $route;
	private $request;

	function __construct() 
	{
		Session::start();
	}

	public function bootstrap()
	{
		$this->request = Request::createFromGlobals();

		try {
			include DOCROOT . APP_PATH . 'filters.php';
			include DOCROOT . APP_PATH . 'routes.php';
			$this->route();
			include DOCROOT . APP_PATH . 'globals.php';
			include DOCROOT . APP_PATH . 'helpers.php';
			$this->dispatch();
		} catch(Exception $ex) {
			View::create('errors/error')->set('messages', $ex->getMessage())->render();
			exit;
		}
	}
}