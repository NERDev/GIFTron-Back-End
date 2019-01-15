<?php

//error_reporting(E_ALL); ini_set('display_errors', 1);
/*
define('PHPROOT', realpath(ROOT . '/git/GIFTron/GIFTron-Back-End'));
define('WEBROOT', realpath(ROOT . '/webroot'));
define('VERSION', preg_split('/[\\x5c\/]/', str_replace(ROOT, '', __FILE__))[4]);
*/

define('SERVER_WELCOME', '533005932079218689');

require_once "libraries/security-lib.php";
require_once "libraries/discord-lib2.php";
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
    public $gameID;

    function __construct($guild)
    {
        $this->guildID = $_GET['guild_id'];
        $params = array_intersect_key($_POST, get_object_vars($this));

        foreach ($params as $param => $value)
        {
            extract([$param]);
            $this->$param = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ??
                            filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE) ??
                            filter_var($value, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        }

        switch ($this)
        {
            case (!$this->gameID):
                $this->fail($this);
                break;
        }
    }

    function fail(Giveaway &$me)
    {
        //
    }
}

class APIhost extends Security
{
    protected $webroot;
    protected $phproot;
    protected $discord;
    protected $storage;
    public $version;

    function __construct($internal)
    {
        parent::__construct();
        $credentials = json_decode(file_get_contents("$this->phproot/metadata/credentials"));

        
        //error_reporting(E_ALL); ini_set('display_errors', 1);
        try {
            $this->discord = new DiscordLib\API($credentials);
        } catch (Exception $e) {
            $this->respond(500, $e->getMessage());
        }
        
        $this->storage = new StorageNode;
        
        if ($this->session = $this->parse_session($_COOKIE['session']))
        {
            //session exists
            $this->user = $this->storage->read("users/" . $this->session->user)->data;
            $this->discord->user->token = $this->session->token;
        }
    }

    function login()
    {
        //error_reporting(E_ALL); ini_set('display_errors', 1);
        
        //Pre-Login checks
        $this->require_methods('GET') ?: $this->respond(400, "Unsupported Method");
        if ($this->user && !$guildID = $_GET['guild_id'])
        {
            $this->redirect("$this->apiroot/user/info");
            //$this->respond(200, "Already logged in");
        }
        isset($_GET['code']) ?: $this->respond(400, "A code is needed to login");
        


        //Verify that User has logged in successfully, and didn't forge a code
        $access_token = $this->discord->user->verify($_GET['code']) ?: $this->respond(400, "Invalid Code");
        var_dump($access_token);
        exit;
        
        //Get User info
        $currentUserData = $this->discordAPI->getUserInfo();
        $localUserData = $this->storage->read("users/$currentUserData->id")->data;
        $this->user = (object) array_merge(
            (array) $localUserData,
            (array) $currentUserData
        );
        
        
        //Create session for User
        $sessionID = uniqid(random_int(0,999), TRUE);
        if ($this->storage->write("sessions/$sessionID", [
            "user"  => $this->user->id,
            "token" => $this->discordAPI->userToken,
            "ip"    => $_SERVER['REMOTE_ADDR']
        ])) setcookie('session', $sessionID, null, '/');        


        //Check if User is attempting to add the bot to a guild
        if ($guildID = $_GET['guild_id'])
        {
            //Make sure it has the correct permissions.
            //Check if bot has already been added, or if this guild even exists.
            if (!$this->storage->read("guilds/$guildID"))
            {
                //Bot has not been added to this guild before. It is either new or invalid.
                $guildInfo = $this->discordAPI->getGuildInfo($guildID);
                if ($guildInfo)
                {
                    //Bot is verifiably added to this guild.
                    //Add guild to User's list, and instantiate it.
                    //We need to keep track of the webhook to use.
                    $this->user->guilds->$guildID = true;
                    $this->storage->write("guilds/$guildID", [
                        "users"     => [$this->user->id => true],
                        "channel"   => null,
                        "wallet"    => 0,
                        "giveaways" => []
                    ]);

                    $this->discordAPI->postMessage("Attention! @" . $this->user->username . " just added me to $guildInfo->name!", SERVER_WELCOME);

                    //$this->redirect("/giftron#dashboard?setup=$guildID", false);
                    $redirect = "/giftron#dashboard/$guildID";
                }
                else
                {
                    //Bot has been removed from this guild.
                }
            }
        }


        //Check if User has been updated with new information
        if ($this->user != $localUserData)
        {
            //User has information that is new... Write changes
            //$this->storage->write("users/" . $this->user->id, $this->user);
            //var_dump($localUserData, $this->user);
        }
        $this->storage->write("users/" . $this->user->id, $this->user);

        //$this->respond(200, "Welcome, " . $this->user->username);
        //$this->respond(200, $this->user);

        //$this->redirect($redirect ?? "/giftron#dashboard");
        //troubleshoot issue with $localUserData not representing what it should
        var_dump($this->user, $localUserData, $this->user != $localUserData);
    }

    function user_info()
    {
        $this->discordAPI->userToken ?: $this->respond(200, "Please Log In");
        $this->respond(200, $this->user);
        //$this->respond(200, $this->discordAPI->getUserInfo());
        
        /*
        //Get User info
        $currentUserData = $this->discordAPI->getUserInfo();
        //$localUserData = $this->storage->read("users/$remoteUserData->id")->data;
        $this->respond(200, (object) array_merge((array) $this->user, (array) $remoteUserData));
        */
    }

