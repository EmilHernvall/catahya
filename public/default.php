<?php

class PHPError extends Exception {}

define('STARTTIME', microtime(1));

ini_set("display_errors", 1);
error_reporting(E_ALL | E_STRICT);
ob_start("ob_gzhandler");

function errorHandle($errno, $errstr, $errfile, $errline, $errcontext) {
	throw new PHPError($errstr, $errno);
}

set_error_handler('errorHandle');

// Initialize session
session_start();

header('Content-type: text/html;charset=utf-8');

if (!array_key_exists('online', $_SESSION)) {
	$_SESSION['online'] = false;
	$_SESSION['id'] = 0;
}

// Initialize paths
define('ROOT_PATH', realpath(dirname(__FILE__) . '/..'));
define('TEMPLATE_PATH', ROOT_PATH . '/application/views');
define('CACHE_PATH', ROOT_PATH . '/cache');

define('TIMEOUT_RELATIONS', 300);

$includePath = array( ROOT_PATH,
                      ROOT_PATH . '/application/models',
                      ROOT_PATH . '/functions',
                      ROOT_PATH . '/library' );
set_include_path(implode(PATH_SEPARATOR, $includePath));

// Registry
require_once 'Zend/Registry.php';
require_once 'Zend/Mail.php';
require_once 'Zend/Mail/Transport/Smtp.php';
require_once 'functions.php';
require_once 'info.php';
require_once 'relations.php';
require_once 'status.php';
require_once 'Catahya/Access.php';

if (get_magic_quotes_gpc()) {
	$_POST = stripslashes_deep($_POST);
	$_GET = stripslashes_deep($_GET);
	$_REQUEST = stripslashes_deep($_REQUEST);
}

// Database
require_once 'Zend/Db.php';

$dbConfig = array('host' => 'localhost', 'username' => 'root', 
	'password' => '-', 'dbname' => 'catahya2' );
                   
$db = Zend_Db::factory('Pdo_Mysql', $dbConfig);

$db->query('SET NAMES utf8');

Zend_Registry::set('db', $db);

// Mail
Zend_Mail::setDefaultTransport(new Zend_Mail_Transport_Smtp('mail.comhem.se'));

// Dispatch
require_once 'Zend/Controller/Front.php';
require_once 'Zend/Controller/Router/Rewrite.php';
require_once 'Zend/View.php';
require_once 'Zend/Config.php';
require_once 'Zend/Config/Ini.php';

$front = Zend_Controller_Front::getInstance();
$front->setParam('noViewRenderer', true);
$front->throwExceptions(true);
$front->addModuleDirectory(ROOT_PATH."/application/modules");

$router = new Zend_Controller_Router_Rewrite();

$routesPath = ROOT_PATH."/conf/routes.ini";
$routes = new Zend_Config(new Zend_Config_Ini($routesPath, null));

$router->addConfig($routes, 'routes');

$front->setRouter($router);

try {
	$front->dispatch();
	if (!defined("JSON")) {
		echo "<!-- Generation time: ".(microtime(1)-STARTTIME)." -->";
	}
} 
catch (Zend_Controller_Dispatcher_Exception $e) {
	$request = new Zend_Controller_Request_Http();
	$request->setRequestUri('/error/404');
	$front->dispatch($request);
}
catch (Zend_Controller_Action_Exception $e) {
	$request = new Zend_Controller_Request_Http();
	$request->setRequestUri('/error/404');
	$front->dispatch($request);
}
catch (Exception $e) {
	$data = serialize($e);
	$hash = sprintf("%X", crc32($data));
	
	file_put_contents(ROOT_PATH . "/log/" . $hash, $data);

	$view = new Zend_View;
	$view->addScriptPath(TEMPLATE_PATH);
	$view->code = $hash;
	$view->e = $e;
	echo $view->render("error.phtml");
}
