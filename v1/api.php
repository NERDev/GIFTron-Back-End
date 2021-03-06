<?php

//error_reporting(E_ALL); ini_set('display_errors', 1);
/*
define('PHPROOT', realpath(ROOT . '/git/GIFTron/GIFTron-Back-End'));
define('WEBROOT', realpath(ROOT . '/webroot'));
define('VERSION', preg_split('/[\\x5c\/]/', str_replace(ROOT, '', __FILE__))[4]);
*/

define('SERVER_WELCOME', '533005932079218689');
define('SERVER_ORDERS', '538825512567439380');
define('NERDEV', '521130623750897694');
define('NERDEV_EMPLOYEE', '540647711947358222');

require_once "libraries/security-lib.php";
require_once "libraries/discord-lib2.php";
require_once "libraries/g2a-lib.php";
require_once "libraries/storage-lib.php";

header_remove("X-Powered-By");
$apipath = array_slice(preg_split('/[\x5c\/]/', str_replace(ROOT, '', getcwd())), 5);

trait RequiredGiveawayParams
{
    public $guild_id;
    public $end;
    public $name;
    public $channel;
}

class Giveaway
{
    use RequiredGiveawayParams;

    public $start;
    public $visible;
    public $recurring;
    public $key;
    public $game_id;

    function __construct($params)
    {
        $this->visible = true;
        $this->recurring = false;
        if ($params['recurring'])
        {
            isset($params['count']) && $this->count = $params['count'] - 1;
            $this->beginning = $params['start'];
        }

        $params = array_intersect_key($params, get_object_vars($this));
        foreach ($params as $param => $value)
        {
            extract([$param]);
            $this->$param = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ??
                            filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE) ??
                            filter_var($value, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        }

        if (!$this->key && !$this->game_id)
        {
            $missing[] = "key or game ID";
        }

        foreach (get_class_vars(RequiredGiveawayParams) as $key => $value)
        {
            if (!get_object_vars($this)[$key])
            {
                $missing[] = $key;
            }
        }

        if (count($missing))
        {
            throw new Exception(implode(', ', $missing));
        }
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
            $this->respond(503, $e->getMessage());
        }
        
        $this->storage = new StorageNode;
        //Don't freak out, these credentials are public on G2A's documentation.
        $this->g2a = new \G2A\API("qdaiciDiyMaTjxMt", "74026b3dc2c6db6a30a73e71cdb138b1e1b5eb7a97ced46689e2d28db1050875");
        
