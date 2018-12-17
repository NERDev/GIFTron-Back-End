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
        $this->storageAPI = new StorageNode;
    }

    private function respond($status, $data)
    {
        http_response_code($status);
        //$data = gettype($data) == "object" ? $data : (object)$data;
        $data = json_decode($data) ? $data : json_encode($data);
        exit($data);
    }

    function login()
    {
        $this->require_methods('GET') ?: $this->respond(400, "Unsupported Method");

        //Verify that user has logged in successfully, and didn't forge a code

        if ($_GET['guild_id'])
        {
            //This user logged in, and also added the bot to a server.
            //Check if bot has already been added, or if this server even exists.
            //Also, check if this bot has the permissions it needs in order to function
        }

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

    function schedule_new()
    {
        //$this->respond(200, $this->discordAPI->getGuildInfo($_GET['id']));
        //$this->respond(200, $this->discordAPI->getBotGuilds());
        //$this->respond(200, $this->storageAPI->write('ab4281', ["kek" => "stuff"]));
        $this->respond(200, $this->storageAPI->write('test/ab4280', ["kek" => "ayylmao", "things" => ["stuff", "otherstuff"]]));
    }

    function storage_check()
    {
        //simply check if file exists
    }

    function storage_read()
    {
        $this->trusted_server($_SERVER['REMOTE_ADDR']) ?: $this->respond(400, "Untrusted Origin");
        $this->respond(200, $this->storageAPI->read($_SERVER['QUERY_STRING']));
    }

    function storage_write()
    {
        $this->trusted_server($_SERVER['REMOTE_ADDR']) ?: $this->respond(400, "Untrusted Origin");
        $storageapi = new StorageNode;
        $this->respond(200, $storageapi->write($_SERVER['QUERY_STRING'], json_decode(file_get_contents("php://input"), true)));
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