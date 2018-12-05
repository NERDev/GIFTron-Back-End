<?php

require "oauth2-lib.php";

class DiscordAPI extends OAuth2Service
{    
    function __construct($id, $secret)
    {
        $params = [
            'clientId'                => $id,
            'clientSecret'            => $secret,
            'redirectUri'             => 'http://dev.api.nerdev.io/index.php',
            'urlBase'                 => 'https://discordapp.com/api',
            'urlAuthorize'            => 'https://discordapp.com/api/oauth2/authorize',
            'urlAccessToken'          => 'https://discordapp.com/api/oauth2/token'
        ];

        parent::__construct($params);
    }
    
    function getUserInfo()
    {
        return $this->get("$this->urlBase/users/@me");
    }
}