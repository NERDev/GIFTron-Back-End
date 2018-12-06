<?php

define('ROOT', $_SERVER['DOCUMENT_ROOT']);

class Security
{
    function check_whitelist($ip)
    {
        $whitelist = json_decode(file_get_contents("ROOT"));
        return in_array($ip, $whitelist);
    }
}