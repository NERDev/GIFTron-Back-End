<?php

namespace DiscordLib;

//error_reporting(E_ALL); ini_set('display_errors', 1);

abstract class HTTP
{
    public static $requests;
    public static $clientId;
    public static $clientSecret;
    public static $baseURL;

    private static function type($context)
    {
        if ($context == 'User')
        {
            return 'Bearer';
        }
        elseif ($context == 'Bot')
        {
            return 'Bot';
        }
        else
        {
            return false;
        }
    }

    private static function buildHeaders($data)
    {
        foreach ($data as $header => $value)
        {
            $headers[] = "$header: $value";
        }

        return implode("\r\n", $headers);
    }

    private static function parseHeaders($headers)
    {
        $responsecode = explode(' ', array_shift($headers))[1];
        if (!$responsecode) return;
        foreach ($headers as $header)
        {
            $headerdata = explode(":", $header, 2);
            $newheaders[$headerdata[0]] = trim($headerdata[1]);
        }
        $newheaders['HTTP'] = $responsecode;
        return $newheaders;
    }

    static function get($url)
    {
        self::$requests[] = $url;
        $prevobj = debug_backtrace()[1]['object'];
        $tokentype = self::type(explode('/', $prevobj->context)[0]);
        $response = json_decode(file_get_contents(self::$baseURL.$url, false, stream_context_create([
            'http' => [
                "header" => "Authorization: $tokentype {$prevobj->token}\r\n"
            ]
        ])));
        $headers = self::parseHeaders($http_response_header);
        if (substr($headers['HTTP'], 0, 1) == 2) return $response;
        if (!$headers) $headers['HTTP'] = 0;
        $headers['HTTP'] = intval($headers['HTTP']);
        throw new \Exception(json_encode($headers));
    }
    
    static function post($url, $data)
    {
        self::$requests[] = $url;
        $prevobj = debug_backtrace()[1]['object'];
        $tokentype = self::type(explode('/', $prevobj->context)[0]);
        $tokentype && $headers['Authorization'] = "$tokentype {$prevobj->token}";
        $headers['Content-Type'] = "application/" . (json_decode($data) ? 'json' : 'x-www-form-urlencoded');
        $response = json_decode(file_get_contents(self::$baseURL.$url, false, stream_context_create([
            'http' => [
                'header'  => self::buildHeaders($headers),
                'method'  => 'POST',
                'content' => $data,
            ]
        ])));
        $headers = self::parseHeaders($http_response_header);
        if (substr($headers['HTTP'], 0, 1) == 2) return $response;
        if (!$headers) $headers['HTTP'] = 0;
        $headers['HTTP'] = intval($headers['HTTP']);
        throw new \Exception(json_encode($headers));
    }
    
    static function patch($url, $data)
    {
        self::$requests[] = $url;
        $prevobj = debug_backtrace()[1]['object'];
        $tokentype = self::type(explode('/', $prevobj->context)[0]);
        $tokentype && $headers['Authorization'] = "$tokentype {$prevobj->token}";
        $headers['Content-Type'] = "application/" . (json_decode($data) ? 'json' : 'x-www-form-urlencoded');
        $response = json_decode(file_get_contents(self::$baseURL.$url, false, stream_context_create([
            'http' => [
                'header'  => self::buildHeaders($headers),
                'method'  => 'PATCH',
                'content' => $data,
            ]
        ])));
        $headers = self::parseHeaders($http_response_header);
        if (substr($headers['HTTP'], 0, 1) == 2) return $response;
        if (!$headers) $headers['HTTP'] = 0;
        $headers['HTTP'] = intval($headers['HTTP']);
        throw new \Exception(json_encode($headers));
    }
}

class User extends API
{
    public $token;
    public $context;

    function auth($code)
    {
        $redirectUri = "http".(!boolval($_SERVER['HTTPS'])?"s":"")."://".$_SERVER['SERVER_NAME'].$_SERVER['URL'];
        $response = HTTP::post('/oauth2/token', http_build_query([
            'client_id'     => HTTP::$clientId,
            'client_secret' => HTTP::$clientSecret,
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => $redirectUri,
            'scope'         => 'identify'
        ]));
        $this->token = $response->access_token;
        $this->refreshtoken = $response->refresh_token;
        $this->timeout = ($response->expires_in + time() - ini_get('default_socket_timeout'));
        return (bool)$response;
    }

