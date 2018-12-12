<?php

//error_reporting(E_ALL); ini_set('display_errors', 1);
/*
define('PHPROOT', realpath(ROOT . '/git/GIFTron/GIFTron-Back-End'));
define('WEBROOT', realpath(ROOT . '/webroot'));
define('VERSION', preg_split('/[\\x5c\/]/', str_replace(ROOT, '', __FILE__))[4]);
*/
require_once "libraries/security-lib.php";
require_once "libraries/discord-lib.php";
require_once "libraries/storage-lib.php";

$apipath = array_slice(preg_split('/[\x5c\/]/', str_replace(ROOT, '', getcwd())), 5);

class APIhost extends Security
{
    protected $webroot;
    protected $phproot;
    protected $discordAPI;
    public $version;

    function __construct()
    {
        parent::__construct();
        $credentials = json_decode(file_get_contents("$this->phproot/metadata/credentials"));
        $this->discordAPI = new DiscordAPI($credentials->clientId, $credentials->clientSecret);
    }

    private function respond($status, $data)
    {
        http_response_code($status);
        $data = gettype($data) == "object" ? $data : (object)$data;
        exit(json_encode($data));
    }

    function login()
    {
        $this->require_methods('GET') ?: $this->respond(400, "Unsupported Method");
        setcookie('token', $this->discordAPI->getAccessToken('authorization_code', $_GET['code']), null, '/');
        $this->respond(200, "success");
    }

    function user_info()
    {
        $this->respond(200, $this->discordAPI->getUserInfo());
    }

    function user_guilds()
    {
        if (is_numeric($_SERVER['QUERY_STRING']))
        {
            $guilds = $this->discordAPI->getUserGuilds();

            $this->discordAPI->list_permissions($_SERVER['QUERY_STRING']);
            $this->respond(200, $this->discordAPI->list_permissions($_SERVER['QUERY_STRING']));
        }
        $this->respond(200, $this->discordAPI->getUserGuilds());
    }

    function storage_read()
    {
        $this->trusted_server() ?: $this->respond(400, "Untrusted Origin");
        $storageapi = new StorageNode;
        $this->respond(200, $storageapi->read($_SERVER['QUERY_STRING']));
    }

    function storage_write()
    {
        $this->trusted_server() ?: $this->respond(400, "Untrusted Origin");
        $storageapi = new StorageNode;
        $this->respond(200, $storageapi->write($_SERVER['QUERY_STRING'], json_decode($_POST)));
    }
}

function build()
{
    function recurse($struct, $parent = null)
    {
        $apiroot = realpath("../../../../webroot/giftron/api/v1");
        foreach($struct as $name => $properties)
        {
            chdir($parent ?? $apiroot);

            if (gettype($properties) == "object")
            {
                mkdir($name);
                if ($properties->methods)
                {
                    file_put_contents("$name/index.php", $GLOBALS['seed']);
                }
                else
                {
                    recurse($properties, $name);
                }
            }
        }
    }
    
    echo "building API tree\n";
    $GLOBALS['seed'] = file_get_contents("seed.php");
    $schema = json_decode(file_get_contents("schema.json"));
    chdir(realpath("../../../../webroot/giftron/api/v1"));
    recurse($schema);
}

if (function_exists($argv[1]))
{
    $argv[1]();
}
else
{
    $method = implode('_', $apipath);
    $api = new APIhost;
    $api->$method();
}



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