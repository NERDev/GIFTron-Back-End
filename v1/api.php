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

        $this->discordAPI->accessToken = $this->parse_session($_COOKIE['session'])->token;
    }

    function login()
    {
        $this->require_methods('GET') ?: $this->respond(400, "Unsupported Method");
        isset($_GET['code']) ?: $this->respond(400, "A code is needed to login");

        //Verify that user has logged in successfully, and didn't forge a code
        $this->discordAPI->getAccessToken('authorization_code', $_GET['code']);
        
        if ($_GET['guild_id'])
        {
            //This user logged in, and also added the bot to a server.
            //Check if bot has already been added, or if this server even exists.
            //Also, check if this bot has the permissions it needs in order to function
            //You know what, just alias the "add" API endpoint or whatever the bot add function is
        }

        //create session that expires at the end of the browsing period
        $user = $this->discordAPI->getUserInfo();
        $sessionID = uniqid();
        $this->storageAPI->write("sessions/$sessionID", [
            "user"  => $user->id,
            "token" => $this->discordAPI->accessToken,
            "ip"    => $_SERVER['REMOTE_ADDR']
        ]);
        setcookie('session', $sessionID, null, '/');

        //Write the User to a file if it doesn't already
        if (!$this->storageAPI->read("users/$user->id"))
        {
            $this->storageAPI->write("users/$user->id", $user);
            $this->respond(200, "welcome new user!");
        }
        else
        {
            $this->respond(200, "welcome returning user!");
        }
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
        //$this->respond(200, ["write transaction" => $this->storageAPI->write('ab4280', ["kek" => "ayylmao", "things" => ["stuff", "otherstuff"]]), "read transaction" => $this->storageAPI->read('ab4280')]);
        //$this->respond(200, $this->hash($this->discordAPI->getUserInfo()->id));

        $this->respond(200, $this->discordAPI->getUserInfo());
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