    function reauth($refreshtoken)
    {
        $redirectUri = "http://dev.nerdev.io/giftron/api/v1/user/auth/index.php";
        $response = HTTP::post('/oauth2/token', http_build_query([
            'client_id'     => HTTP::$clientId,
            'client_secret' => HTTP::$clientSecret,
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refreshtoken,
            'redirect_uri'  => $redirectUri,
            'scope'         => 'identify'
        ]));
        $this->token = $response->access_token;
        $this->refreshtoken = $response->refresh_token;
        $this->timeout = ($response->expires_in + time() - ini_get('default_socket_timeout'));
        return (bool)$response;
    }

    function guilds()
    {
        return HTTP::get("/users/@me/guilds");
    }
}

class Channel
{
    public $token;
    public $context;
    public $id;

    function __construct($id)
    {
        $this->id = $id;
    }

    function __get($n)
    {
        if (method_exists($this, $n))
        {
            return $this->$n = $this->$n();
        }
    }

    function info()
    {
        return HTTP::get("/channels/$this->id");
    }

    function modify($settings)
    {
        return HTTP::patch("/channels/$this->id", json_encode($settings));
    }

    function postMessage($message)
    {
        return HTTP::post("/channels/$this->id/messages", json_encode([
            "content" => $message,
            "tts" => false
        ]));
    }
}

class Channels extends API
{
    public $token;
    public $context;

    function __get($n)
    {
        if (!in_array($n, get_class_vars($this)))
        {
            $this->$n = new Channel($n);
            $this->$n->id = $n;
            $this->$n->token = $this->token;
            $this->$n->context = $this->context;
            return $this->$n;
        }
    }
}

class Member
{
    public $token;
    public $context;
    public $id;

    function __construct($id)
    {
        $this->id = $id;
    }

    function __get($n)
    {
        if (method_exists($this, $n))
        {
            return $this->$n = $this->$n();
        }
    }

    function info()
    {
        return HTTP::get("/guilds/$this->guildid/members/$this->id");
    }
}

class Members
{
    public $token;
    public $context;

    function __get($n)
    {
        if (!in_array($n, get_class_vars($this)))
        {
            $this->$n = new Member($n);
            $this->$n->token = $this->token;
            $this->$n->context = $this->context;
            $this->$n->guildid = $this->guildid;
            return $this->$n;
        }
    }
}

class Guild
{
    public $token;
    public $context;
    public $id;

    function __construct($id)
    {
        $this->id = $id;
    }

    function __get($n)
    {
        $classname = __NAMESPACE__ . "\\$n";
        if (method_exists($this, $n))
        {
            return $this->$n = $this->$n();
        }
        elseif (class_exists($classname))
        {
            $this->$n = new $classname();
            $this->$n->token = $this->token;
            $this->$n->context = $this->context;
            $this->$n->guildid = $this->id;
            return $this->$n;
        }
        else
        {
            return false;
        }
    }

    function info()
    {
        return HTTP::get("/guilds/$this->id");
    }

    function channels()
    {
        return HTTP::get("/guilds/$this->id/channels");
    }
}

class Guilds extends API
{
    public $token;
    public $context;

    function __get($n)
    {
        if (!in_array($n, get_class_vars($this)))
        {
            $this->$n = new Guild($n);
            $this->$n->id = $n;
            $this->$n->token = $this->token;
            $this->$n->context = $this->context;
            return $this->$n;
        }
    }
}

class Bot extends API
{
    public $token;
    public $context;

    function directMessage($recipient, $message = null)
    {
        $channel = HTTP::post("/users/@me/channels", json_encode([
            "recipient_id" => $recipient
        ]));
        
        if ($message)
        {
            return HTTP::post("/channels/$channel->id/messages", json_encode([
                "content" => $message,
                "tts" => false
            ]));
        }
        else
        {
            return $channel->id;
        }
    }
}

class API
{
    
    function __construct($param1 = null, $param2 = null)
    {
        if (gettype($param1) == 'object')
        {
            $credentials = $param1;
            $this->bot->token = $credentials->botToken;
            HTTP::$clientId = $credentials->clientId;
            HTTP::$clientSecret = $credentials->clientSecret;
            HTTP::$baseURL = 'https://discordapp.com/api';
        }
        else
        {
            $this->context .= $param1;
            $this->token = $param2;
            //var_dump("context: $this->context", "token: $this->token");
        }
    }

    /*
    function __set($n, $v)
    {
        $this->$n = $v;
    }
    */    

