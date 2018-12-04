<?php

error_reporting(E_ALL); ini_set('display_errors', 1);

require "./oauth2-lib.php";


$discord_oauth = new OAuth2Service([
    'clientId'                => '',    // The client ID assigned to you by the provider
    'clientSecret'            => '',   // The client password assigned to you by the provider
    'redirectUri'             => 'http://dev.api.nerdev.io/index.php',
    'urlBase'                 => 'https://discordapp.com/api',
    'urlAuthorize'            => 'https://discordapp.com/api/oauth2/authorize',
    'urlAccessToken'          => 'https://discordapp.com/api/oauth2/token'
]);

var_dump($discord_oauth->getAccessToken('authorization_code', $_GET['code']));
var_dump($discord_oauth->getUserInfo());