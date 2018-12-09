<?php

require_once "security-lib.php";

define('ROOT', realpath($_SERVER['DOCUMENT_ROOT'] . '/..'));
define('PHPROOT', realpath(ROOT . '/git/GIFTron/GIFTron-Back-End'));
define('WEBROOT', realpath(ROOT . '/webroot'));
define('API_PATH', array_slice(preg_split('/[\x5c\/]/', str_replace(ROOT, '', getcwd())), 4));
define('VERSION', preg_split('/[\\x5c\/]/', str_replace(ROOT, '', __FILE__))[4]);

class API
{

}