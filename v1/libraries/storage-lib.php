<?php

define('HERE', array_shift(explode('.', gethostname())));
define('ALPHABET', array_diff([HERE], range('a', 'z')));

class StorageNode extends Storage
{
    

    function read($location)
    {
        //read from local storage and compare to remote
        //if different, overwrite whichever is older with whichever is newer
        $server0 = $location[0];
        $server1 = $location[1];

        $primary = HERE == $server0;
        $secondary = HERE == $secondary;

        if ($primary)
        {
            //get contents of location, compare to secondary
        }

        if ($secondary)
        {
            //get contents of location
        }

        if (!$secondary && !$primary)
        {
            //this must be the remote server, handle the request to the primary to get the ball rolling
        }
    }

    function write($location, $data)
    {
        //write to local and remote storage
        $server0 = $location[0];
        $server1 = $location[1];

        $primary = HERE == $server0;
        $secondary = HERE == $secondary;

        if ($primary)
        {
            //write locally and remotely
        }

        if ($secondary)
        {
            //write locally
        }

        if (!$secondary && !$primary)
        {
            //this must be the remote server, handle the request to the primary to get the ball rolling
        }
    }
}

class Storage
{
    protected $basedir = ROOT . "/data";

    function __construct()
    {
        
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

    protected function remote_read()
    {
        //call API for read
    }

    protected function remote_write()
    {
        //call API for write
    }
}