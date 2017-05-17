<?php
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

define('BASE_PATH', $_SERVER['DOCUMENT_ROOT'].'/');
define('ENVIRONMENT', isset($_SERVER['SS_ENV']) ? $_SERVER['SS_ENV'] : 'development');
switch (ENVIRONMENT)
{
	case 'development':
		error_reporting(-1);
		ini_set('display_errors', 1);
	break;
	case 'production':

	break;
}

if (!file_exists(BASE_PATH.'vendor/autoload.php')) {
	die('Install Composer and packages from composer.json');
} else {
	include BASE_PATH.'vendor/autoload.php';
}

//var_dump($_GET);

$route0 = new Route('', ['_controller' => 'IndexController']);
$route1 = new Route('/main', ['_controller' => 'MainController']);
$route2 = new Route('/medal', ['_controller' => 'MedalController']);
$route3 = new Route('/test', ['_controller' => 'TestController']);

$routes = new RouteCollection();

$routes->add('index', $route0);
$routes->add('medal', $route1);
$routes->add('main', $route2);
$routes->add('test', $route3);

$context = new RequestContext($_SERVER['REQUEST_URI']);

$matcher = new UrlMatcher($routes, $context);

try {
	$parameters = $matcher->match('/'.$_GET['q']);
	//var_dump($parameters);
	include BASE_PATH.'controllers/'.$parameters['_controller'].'.php';
	$controller = new $parameters['_controller']();
	$controller->index();
} catch (Symfony\Component\Routing\Exception\ResourceNotFoundException $e) {
	die($e->getMessage());
}


