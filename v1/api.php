<?php

define('ROOT', $_SERVER['DOCUMENT_ROOT']);
define('API_PATH', preg_split('/[\x5c\/]/', str_replace(ROOT, '', getcwd())));

//require_once ROOT . "v1-libraries/discord-lib.php";



var_dump(API_PATH);

var_dump(ROOT . "/v1/schema.json");
var_dump(file_exists(ROOT . "/v1/schema.json"));

var_dump(json_decode(file_get_contents(ROOT . "/v1/schema.json")));