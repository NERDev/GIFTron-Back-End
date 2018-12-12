<?php

define('ROOT', realpath($_SERVER['DOCUMENT_ROOT'] . '/..'));

class Security
{
    private $whitelist;

    function __construct()
    {
        $this->phproot  = realpath(ROOT . '/git/GIFTron/GIFTron-Back-End');
        $this->webroot  = realpath(ROOT . '/webroot');
        $this->version  = preg_split('/[\\x5c\/]/', str_replace(ROOT, '', __FILE__))[4];
        $this->whitelist = json_decode(file_get_contents("$this->phproot/metadata/whitelist"));
    }

    function trusted_server($ip)
    {
        return in_array($ip, $this->whitelist);
    }

    function require_methods($methods)
    {
        $methods = gettype($methods) == "array" ? $methods : [$methods];
        return in_array($_SERVER['REQUEST_METHOD'], $methods);
    }
}