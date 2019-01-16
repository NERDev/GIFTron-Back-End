<?php

namespace DiscordLib;

//error_reporting(E_ALL); ini_set('display_errors', 1);

class HTTP
{
    public static $clientId;
    public static $clientSecret;

    static function get($url, $token, $tokentype = 'Bearer')
    {
        $options = [
            'http' => [
                "header" => "Authorization: $tokentype {$token}\r\n"
            ]
        ];

        $context  = stream_context_create($options);
        $raw_response = file_get_contents($url, false, $context);
        $response = json_decode($raw_response);
        return $response;
    }
    
    static function post($url, $data)
    {
        return json_decode(file_get_contents($url, false, stream_context_create([
            'http' => [
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data),
            ]
        ])));
    }
}

class User extends API
{
    protected $token;

    function auth($code)
    {
        //$redirectUri = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") ."://$_SERVER[HTTP_HOST]$_SERVER[PATH_INFO]");

        $redirectUri = "http".(!boolval($_SERVER['HTTPS'])?"s":"")."://".$_SERVER['SERVER_NAME'].$_SERVER['URL'];

        return (bool)$this->token = HTTP::post('https://discordapp.com/api/oauth2/token', [
            'client_id'     => HTTP::$clientId,
            'client_secret' => HTTP::$clientSecret,
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => $redirectUri,
            'scope'         => 'identify'
        ])->access_token;
    }
}

class Bot extends API
{
    protected $token;
}

class API
{
    
    function __construct($credentials = null)
    {
        if ($credentials)
        {
            $this->bot->token = $credentials->botToken;
            HTTP::$clientId = $credentials->clientId;
            HTTP::$clientSecret = $credentials->clientSecret;
        }
    }

    
    function __set($n, $v)
    {
        $this->$n = $v;
    }
    

    function __get($n)
    {
        $classname = __NAMESPACE__ . "\\$n";
        if (class_exists($classname))
        {
            return $this->$n = new $classname;
        }
        elseif (method_exists($this, $n))
        {
            return $this->$n();
        }
        else
        {
            return "I have no idea what $n is";
        }
    }

    function __call($name, $arguments)
    {
        throw new Exception("Cannot call method $name");
    }

    function info()
    {
        var_dump("going and getting information using token $this->token");
        return $this->{__FUNCTION__} = 'information';
    }

    function postMessage($message)
    {
        //var_dump($this);
        return "Posting '$message' on behalf of " . (new \ReflectionClass($this))->getShortName() . " using token: $this->token";
    }
}


function list_permissions($permcode)
{
    $perms = [
        // General
        "generalCreateInstantInvite" => 0x1,
        "generalKickMembers:" => 0x2,
        "generalBanMembers:" => 0x4,
        "generalAdministrator:" => 0x8,
        "generalManageChannels:" => 0x10,
        "generalManageServer:" => 0x20,
        "generalChangeNickname:" => 0x4000000,
        "generalManageNicknames:" => 0x8000000,
        "generalManageRoles:" => 0x10000000,
        "generalManageWebhooks:" => 0x20000000,
        "generalManageEmojis:" => 0x40000000,
        "generalViewAuditLog:" => 0x80,
        // Text
        "textAddReactions:" => 0x40,
        "textReadMessages:" => 0x400,
        "textSendMessages:" => 0x800,
        "textSendTTSMessages:" => 0x1000,
        "textManageMessages:" => 0x2000,
        "textEmbedLinks:" => 0x4000,
        "textAttachFiles:" => 0x8000,
        "textReadMessageHistory:" => 0x10000,
        "textMentionEveryone:" => 0x20000,
        "textUseExternalEmojis:" => 0x40000,
        // Voice
        "voiceViewChannel:" => 0x400,
        "voiceConnect:" => 0x100000,
        "voiceSpeak:" => 0x200000,
        "voiceMuteMembers:" => 0x400000,
        "voiceDeafenMembers:" => 0x800000,
        "voiceMoveMembers:" => 0x1000000,
        "voiceUseVAD:" => 0x2000000,
        "voicePrioritySpeaker:" => 0x100
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