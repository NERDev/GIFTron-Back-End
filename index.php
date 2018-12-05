<?php

error_reporting(E_ALL); ini_set('display_errors', 1);

require "discord-lib.php";










$credentials = json_decode(file_get_contents("credentials"));

$discordAPI = new DiscordAPI($credentials->clientId, $credentials->clientSecret);




var_dump($discordAPI->getAccessToken('authorization_code', $_GET['code']));

var_dump($discordAPI->getUserInfo());