        if ($_COOKIE['session'] && $this->session = $this->storage->read("sessions/" . $_COOKIE['session'])->data)
        {
            //session exists
            if (time() > $this->session->expires)
            {
                //reauth using the refresh token
                try {
                    $this->discord->user->reauth($this->session->refreshtoken);
                } catch (\Throwable $th) {
                    setcookie('session', null, -1, '/');
                    $this->respond(400, "Reauthorization failure");
                    file_put_contents("../../../../logs/auth/" . microtime(true), json_encode($th));
                }
                $this->session->token = $this->discord->user->token;
                $this->session->refreshtoken = $this->discord->user->refreshtoken;
                $this->session->expires = $this->discord->user->timeout;
                $this->storage->write("sessions/" . $_COOKIE['session'], $this->session);
                setcookie('session', $_COOKIE['session'], null, '/');
            }
            $this->user = $this->storage->read("users/" . $this->session->user)->data;
            $this->discord->user->token = $this->session->token;
        }
    }

    function user_auth()
    {
        //error_reporting(E_ALL); ini_set('display_errors', 1);
        $asking = 268438544;
        //Check if asking for an OAuth2 URL
        if (count($_GET) == 1 && isset($_GET['scope']))
        {
            $scope = $_GET['scope'];
            $parts = [
                "client_id" => \DiscordLib\HTTP::$clientId,
                "permissions" => $asking,
                "redirect_uri" => "http".(!boolval($_SERVER['HTTPS'])?"s":"")."://".$_SERVER['SERVER_NAME'].$_SERVER['URL'],
                "response_type" => "code",
                "scope" => $scope
            ];

            if (!in_array('bot', explode(' ', $scope)))
            {
                unset($parts['permissions']);
            }

            $this->redirect(\DiscordLib\HTTP::$baseURL . "/oauth2/authorize?" . http_build_query($parts));
        }

        isset($_GET['code']) ?: $this->respond(400, "A code is needed to login");
        if ($guildID = strval($_GET['guild_id']))
        {
            try {
                $this->discord->bot->guilds->$guildID->info;
            } catch (\Throwable $th) {
                //first attempt failed, bot was not in this guild prior to code execution
                $newGuild = true;
            }
        }

        try {
            $this->discord->user->auth($_GET['code']) ?: $this->respond(400, "Invalid Code");
        } catch (\Throwable $th) {
            $e = $this->parseException($th);
            $this->respond($e->code ?: 501, $e->message ?: "Authorization is unavailable at this time.");
        }

        if (!$this->user)
        {
            //Get User info
            $localUserData = $this->storage->read("users/{$this->discord->user->info->id}")->data;
            try {
                $this->discord->user->info;
            } catch (\Throwable $th) {
                $e = $this->parseException($th);
                $this->respond($e->code ?: 501, $e->message ?: "Could not retrieve user info from Discord.");
            }
            $this->user = (object) array_merge(
                (array) $localUserData,
                (array) $this->discord->user->info
            );
            $this->user->staff = $this->is_staff();
        }

        //Create session for User
        $sessionID = md5(uniqid(random_int(0,999), TRUE));
        if ($this->storage->write("sessions/$sessionID", [
            "user"          => $this->user->id,
            "token"         => $this->discord->user->token,
            "refreshtoken"  => $this->discord->user->refreshtoken,
            "expires"       => $this->discord->user->timeout,
            "ip"            => $_SERVER['REMOTE_ADDR']
        ])) setcookie('session', $sessionID, null, '/');

        $redirect = "/giftron";
        //Check if User is attempting to add the bot to a guild
        if ($guildID = strval($_GET['guild_id']))
        {
            $redirect .= "#dashboard?$guildID";
            //Make sure it has the correct permissions.
            //Check if bot has already been added, or if this guild even exists.
            try {
                $this->discord->bot->guilds->$guildID->info;
            } catch (\Throwable $th) {
                //second attempt failed, bot is still not in this guild... wtf
                $this->respond(420, "wtf brah");
            }

            //Bot is verifiably added to this guild.
            if (!$this->storage->read("guilds/$guildID"))
            {
                //Add guild to User's list, and instantiate it.

                $this->user->guilds->$guildID = true;
                
                $this->storage->write("guilds/$guildID", [
                    //"users"     => [$this->user->id],
                    "settings"     => [
                        "channels"     => null,
                        "access_roles" => null,
                        "strict"       => false,
                        "max"          => 5,
                        "min"          => 1
                    ],
                    "wallet"       => 0,
                    "giveaways"    => [],
                    "name"         => $this->discord->bot->guilds->$guildID->info->name,
                    "icon"         => $this->discord->bot->guilds->$guildID->info->icon
                ]);

                //Alert to the welcome channel that we have a new member
                var_dump("addnew", @$this->discord->bot->channels->{SERVER_WELCOME}->postMessage(
                    "Attention! <@".$this->user->id."> just added me to " .
                    $this->discord->bot->guilds->$guildID->info->name . "!"
                ));
            }
            else
            {
                if ($newGuild)
                {
                    var_dump("addback", $this->discord->bot->channels->{SERVER_WELCOME}->postMessage(
                        "Attention! <@".$this->user->id."> just added me back to " .
                        $this->discord->bot->guilds->$guildID->info->name . "!"
                    ));
                }
                else
                {
                    var_dump("addback", $this->discord->bot->channels->{SERVER_WELCOME}->postMessage(
                        "LOL! <@".$this->user->id."> just tried adding me to " .
                        $this->discord->bot->guilds->$guildID->info->name . ", even though I'm already there!"
                    ));
                }
            }
        }


        //Check if User has been updated with new information
        //var_dump($this->user);
        $this->storage->write("users/{$this->user->id}", $this->user);
        //var_dump("We hit discord " . count(\DiscordLib\HTTP::$requests) . " times.");
        $this->redirect($redirect);
    }

    function user()
    {
        $this->user ?: $this->respond(401, "Please log in.");
        unset($this->user->guilds);
        $this->respond(200, $this->user);
    }

    function user_guilds()
    {
        $this->user ?: $this->respond(401, "Please log in.");
        if ($_SERVER['REQUEST_METHOD'] == 'GET')
        {
            //OBJECTIVE: RETURN ALL POSSIBLE GUILDS, REGARDLESS OF IF THEY'RE IN THE SYSTEM OR NOT
            //LET THE CLIENT FIGURE IT OUT
            try {
                $this->discord->user->guilds;
            } catch (\Throwable $th) {
                $e = $this->parseException($th);
                if ($e->details->HTTP == 403)
                {
                    $e->code = 400;
                    $e->message = "We need permission to see your guilds in order to complete this request.";
                }
                $this->respond($e->code ?: 501, $e->message ?: "We cannot retrieve the guilds for this user.");
            }
            $discordguilds = array_column($this->discord->user->guilds, 'id');
            $discordguildsfill = array_combine($discordguilds, array_fill(0, count($discordguilds), false));            
            $this->respond(200, array_intersect_key((array)$this->user->guilds + $discordguildsfill, $discordguildsfill));
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST')
        {
            if (!$guilds = json_decode(file_get_contents("php://input")))
            {
                $guilds[] = strval($_SERVER['QUERY_STRING']);
            }
            
            foreach ($guilds as $guildID)
            {
                if (!isset($this->user->guilds->$guildID) && $this->storage->read("guilds/$guildID"))
                {
                    $this->user->guilds->$guildID = false;
                }
            }
            $this->respond(200, $this->user->guilds);
        }
    }

    function guild()
    {
        $guildID = (count($_GET) == 1 ? (reset($_GET) ?: (string)key($_GET)) : $_GET['guild_id']) ?:
        $this->respond(400, "Which guild did you want?");

        /*
        if ($_SERVER['REQUEST_METHOD'] == 'HEAD')
        {
            if ($this->storage->read("guilds/$guildID")->data)
            {
                http_response_code(200);
            }
            else
            {
                http_response_code(204);
            }
        }
        */
        
        if ($_SERVER['REQUEST_METHOD'] == 'GET')
        {
            //error_reporting(E_ALL); ini_set('display_errors', 1);
            $guild = $this->storage->read("guilds/$guildID")->data ?: $this->respond(204, "We don't have this guild in our system.");
            
            try {
                $this->discord->bot->guilds->$guildID->info;
            } catch (\Throwable $th) {
                $e = $this->parseException($th);
                if ($e->details->HTTP == 403)
                {
                    //GIFTron was kicked from this server.
                    $this->respond(200, (object)[
                        'unavailable' => true,
                        'icon' => $guild->icon,
                        'name' => $guild->name
                    ]);
                }
                $this->respond($e->code ?: 500, $e->message ?: "We cannot retrieve the info for this guild: {$e->details->HTTP}");
            }

            if ((!$permitted = $this->permitted($guildID)) && (!$staff = $this->is_staff()))
            {
                //if user is both not permitted AND not a staff member
                unset($guild->settings);
                unset($guild->wallet);
            }
            else
            {
                //Objective: return list of suggested channels, and list of available channels minus suggested channels
                function match_suggested($data, $matches)
                {
                    $matches = is_array($matches) ? $matches : [$matches];
                    foreach ($channels = array_filter(array_map(function ($channel) {
                        if (!(decbin($channel->type) & 3)) return $channel;
                    }, $data)) as $channel) {
                        if ($channel->type == 0) {
                            $tree[intval($channel->parent_id)][] = $channel->id;
                            $availableChannels[$channel->id] = $channel->name;
                        }
                    }
                    foreach ($lookup = array_column($channels, 'name', 'id') as $id => $name) {
                        foreach ($matches as $match) {
                            if (strstr(strtolower(preg_replace("/[^a-zA-Z]/", '', $name)), $match))
                            foreach (($tree[$id] ?: [$id]) as $channelid) {
                                $suggestedChannels[$channelid] = $lookup[$channelid];
                            }
                        }
                    }
                    return (object)["suggested" => $suggestedChannels,
                    "available" => array_diff($availableChannels, $suggestedChannels) ?: $availableChannels];
                }
                
                try {
                    $this->discord->bot->guilds->$guildID->channels;
                } catch (\Throwable $th) {
                    $e = $this->parseException($th);
                    if ($e->details->HTTP == 403)
                    {
                        $e->code = 400;
                        $e->message = "We need permission to see this guild's channels in order to complete this request.";
                    }
                    $this->respond($e->code ?: 500, $e->message ?: "We cannot retrieve the channels for this guild.");
                }
                
                if ($guild->settings->channels === null) $guildtemp->setup->channels = match_suggested($this->discord->bot->guilds->$guildID->channels, ["giveaway", "giving"]);
                if ($guild->settings->access_roles === null) $guildtemp->setup->access_roles = match_suggested($this->discord->bot->guilds->$guildID->info->roles, ["owner", "admin"]);
            }

            
            if ($this->user->guilds->$guildID != ($permitted ?? ($permitted = $this->permitted($guildID))))
            {
                //The value on file for the guild, in the user's list, is not what it now is... And it was a deliberate, direct request. Need to update.
                //Note: this operation happens REGARDLESS of whether or not the user was permitted or denied. We're just changing the status.
                $this->user->guilds->$guildID = $permitted;
                $this->storage->write("users/{$this->user->id}", $this->user);
            }

            if ((array)$guild->settings && $this->discord->bot->guilds->$guildID->info->icon != $guild->icon || $this->discord->bot->guilds->$guildID->info->name != $guild->name)
            {
                //Guild icon or name changed at some point
                $guild->icon = $this->discord->bot->guilds->$guildID->info->icon;
                $guild->name = $this->discord->bot->guilds->$guildID->info->name;
                $this->storage->write("guilds/$guildID", $guild);
            }

            //var_dump("We hit discord " . count(\DiscordLib\HTTP::$requests) . " times.", \DiscordLib\HTTP::$requests);
            $this->respond(200, array_merge((array)$guild, (array)$guildtemp));
        }
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST')
        {
            $guild = $this->storage->read("guilds/$guildID")->data ?: $this->respond(400, "We don't have this guild on file. Are you sure you have the right ID?");
            $this->permitted($guildID) ?: $this->respond(403, "Hey, don't go configuring guilds without permission. Go ask someone for access.");
            $settings = json_decode(file_get_contents("php://input"));

            foreach ($settings as $name => $value)
            {
                /*
                if (!in_array($name, array_keys(get_object_vars($guild->settings))))
                {
                    var_dump($name, array_keys(get_object_vars($guild->settings)));
                    unset($settings->$name);
                    continue;
                }
                */
                
                if ($name == "channels")
                {
                    $settings->$name = (array)$value;
                    $settings->$name = filter_var($settings->$name, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $settings->$name;
                    if ($settings->$name !== false)
                    {
                        foreach ($settings->$name as $i => $channel)
                        {
                            if (!in_array($channel, array_column($this->discord->bot->guilds->$guildID->channels, 'id')))
                            {
                                unset($settings->$name[$i]);
                            }

                            if (count($settings->$name))
                            {
                                //reindex, stringify
                                $settings->$name = array_map('strval', array_values($settings->$name));
                            }
                            else
                            {
                                //no valid channels passed, set to false
                                $settings->$name = false;
                            }
                        }
                    }
                }
                elseif ($name == "access_roles")
                {
                    $settings->$name = (array)$value;
                    $settings->$name = filter_var($settings->$name, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $settings->$name;
                    if ($settings->$name !== false)
                    {
                        foreach ($settings->$name as $i => $role)
                        {
                            if (!in_array($role, array_column($this->discord->bot->guilds->$guildID->info->roles, 'id')))
                            {
                                unset($settings->$name[$i]);
                            }
                        }

                        if (count($settings->$name))
                        {
                            //reindex, stringify
                            $settings->$name = array_map('strval', array_values($settings->$name));
                        }
                        else
                        {
                            //no valid access_roles passed, set to false
                            $settings->$name = false;
                        }
                    }                    
                }
                elseif ($name == "strict")
                {
                    if (!is_bool($settings->$name) || ($this->discord->bot->guilds->$guildID->info->owner_id != $this->user->id))
                    {
                        //isn't a boolean, so disregard
                        unset($settings->$name);
                    }
                }
            }

            //var_dump(array_column($this->discord->bot->guilds->$guildID->channels, 'name', 'id'));
            
            $newsettings = array_merge((array)$guild->settings, (array)$settings);
            $danger_will_robinson = !(($guild->settings->access_roles == $newsettings['access_roles']) && ($guild->settings->strict == $newsettings['strict']));
            $guild->settings = $newsettings;
            $this->storage->write("guilds/$guildID", $guild);

            
            /*
            
            Numbskull protocol

            if ($danger_will_robinson && !$guild->settings->access_roles && $guild->settings['strict'] && $this->user->id != $this->discord->bot->guilds->$guildID->info->owner_id)
            {
                //Settings were changed
                //Access roles are false
                //Strict is set
                //User is not owner
                //Yep, user is a numbskull: locked everyone out but the owner
                var_dump("numbskull");
                $this->discord->bot->directMessage($this->discord->bot->guilds->$guildID->info->owner_id,
                    "Hey, <@".$this->user->id."> just locked everyone but you out of {$this->discord->bot->guilds->$guildID->info->name}'s GIFTron Dashboard. Thought you should know."
                );
            }
            */
            //var_dump("We hit discord " . count(\DiscordLib\HTTP::$requests) . " times.", \DiscordLib\HTTP::$requests);
            $this->respond(200, $guild);
        }
    }

    function guild_permissions()
    {
        //error_reporting(E_ALL); ini_set('display_errors', 1);

        $guildID = $_GET['guild_id'];
        $channelID = $_GET['channel_id'];
        $entityID = $_SERVER['QUERY_STRING'];

        if ($_SERVER['REQUEST_METHOD'] == 'POST')
        {
            if ($guildID)
            {
                $entityID = $guildID;
                $type = 'guilds';
            }
            else if ($channelID)
            {
                $entityID = $channelID;
                $type = 'channels';
            }
            else
            {
                $this->respond(400, "Undefined entity type");
            }

            $settings = json_decode(file_get_contents("php://input"));
            
            try {
                $this->respond(200, $this->discord->bot->$type->$entityID->modify($settings));
            } catch (\Throwable $th) {
                $e = $this->parseException($th);
                if ($e->details->HTTP == 403)
                {
                    $e->code = 400;
                    $e->message = "We need permission to manage this guild's roles in order to complete this request.";
                }
                $this->respond($e->code ?: 500, $e->message ?: "We cannot manage the roles for this guild.");
            }
        }

        if ($_SERVER['REQUEST_METHOD'] == 'GET')
        {
            try {
                $channel = $this->discord->bot->channels->$entityID->info;
                $guildID = $channel->guild_id;
            } catch (\Throwable $th) {
                try {
                    $guild = $this->discord->bot->guilds->$entityID->info;
                    foreach ($guild->roles as $role)
                    {
                        if ($role->managed)
                        {
                            $permissions = $role->permissions;
                            break;
                        }
                    }
                    $this->respond(200, $this->discord->list_permissions($permissions));
                } catch (\Throwable $th) {
                    $this->respond(200, false);
                }
            }
    
            if (!$guild)
            {
                try {
                    $guild = $this->discord->bot->guilds->$guildID->info;
                } catch (\Throwable $th) {
                    $this->respond(200, false);
                }
            }
    
            try {
                $member = $this->discord->bot->guilds->$guildID->members->{$this->discord->bot->info->id}->info;
            } catch (\Throwable $th) {
                $this->respond(200, false);
            }
    
            //var_dump("We hit discord " . count(\DiscordLib\HTTP::$requests) . " times.", \DiscordLib\HTTP::$requests);
            $this->respond(200, $this->discord->list_permissions($this->discord->get_permissions($member, $guild, $channel)) ?: []);
        }
    }

    function guild_schedule_giveaway()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST')
        {
            $this->user ?: $this->respond(401, "Please log in.");
            $guildID = $_SERVER['QUERY_STRING'] ?: $this->respond(400, "We need to know what guild you want to schedule a giveaway for.");
            $guild = $this->storage->read("guilds/$guildID")->data;
            
            $channelnum = count($guild->settings->channels);

            if (!$channelnum)
            {
                $this->respond(400, "You need to setup a Giveaway channel before you can make a Giveaway!");
            }

            if ($channelnum == 1)
            {
                $channel = $guild->settings->channels[0];
            }

            $autodata = [
                "guild_id" => $guildID,
                "channel"  => $channel
            ];

            try {
                $giveaway = new Giveaway(array_merge($autodata, (array) json_decode(file_get_contents("php://input"))));
            } catch (Exception $e) {
                $this->respond(400, "You are missing the following parameters: {$e->getMessage()}");
            }
            
            if (!in_array($giveaway->channel, $guild->settings->channels))
            {
                $this->respond(400, "You can't schedule a Giveaway for a channel that's not setup for it.");
            }

            $id = "$giveaway->end-$giveaway->start-" . random_int(0,9999999999);
            
            if ($giveaway->key)
            {
                //GIFTron's got a key to give away - proceed

                if (!in_array($id, $guild->giveaways))
                {
                    $guild->giveaways[] = $id;
                }

                $this->storage->write("giveaways/$id", $giveaway);
                $this->storage->write("guilds/$guildID", $guild);
                $giveaway->id = $id;
                $this->respond(200, $giveaway);
            }

            if ($giveaway->game_id)
            {
                //GIFTron needs to buy this game

                if (!$guild->wallet)
                {
                    //$this->respond(400, "You don't have any money!");
                }

                if (!$gameInfo = $this->g2a->games->{$giveaway->game_id}->info->docs[0])
                {
                    $this->respond(400, "Invalid game");
                }

                $gameSlug = "https://www.g2a.com$gameInfo->slug";

                $order = [
                    "status"    => "pending",
                    "giveaway"  => $giveaway
                ];

                $this->storage->write("orders/$id", $order);

                try {
                    $this->discord->bot->channels->{SERVER_ORDERS}->postMessage(
                        "<@".$this->user->id."> from {$this->discord->bot->guilds->$guildID->info->name} placed order number `$id` for $gameSlug with \$$guild->wallet"
                    );
                } catch (\Throwable $th) {
                    $this->respond(500, "We could not alert our team about your order! Please contact them IMMEDIATELY!");
                }
                $order->id = $id;
                $this->respond(200, $order);
            }
        }

        if ($_SERVER['REQUEST_METHOD'] == 'GET')
        {
            if ($giveaway = $this->storage->read("giveaways/{$_SERVER['QUERY_STRING']}")->data)
            {
                $this->respond(200, $giveaway);
            }
            else
            {
                $this->respond(400, "We don't have this giveaway on file. Are you sure you have the right ID?");
            }
        }
    }

    function order()
    {
        $orderID = $_SERVER['QUERY_STRING'] ?: $this->respond(400, "We need an order ID.");
        $order = $this->storage->read("orders/$orderID")->data ?: $this->respond(400, "We don't have an order by this ID.");

        if ($_SERVER['REQUEST_METHOD'] == 'POST')
        {
            if ($order->status != 'pending')
            {
                $this->respond(200, "Order has already been $order->status.");
            }
            
            $price = $_POST['price'] ?: $this->respond(400, "We need to know how much this cost.");
            $key = $_POST['key'] ?: $this->respond(400, "We need to know what the key is for this game.");

            $guild = $this->storage->read("guilds/{$order->giveaway->guild_id}");
            
            $giveaway = $order->giveaway;
            unset($order->giveaway);

            if (!($guild->wallet - $price >= 0))
            {
                $order->status = "declined";
            }
            else
            {
                $order->status = "filled";
                $order->price = $price;
                $giveaway->key = $key;
                $guild->wallet -= $price;
                $guild->giveaways[] = $orderID;
                $this->storage->write("giveaways/$orderID", $giveaway);
            }

            $this->storage->write("orders/$orderID", $order);
            @$this->discord->bot->channels->{SERVER_ORDERS}->postMessage("Order `$orderID` has been $order->status.");
            $this->respond(200, $order);
        }

        if ($_SERVER['REQUEST_METHOD'] == 'GET')
        {
            $this->respond(200, $order);
        }
    }

    function staff_reauth($internal = false)
    {
        if (!$this->is_staff())
        {
            $this->respond(400, "You are not staff.");
        }

        if (!$_GET['clientId'] || !$_GET['clientSecret'] || !$_GET['botToken'])
        {
            try {
                $data = $this->discord->user->resetBotAuth();
            } catch (\Throwable $th) {
                $this->respond(403, "You aren't authorized to do this.");
            }
        }

        $returndata = json_encode(["clientId" => GIFTRON_ID, "clientSecret" => $data->secret ?: $_GET['clientSecret'], "botToken" => $data->bot->token ?: $_GET['botToken']]);
        file_put_contents("$this->phproot/metadata/credentials", $returndata);
        if ($internal)
        {
            return $returndata;
        }
        $this->respond(200, $returndata);
    }

    function storage_read()
    {
        $this->respond(200, $this->storage->read($_SERVER['QUERY_STRING']));
    }

    function storage_write()
    {
        $this->respond(200, $this->storage->write($_SERVER['QUERY_STRING'], json_decode(file_get_contents("php://input"), true)));
    }
}

function test()
{
    //define('FSROOT', realpath("../../../../webroot/giftron/api"));
    //var_dump(FSROOT . "/index.html", realpath("./documentation.md"));
    symlink('L:\Documents\GitHub\NERDev\git\GIFTron\GIFTron-Back-End\documentation.html', 'L:\Documents\GitHub\NERDev\webroot\giftron\api\index.html');
}

function build()
{
    define('FSROOT', realpath("../../../../webroot/giftron/api/v1"));
    function recurse($struct, $parent = null)
    {
        if (isset($struct->methods))
        {
            file_put_contents(getcwd() . "/index.php", $GLOBALS['seed']);
        }

        foreach($struct as $name => $properties)
        {
            if (gettype($properties) == "object")
            {
                $dirname = getcwd() . "/$name";
                mkdir($dirname);
                chdir($dirname);
                recurse($properties, $name);
            }
        }
        chdir("../");
    }
    
    echo "building API tree\n";
    $GLOBALS['seed'] = file_get_contents("seed.php");
    $schema = json_decode(file_get_contents("schema.json"));
    chdir(FSROOT);
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