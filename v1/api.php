<?php

error_reporting(E_ALL); ini_set('display_errors', 1);
define('ROOT', realpath($_SERVER['DOCUMENT_ROOT'] . '/..'));
/*
define('PHPROOT', realpath(ROOT . '/git/GIFTron/GIFTron-Back-End'));
define('WEBROOT', realpath(ROOT . '/webroot'));
define('VERSION', preg_split('/[\\x5c\/]/', str_replace(ROOT, '', __FILE__))[4]);
*/
require "libraries/discord-lib.php";

$apipath = array_slice(preg_split('/[\x5c\/]/', str_replace(ROOT, '', getcwd())), 4);

class API
{
    protected $webroot;
    protected $phproot;
    protected $discordAPI;
    public $version;

    function __construct()
    {
        $this->phproot  = realpath(ROOT . '/git/GIFTron/GIFTron-Back-End');
        $this->webroot  = realpath(ROOT . '/webroot');
        $this->version  = preg_split('/[\\x5c\/]/', str_replace(ROOT, '', __FILE__))[4];

        $credentials = json_decode(file_get_contents("$this->phproot/metadata/credentials"));
        $this->discordAPI = new DiscordAPI($credentials->clientId, $credentials->clientSecret);
    }

    private function respond($status, $data)
    {
        http_response_code($status);
        $data = gettype($data) == "object" ? $data : (object)$data;
        echo(json_encode($data));
    }

    function login()
    {
        $this->respond(200, $this->discordAPI->getAccessToken('authorization_code', $_GET['code']));
    }

    function user()
    {
        $this->respond(200, $this->discordAPI->getUserInfo());
    }
}

$method = implode('-', $apipath);
$api = new API;
$api->$method();


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