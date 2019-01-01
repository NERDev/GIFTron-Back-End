<?php

define('ROOT', realpath($_SERVER['DOCUMENT_ROOT'] . '/..'));
define('PHPROOT', realpath(ROOT . '/git/GIFTron/GIFTron-Back-End'));
define('WEBROOT', realpath(ROOT . '/webroot'));
define('VERSION', preg_split('/[\\x5c\/]/', str_replace(ROOT, '', __FILE__))[4]);

//add defines for phproot, webroot, version, and whitelist

class Security
{
    private $whitelist;

    function __construct()
    {
        //$this->phproot  = realpath(ROOT . '/git/GIFTron/GIFTron-Back-End');
        //$this->webroot  = realpath(ROOT . '/webroot');
        //$this->version  = preg_split('/[\\x5c\/]/', str_replace(ROOT, '', __FILE__))[4];
        $this->phproot = PHPROOT;
        $this->webroot = WEBROOT;
        $this->version = VERSION;
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

    protected function respond($status, $data)
    {
        http_response_code($status);
        //$data = gettype($data) == "object" ? $data : (object)$data;
        $data = json_decode($data) ? $data : json_encode($data);
        exit($data);
    }

    protected function parse_session($sessionID)
    {
        return $this->storageAPI->read("sessions/$sessionID")->data;
    }

    //Old Hash Function
    /*
    protected function hash($id)
    {
        $half = strlen($id) / 2;
        $firsthalf = substr($id, 0, $half);
        $secondhalf= substr($id, $half);
        
        
        $firsttotal = 0;
        $secondtotal = 0;
        for($i = 0; $i<$half; $i++)
        {
            $firsttotal += $firsthalf[$i];
            $secondtotal += $secondhalf[$i];
        }
        
        $firstletter = ALPHABET[$firsttotal % 26];
        $secondletter = ALPHABET[$secondtotal % 26];

        return "$firstletter$secondletter$id";
        
        //$id = incoming id;
        //logic!
        //return outgoing id;
    }
    */
}