    function user_guilds()
    {
        /*
        if (is_numeric($_SERVER['QUERY_STRING']))
        {
            $guilds = $this->discordAPI->getUserGuilds();

            $this->discordAPI->list_permissions($_SERVER['QUERY_STRING']);
            $this->respond(200, $this->discordAPI->list_permissions($_SERVER['QUERY_STRING']));
        }
        $this->respond(200, $this->discordAPI->getUserGuilds());
        */

        //Beware: this is an EXPENSIVE request!!

        //Let's build a list of guilds relating to this user
        $userguilds = array_map('strval', array_keys((array)$this->user->guilds));
        foreach ($this->discordAPI->getUserGuilds() as $discordGuild)
        {
            in_array($discordGuild->id, $userguilds) ?: $guilds[] = $discordGuild->id;
        }
        
        $guilds = array_merge($userguilds, $guilds);

        //This is the almighty filter... if it doesn't exist on our system, it doesn't get returned.
        foreach ($guilds as $guildID)
        {
            if (!$this->storage->read("guilds/$guildID")->hash)
            {
                unset($this->user->guilds->$guildID);
            }
        }
        $this->respond(200, $this->user->guilds);
    }

    function guild_info()
    {
        //error_reporting(E_ALL); ini_set('display_errors', 1);
        $guildID = $_SERVER['QUERY_STRING'] ?: $this->respond(400, "Which guild did you want information for?");
        in_array($guildID, array_keys((array)$this->user->guilds)) ? $guild = $this->storage->read("guilds/$guildID")->data :
        $this->respond(400, "Hey, don't go peeking at guilds you shouldn't.");
        $guild = $this->storage->read("guilds/$guildID")->data ?: $this->respond(400, "We don't have this guild in our system.");
        if (!$guild->channel)
        {
            $defaultChannel = 'giveaway';
            $channels = $this->discordAPI->getGuildChannels($guildID);

            var_dump($channels);

            //Make sure to only include text channels
            foreach ($channels as $channel)
            {
                if ($channel->type == 0)
                {
                    $textChannels[$channel->id] = $channel->name;
                }
            }


            //Figure out which text channel has the best match
            $bestsim = 50;
            foreach ($textChannels as $id => $name)
            {
                similar_text($name, $defaultChannel, $sim);
                if ($sim > $bestsim)
                {
                    $bestsim = $sim;
                    $suggestedChannel = [$id => $name];
                }
            }
            
            $guild->setup = [
                "channel" => [
                    "suggested" => $suggestedChannel,
                    "available" => $textChannels
                ]
            ];
        }
        $this->respond(200, $guild);
        //var_dump($this->discordAPI->getUserGuilds());
        //var_dump($this->discordAPI->getGuildInfo($guildID));
        //var_dump($this->discordAPI->postMessage("Hi Carrot", 525404638552391682));
    }

    function guild_configure()
    {
        $guildID = $_SERVER['QUERY_STRING'] ?: $this->respond(400, "Which guild did you want to configure?");
        $this->user->guilds->$guildID ? $guild = $this->storage->read("guilds/$guildID")->data :
        $this->respond(400, "Hey, don't go meddling with guilds you shouldn't.");
        $guildInfo = $this->discordAPI->getGuildInfo($guildID);
        var_dump($guildInfo);
        var_dump($guild);
        var_dump($this->user);
    }

    function schedule_new()
    {
        error_reporting(E_ALL); ini_set('display_errors', 1);
        //Make sure we've got what we need
        $guildID = $_GET['guild_id'] ?: $this->respond(400, "We can't schedule anything if we don't know the Guild ID.");
        $this->user->guilds->$guildID ? $guild = $this->storage->read("guilds/$guildID")->data :
        $this->respond(400, "Hey, don't go scheduling giveaways without permission.");

        $giveaway = new Giveaway($guild);

        if (!$giveaway->key)
        {
            //Alert NERDev that an order has been placed, and needs to be filled
            $guildInfo = $this->discordAPI->getGuildInfo($guildID);
            //var_dump($this->discordAPI->postMessage("$guildInfo->name is attempting to buy a key for $giveaway->gameID with $$guild->wallet", 531964952819400704));
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
        $this->respond(200, $this->storage->read($_SERVER['QUERY_STRING']));
    }

    function storage_write()
    {
        //refactor to user $this->storage as above
        $this->trusted_server($_SERVER['REMOTE_ADDR']) ?: $this->respond(400, "Untrusted Origin");
        $storage = new StorageNode;
        $this->respond(200, $storage->write($_SERVER['QUERY_STRING'], json_decode(file_get_contents("php://input"), true)));
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
    $security = new Security;

    //Get properties of method
    $methodproperties = $schema;
    foreach ($apipath as $property)
    {
        $methodproperties = $methodproperties->$property;
    }


    //Check before instantiating APIhost
    if (!$security->require_methods($methodproperties->methods))
    {
        $security->respond(405, "-Method Not Allowed");
    }

    $internal = $security->trusted_server($_SERVER['REMOTE_ADDR']);

    if ($methodproperties->protected)
    {
        if (!$internal)
        {
            $security->respond(400, "-Untrusted Origin");
        }
    }

    //Passed! Process the request
    $api = new APIhost($internal);
    method_exists($api, $method) ? $api->$method() :
    $api->respond(418, "We don't have any code for this endpoint... maybe it's not built yet.");    
}