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

	public function route()
	{
		$arrPath = self::URItoArray(ltrim($this->request->getPathInfo(), '/'));

		//Check the language. We don't consider it part of the route.
		if(is_numeric(array_search($arrPath[0], Settings::$availableLangs))) {
			$this->lang = $arrPath[0];

			array_shift($arrPath);
			if(empty($arrPath)) {
				array_push($arrPath, "");
			}
		}

		$this->ProcessRequest($arrPath);
		return;
	}

	public function dispatch()
	{

		Loader::configureORM();

		//Load the Controller
		if($this->isController($this->controller))
		{
			//Here we run the filters that are registered to the route
			Router::runFilters($this->route);

			$filename = DOCROOT . CONTROLLERS_PATH . $this->controller . '.php';
			require_once $filename;
			$cName = $this->controller;
			$controller = new $cName();
			$controller->setLang($this->lang);

			//Execute Pre Action
			$controller->before_action($this->action, $this->parameters);

			//Execute Modules
			foreach($this->route->modules as $module)
			{
				$module->execute(Request::createFromGlobals());
			}

			//Execute Action
			Loader::runController($controller, $this->action, $this->parameters);
		} else
		{
			throw new ControllerNotFoundException("Controller " . $this->controller . " not found!");
		}
	}
}