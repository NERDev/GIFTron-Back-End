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
            if (time() < $this->session->expires)
            {
                //session hasn't expired yet
                $this->user = $this->storage->read("users/" . $this->session->user)->data;
                $this->discord->user->token = $this->session->token;
            }
            else
            {
                //reauth using the refresh token
                //$this->discord->user->reauth($this->session->refreshtoken);
                $this->respond(200, "reauthorizing...");
            }
        }
    }

    function user_auth()
    {
        //error_reporting(E_ALL); ini_set('display_errors', 1);

        isset($_GET['code']) ?: $this->respond(400, "A code is needed to login");
        $this->discord->user->auth($_GET['code']) ?: $this->respond(400, "Invalid Code");

        if (!$this->user)
        {
            //Get User info
            $localUserData = $this->storage->read("users/{$this->discord->user->info->id}")->data;
            $this->user = (object) array_merge(
                (array) $localUserData,
                (array) $this->discord->user->info
            );
        }

        //Create session for User
        $sessionID = md5(uniqid(random_int(0,999), TRUE));
        if ($this->storage->write("sessions/$sessionID", [
            "user"    => $this->user->id,
            "token"   => $this->discord->user->token,
            "expires" => $this->discord->user->timeout,
            "ip"      => $_SERVER['REMOTE_ADDR']
        ])) setcookie('session', $sessionID, $this->discord->user->timeout, '/');

        //Check if User is attempting to add the bot to a guild
        if ($guildID = $_GET['guild_id'])
        {
            //Make sure it has the correct permissions.
            //Check if bot has already been added, or if this guild even exists.
            if ($this->discord->bot->guilds->$guildID->info)
            {
                //Bot is verifiably added to this guild.

                //Add guild to User's list, and instantiate it.
                $this->user->guilds[] = "$guildID";

                if (!$this->storage->read("guilds/$guildID"))
                {
                    $this->storage->write("guilds/$guildID", [
                        //"users"     => [$this->user->id],
                        "channel"   => null,
                        "wallet"    => 0,
                        "giveaways" => []
                    ]);

                    //Alert to the welcome channel that we have a new member
                    $this->discord->bot->channels->{SERVER_WELCOME}->postMessage(
                        "Attention! <@".$this->user->id."> just added me to " .
                        $this->discord->bot->guilds->$guildID->info->name . "!"
                    );
                }
                else
                {
                    $this->discord->bot->channels->{SERVER_WELCOME}->postMessage(
                        "Attention! <@".$this->user->id."> just added me back to " .
                        $this->discord->bot->guilds->$guildID->info->name . "!"
                    );
                }
            }
            else
            {
                //Bot has been removed from this guild.
                $this->discord->bot->channels->{SERVER_WELCOME}->postMessage(
                    "Uh oh! <@".$this->user->id."> just tried to add me to $guildID, but I'm not in that guild.
                    Something's wrong."
                );
            }
        }


        //Check if User has been updated with new information
        var_dump($this->user);
        $this->storage->write("users/" . $this->user->id, $this->user);
        var_dump("We hit discord " . \DiscordLib\HTTP::$requests . " times.");
    }

    function user_info()
    {
        $this->respond(200, $this->user);
    }

    function user_guilds()
    {
        //Beware: this is an EXPENSIVE request!!

        //This is the almighty filter... if it doesn't exist on our system, it doesn't get returned.
        if (!$this->discord->user->guilds)
        {
            $this->respond(400, "Unable to load guilds... Check permissions");
        }
        
        foreach (array_unique(array_merge(array_map(function($g){return $g->id;},
        $this->discord->user->guilds), $this->user->guilds)) as $guildID)
        {
            if ($this->storage->read("guilds/$guildID")->hash)
            {
                foreach ($this->discord->user->guilds as $i => $guild)
                {
                    if ($guild->id == $guildID)
                    {
                        $guilds[] = $this->discord->user->guilds[$i];
                    }
                }
            }
        }
        //var_dump("We hit discord " . \DiscordLib\HTTP::$requests . " times.");
        $this->respond(200, $guilds);
    }

    function guild_info()
    {
        //error_reporting(E_ALL); ini_set('display_errors', 1);
        $guildID = $_SERVER['QUERY_STRING'] ?: $this->respond(400, "Which guild did you want information for?");
        $guild = $this->storage->read("guilds/$guildID")->data ?: $this->respond(400, "We don't have this guild in our system.");

        if (!$guild->channel)
        {
            $match = 'giveaway';
            $channels = $this->discord->bot->guilds->$guildID->channels;

            if ($channels)
            {
                //Build tree of channels

                foreach ($channels as $i => $channel)
                {
                    if ($channel->type == 4)
                    {
                        $categories[$channel->id];
                    }

                    if ($channel->type == 0)
                    {
                        $availableChannels[$channel->id] = $channel->name;
                        $categories[$channel->parent_id ?? $channel->guild_id][] = $channel;
                    }
                }

                //Obtain list of suggested channels

                foreach ($channels as $channel)
                {
                    if (strstr(strtolower(preg_replace("/[^a-zA-Z]/", '', $channel->name)), $match))
                    {
                        if ($channel->type == 0)
                        {
                            $suggestedChannels[$channel->id] = $channel->name;
                        }

                        if ($channel->type == 4)
                        {
                            foreach ($categories[$channel->id] as $child)
                            {
                                if ($child->type == 0)
                                {
                                    $suggestedChannels[$child->id] = $child->name;
                                }
                            }
                        }
                    }
                }

                $guild->setup = [
                    "channels" => [
                        "suggested" => $suggestedChannels,
                        "available" => array_diff($availableChannels, $suggestedChannels)
                    ]
                ];
            }
            else
            {
                if (!$this->discord->bot->guilds->$guildID->info)
                {
                    $guild->setup = [
                        "guild" => [
                            "add"
                        ]
                    ];
                }
            }
        }
        
        //var_dump("We hit discord " . \DiscordLib\HTTP::$requests . " times.");
        $this->respond(200, $guild);
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