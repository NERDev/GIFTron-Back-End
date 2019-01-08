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

header_remove("X-Powered-By");
$apipath = array_slice(preg_split('/[\x5c\/]/', str_replace(ROOT, '', getcwd())), 5);

class Giveaway
{
    public $start;
    public $end;
    public $guildID;
    public $name;
    public $visible;
    public $known;
    public $key;
    public $recurring;

    function __construct($guild)
    {
        $this->guild = $guild->id;
        $params = array_intersect_key($_POST, get_object_vars($this));

        foreach ($params as $param => $value) {
            extract([$param]);
            $this->$param = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ??
                            filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE) ??
                            filter_var($value, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        }
    }
}

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

        $this->discordAPI->botToken = $credentials->botToken;
        
        if ($this->session = $this->parse_session($_COOKIE['session']))
        {
            //session exists
            $this->user = $this->storageAPI->read("users/" . $this->session->user)->data;
            $this->discordAPI->userToken = $this->session->token;
        }
    }

    function login()
    {
        error_reporting(E_ALL); ini_set('display_errors', 1);
        
        //Pre-Login checks
        $this->require_methods('GET') ?: $this->respond(400, "Unsupported Method");
        if ($this->user && !$guildID = $_GET['guild_id'])
        {
            $this->respond(200, "Already logged in");
        }
        isset($_GET['code']) ?: $this->respond(400, "A code is needed to login");


        //Verify that User has logged in successfully, and didn't forge a code
        $this->discordAPI->userToken = $this->discordAPI->getAccessToken('authorization_code', $_GET['code']);
        $this->discordAPI->userToken ?: $this->respond(400, "Invalid code");

        
        //Get User info
        $currentUserData = $this->discordAPI->getUserInfo();
        $localUserData = $this->storageAPI->read("users/$currentUserData->id")->data;
        $this->user = (object) array_merge(
            (array) $localUserData,
            (array) $currentUserData
        );
        
        
        //Create session for User
        $sessionID = uniqid(random_int(0,999), TRUE);
        $this->storageAPI->write("sessions/$sessionID", [
            "user"  => $this->user->id,
            "token" => $this->discordAPI->userToken,
            "ip"    => $_SERVER['REMOTE_ADDR']
        ]);
        setcookie('session', $sessionID, null, '/');


        //Check if User is attempting to add the bot to a guild
        if ($guildID = $_GET['guild_id'])
        {
            //Check if bot has already been added, or if this guild even exists.
            if (!$this->storageAPI->read("guilds/$guildID"))
            {
                //Bot has not been added to this guild before. It is either new or invalid.
                $guildInfo = $this->discordAPI->getGuildInfo($guildID);
                if ($guildInfo)
                {
                    //Bot is verifiably added to this guild. Add guild to User's list, and instantiate it.
                    $this->user->guilds->$guildID = true;
                    $this->storageAPI->write("guilds/$guildID", [
                        "users" => [$this->user->id],
                        "wallet" => 0,
                        "giveaways" => []
                    ]);

                    //Search the guild for a channel called #giveaways. If it doesn't exist, create it.

                    //Webhook the #giveaways channel.

                    //var_dump($this->discordAPI->createWebhook(521130623750897696));
                }
                else
                {
                    //Bot has not actually been added to this guild. Something's fishy...
                }
            }
        }


        //Check if User has been updated with new information
        if ($this->user != $localUserData)
        {
            //User has information that is new... Write changes
            $this->storageAPI->write("users/" . $this->user->id, $this->user);
            //var_dump($localUserData, $this->user);
        }

        //$this->respond(200, "Welcome, " . $this->user->username);
        $this->respond(200, $this->user);

    }

    function user_info()
    {
        $this->discordAPI->userToken ?: $this->respond(200, "Please Log In");
        $this->respond(200, $this->user);
        //$this->respond(200, $this->discordAPI->getUserInfo());
        
        /*
        //Get User info
        $currentUserData = $this->discordAPI->getUserInfo();
        //$localUserData = $this->storageAPI->read("users/$remoteUserData->id")->data;
        $this->respond(200, (object) array_merge((array) $this->user, (array) $remoteUserData));
        */
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
        error_reporting(E_ALL); ini_set('display_errors', 1);
        //Make sure we've got what we need
        $guildID = $_GET['guild_id'] ?: $this->respond(400, "We can't schedule anything if we don't know the Guild ID.");
        $this->user->guilds->$guildID ? $guild = $this->storageAPI->read("guilds/$guildID")->data :
        $this->respond(400, "Hey, don't go scheduling giveaways without permission.");

        $giveaway = new Giveaway($guild);

        if (!$giveaway->key)
        {
            //Alert NERDev that an order has been placed, and needs to be filled
            var_dump($this->discordAPI->postMessage("test", null, null));
        }

        var_dump($giveaway);
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
        //refactor to user $this->storageAPI as above
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
    $schema = json_decode(file_get_contents(PHPROOT . "/" . VERSION . "/schema.json"));
    $method = implode('_', $apipath);

    //Get properties of method
    $methodproperties = $schema;
    foreach ($apipath as $property)
    {
        $methodproperties = $methodproperties->$property;
    }


    //Check before instantiating APIhost
    if (!Security::require_methods($methodproperties->methods))
    {
        APIhost::respond(405, "-Method Not Allowed");
    }

    if ($methodproperties->protected)
    {
        if (!Security::trusted_server($_SERVER['REMOTE_ADDR']))
        {
            APIhost::respond(400, "-Untrusted Origin");
        }
    }

    //Passed! Process the request
    $api = new APIhost;
    $api->$method();
}