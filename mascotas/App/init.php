<?php
date_default_timezone_set('America/Costa_Rica');
(Dotenv\Dotenv::createImmutable(dirname(__FILE__, 2)))->load();

if (strtolower($_ENV['environment']) === "production") {
	error_reporting(0);
	ini_set("display_errors", 0);
	ini_set('display_startup_errors', 0);
} else {
	error_reporting(E_ALL);
	ini_set("display_errors", 1);
	ini_set('display_startup_errors', 1);
}

require_once __DIR__.('\Config\Constantes.php');
require_once __DIR__.('\Config\BaseHelpers.php');

helper("cookies, views, redirect, auth");

require base_dir("App\Config\Routes.php");