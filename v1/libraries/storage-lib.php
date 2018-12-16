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
            $local = $this->local_read($location);
            $remote = json_decode(file_get_contents("http://$server1.dev.nerdev.io" . $_SERVER['REQUEST_URI']));

            if ($local && $remote)
            {
                //got a valid response from both servers, compare hashes
                if (!$local->hash || $remote->hash)
                {
                    //a hash is missing, handle
                }

                if ($local->hash != $remote->hash)
                {
                    //discrepancy, over-write whichever is older
                }

                if ($local->hash == $remote->hash)
                {
                    //success, everything checks out
                    return $local;
                }
            }

            if (!$local || !$remote)
            {
                //one of them screwed up... figure out which
                if (!$local && !$remote)
                {
                    //shit's broke, fam
                    return "oshit";
                }

                if (!$local && $remote)
                {
                    //local is blank, write remote locally
                }

                if ($local && !$remote)
                {
                    //remote is blank, write local remotely
                }
            }

            return (object)["error" => "unknown error, this shouldn't have happened"];
        }

        if ($secondary)
        {
            //get contents of location
            return $this->local_read($location);
        }

        if (!$secondary && !$primary)
        {
            //this must be the remote server, handle the request to the primary to get the ball rolling
            return file_get_contents("http://$server0.dev.nerdev.io/giftron/api/v1/storage/read?$location");
        }
    }

    function write($location, $data, $partnered = false)
    {
        //write to local and remote storage
        $server0 = $location[0];
        $server1 = $location[1];

        $primary = HERE == $server0;
        $secondary = HERE == $secondary;

        if ($primary)
        {
            //write locally and remotely
            return "kek";
        }

        if ($secondary)
        {
            //write locally
            echo "secondary reached";
        }

        if (!$secondary && !$primary)
        {
            //this must be the remote server, handle the request to the primary to get the ball rolling
            //delegate server pair unless $partnered is already set
            $partner = $this->partner();
            return $this->remote_write("http://$server0.dev.nerdev.io/giftron/api/v1/storage/write/?$location", $data);
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

    protected function hash($id)
    {
        $hash = md5('521130623750897694');
        if (is_numeric($hash))
        {
            return ALPHABET[$hash[0]] . ALPHABET[$hash[1]];
        }
        else
        {
            return preg_replace('/[0-9]+/', '', $hash);
        }
    }

    protected function unhash($hash)
    {
        //inverse of hash()
    }

    protected function local_read($query)
    {
        $data = json_decode(file_get_contents("$this->basedir/$query"));
        return (object)["data" => $data, "hash" => md5(json_encode($data)), "time" => filectime("$this->basedir/$query")];
    }

    protected function local_write($query, $data)
    {
        $path = explode($query);
        array_pop($path);
        $parentdir = implode('/', $path);
        mkdir("$this->basedir/$parentdir", 0755, TRUE);
        return file_put_contents("$this->basedir/$query", json_encode($data));
    }

    protected function remote_read()
    {
        //call API for read
    }

    protected function remote_write($url, $data)
    {
        //call API for write
        $options = [
            'http' => [
                'header'  => "Content-Type: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode($data),
            ]
        ];

        $context  = stream_context_create($options);
        $raw_response = file_get_contents($url, false, $context);
        //$response = json_decode($raw_response);
        return $raw_response;
    }
}