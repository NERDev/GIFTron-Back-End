<?php

define('HERE', array_shift(explode('.', gethostname())));
define('ALPHABET', array_diff([HERE], range('a', 'z')));
define('ROOT', $_SERVER['DOCUMENT_ROOT']);

class StorageNode extends Storage
{
    

    function read()
    {
        //read from local storage and compare to remote
        //if different, overwrite whichever is older with whichever is newer
        if ($this->verify_familiarity($_SERVER["REMOTE_ADDR"], $_SERVER["REMOTE_HOST"]))
        {
            //request is trusted remote, this server must be secondary
        }
        else
        {
            //request is untrusted remote or local, must determine which
        }
    }

    function write($location, $data)
    {
        //write to local and remote storage
        if ($this->verify_familiarity($_SERVER["REMOTE_ADDR"], $_SERVER["REMOTE_HOST"]))
        {
            //request is trusted remote, this server must be secondary
        }
        else
        {
            //request is untrusted remote or local, must determine which
        }
    }
}

class Storage
{
    protected $basedir = ROOT . "/data";

    function __construct()
    {
        
    }

    protected function verify_familiarity($ip, $dns)
    {
        return $ip == gethostbyname($dns) && substr($dns, 1) == gethostname();
    }
    
    protected function partner()
    {
        //choose a partner to store a backup
        $counterfile = ROOT . "/metadata/counter";
        $count = file_get_contents($counterfile) ?? 0;
        file_put_contents($counterfile, ++$count);
        return ALPHABET[$count % 25];
    }

    protected function local_read($query)
    {
        return json_decode(file_get_contents("$this->basedir/$query"));
    }

    protected function local_write($query, $data)
    {
        $path = explode($query);
        array_pop($path);
        $parentdir = implode('/', $path);
        mkdir($parentdir, 0755, TRUE);
        return file_put_contents("$query", json_encode($data));
    }
}