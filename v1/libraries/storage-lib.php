<?php

define('HERE', array_shift(explode('.', $_SERVER['SERVER_NAME'])));
define('ALPHABET', range('a', 'z'));

class StorageNode extends Storage
{
    

    function read($location)
    {
        //read from local storage and compare to remote
        //if different, overwrite whichever is older with whichever is newer
        $server0 = $location[0];
        $server1 = $location[1];

        $primary = HERE == $server0;
        $secondary = HERE == $server1;

        if ($primary)
        {
            //get contents of location, compare to secondary
            var_dump("this is the primary server: " . HERE, file_get_contents("http://$server1.dev.nerdev.io/giftron/api/v1/storage/read?$location"));
        }

        if ($secondary)
        {
            //get contents of location
            var_dump("this is the secondary server: " . HERE);
        }

        if (!$secondary && !$primary)
        {
            //this must be the remote server, handle the request to the primary to get the ball rolling
            var_dump("this is the remote server", file_get_contents("http://$server0.dev.nerdev.io/giftron/api/v1/storage/read?$location"));
            
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
            echo "primary reached";
        }

        if ($secondary)
        {
            //write locally
            echo "secondary reached";
        }

        if (!$secondary && !$primary)
        {
            //this must be the remote server, handle the request to the primary to get the ball rolling
            $partner = $this->partner();
            echo "chose $partner as partner";
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
        $counterfile = PHPROOT . "/metadata/counter";
        $count = intval(file_get_contents($counterfile));
        file_put_contents($counterfile, ++$count);
        return ALPHABET[$count % (count(ALPHABET))];
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