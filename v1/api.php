<?php

define('ROOT', realpath($_SERVER['DOCUMENT_ROOT'] . '/..'));
define('PHPROOT', realpath(ROOT . '/git/GIFTron/GIFTron-Back-End'));
define('WEBROOT', realpath(ROOT . '/webroot'));
define('API_PATH', array_slice(preg_split('/[\x5c\/]/', str_replace(ROOT, '', getcwd())), 4));
define('VERSION', preg_split('/[\\x5c\/]/', str_replace(ROOT, '', __FILE__))[4]);

var_dump(API_PATH);


var_dump("hi carrot!");

/*
var_dump(ROOT);
var_dump(PHPROOT);
var_dump(WEBROOT);
var_dump(__FILE__);

$schema = json_decode(file_get_contents(PHPROOT . '/' . VERSION . "/schema.json"));

var_dump($schema);


//var_dump($version);

//require_once ROOT . "v1-libraries/discord-lib.php";


//build API based on schema

/*
var_dump(ROOT . "/v1/schema.json");
var_dump(file_exists(ROOT . "/v1/schema.json"));

var_dump(json_decode(file_get_contents(ROOT . "/v1/schema.json")));