<?php

require "oauth2-lib.php";

class MagicProperty
{
    /*
    function __construct()
    {
        $reflector = new ReflectionClass($this);
        foreach ($reflector->getMethods(ReflectionMethod::IS_PRIVATE) as $method)
        {
            $this->{$method->name};
        }
    }
    */

    //somehow figure out how to get this to use DiscordAPI's methods...
    function __get($m)
    {
        global $discordAPI;
        return method_exists(DiscordAPI, $m) ?
        $this->$m = DiscordAPI::$m() :
        false;
    }
}

class DiscordAPI extends OAuth2Client
{
    public $user;
    public $bot;

    function __construct($params, $secret = null, $botToken = null)
    {
        $host = "discordapp.com";
        if(!@fsockopen($host, 443, $errno, $errstr, 30)) throw new Exception("Discord be ded.");

        if (gettype($params) == 'string')
        {
            $params = [
                "id" => $params,
                "secret" => $secret,
                "token" => $botToken
            ];
        }

        switch (gettype($params))
        {
            case 'array':
                break;
            case 'object':
                $params = (array)$params;
                break;
            case 'string':
                $params = [
                    "id" => $params,
                    "secret" => $secret,
                    "token" => $botToken
                ];
                break;
            default:
                throw new Exception("This doesn't work for parsing credentials");
        }
        
        foreach ($params as $k => $v)
        {
            $id = !stripos($k, 'id') ? $id : strval($params[$k]);
            $secret = !stripos($k, 'secret') ? $secret : strval($params[$k]);
            $token = !stripos($k, 'token') ? $token : strval($params[$k]);
        }

        if (!$secret || !$token)
        {
            throw new Exception("Missing " .
                ($secret ? "" : "secret") .
                ($token || $secret ? "" : " and ") .
                ($token ? "" : "bot token")
            );
        }

        $params = [
            'clientId'                => $id,
            'clientSecret'            => $secret,
            'redirectUri'             => "http://dev.nerdev.io/giftron/api/v1/login",
            'urlBase'                 => "https://$host/api",
            'urlAuthorize'            => "https://$host/api/oauth2/authorize",
            'urlAccessToken'          => "https://$host/api/oauth2/token"
        ];

        parent::__construct($params);

        
        $this->user = new MagicProperty;
        /*
        $reflector = new ReflectionClass($this);
        foreach ($reflector->getProperties(ReflectionProperty::IS_PUBLIC) as $property)
        {
            foreach ($reflector->getMethods(ReflectionMethod::IS_PRIVATE) as $method)
            {
                $this->{$property->name} = new MagicProperty;
            }
        }
        */
        var_dump($this);
    }

    static function list_permissions($permcode)
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

    static function info()
    {
        return ["dddd", "ffffadfs"];
    }
}