    function __get($n)
    {
        $classname = __NAMESPACE__ . "\\$n";
        $context = get_parent_class($this) ? (new \ReflectionClass($this))->getShortName() : ucfirst($n);
        //var_dump("Here is where \$context needs to be determined.", $context, get_parent_class($this), $this);
        
        if (method_exists($this, $n))
        {
            return $this->$n = $this->$n();
        }
        elseif (class_exists($classname))
        {
            return $this->$n = new $classname($context, $this->token);
        }
        else
        {
            return false;
        }
    }

    function __call($name, $arguments)
    {
        throw new Exception("Cannot call method $name");
    }

    function info($user = '@me')
    {
        return HTTP::get("/users/$user");
    }

    /*
    function postMessage($message, $channelid)
    {
        return HTTP::post("/channels/$channelid/messages", $message, $this->token);
    }
    */

    function get_permissions($member, $guild, $channel)
    {
        $permission_calculator = new class {
            function __construct()
            {
                $this->ALL = 2146958847;
                $this->administrator = 0x8;
            }

            function compute_base_permissions($member, $guild)
            {
                if ($guild->owner_id == $member->id)
                {
                    return $this->ALL;
                }

                $roles = array_combine(array_column($guild->roles, 'id'), $guild->roles);
                $permissions = $roles[$guild->id]->permissions;

                foreach ($member->roles as $role)
                {
                    $permissions += $role->permissions;
                }

                if ($permissions & $this->administrator)
                {
                    return $this->ALL;
                }

                return $permissions;
            }

            function compute_overwrites($base_permissions, $member, $channel)
            {
                if (($base_permissions & $this->administrator) == $this->administrator)
                {
                    return $this->ALL;
                }

                $overwrites = array_combine(array_column($channel->permission_overwrites, 'id'), $channel->permission_overwrites);

                $permissions = $base_permissions;

                $overwrite_everyone = $overwrites[$channel->guild_id];
                if ($overwrite_everyone)
                {
                    $permissions &= ~$overwrite_everyone->deny;
                    $permissions += $overwrite_everyone->allow;
                }

                $allow = 0;
                $deny = 0;

                foreach ($member->roles as $role)
                {
                    if ($overwrites[$role->id])
                    {
                        $deny += $overwrites[$role->id]->deny;
                        $allow += $overwrites[$role->id]->allow;
                    }
                }


                $permissions &= ~$deny;
                $permissions += $allow;

                if ($overwrites[$member->user->id])
                {
                    $permissions &= ~$overwrites[$member->user->id]->deny;
                    $permissions += $overwrites[$member->user->id]->allow;
                }

                return $permissions;
            }
        };

        return $permission_calculator->compute_overwrites($permission_calculator->compute_base_permissions($member, $guild), $member, $channel);
    }

    function list_permissions($permcode)
    {
        $perms = [
            // General
            "generalCreateInstantInvite" => 0x1,
            "generalKickMembers" => 0x2,
            "generalBanMembers" => 0x4,
            "generalAdministrator" => 0x8,
            "generalManageChannels" => 0x10,
            "generalManageServer" => 0x20,
            "generalChangeNickname" => 0x4000000,
            "generalManageNicknames" => 0x8000000,
            "generalManageRoles" => 0x10000000,
            "generalManageWebhooks" => 0x20000000,
            "generalManageEmojis" => 0x40000000,
            "generalViewAuditLog" => 0x80,
            // Text
            "textAddReactions" => 0x40,
            "textReadMessages" => 0x400,
            "textSendMessages" => 0x800,
            "textSendTTSMessages" => 0x1000,
            "textManageMessages" => 0x2000,
            "textEmbedLinks" => 0x4000,
            "textAttachFiles" => 0x8000,
            "textReadMessageHistory" => 0x10000,
            "textMentionEveryone" => 0x20000,
            "textUseExternalEmojis" => 0x40000,
            // Voice
            "voiceViewChannel" => 0x400,
            "voiceConnect" => 0x100000,
            "voiceSpeak" => 0x200000,
            "voiceMuteMembers" => 0x400000,
            "voiceDeafenMembers" => 0x800000,
            "voiceMoveMembers" => 0x1000000,
            "voiceUseVAD" => 0x2000000,
            "voicePrioritySpeaker" => 0x100
        ];

        foreach ($perms as $perm => $code)
        {
            if ($permcode & $code)
            {
                $permlist[] = $perm;
            }
        }

        return $permlist;
    }
}