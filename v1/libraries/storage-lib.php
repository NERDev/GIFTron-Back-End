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
        
        if (!$secondary && !$primary)
        {
            //this must be the remote server
            return $this->mediate($server0, $server1, $location);
        }

        return $this->local_read($location);
    }

    function write($location, $data, $partnered = false)
    {
        //write to local and remote storage
        $server0 = $location[0];
        $server1 = $location[1];

        $primary = HERE == $server0;
        $secondary = HERE == $server1;

        if ($primary)
        {
            //write locally and remotely
            $this->local_write($location, $data);
            return $this->remote_write("http://$server1.dev.nerdev.io" . $_SERVER['REQUEST_URI'], $data);
        }

        if ($secondary)
        {
            //write locally
            $this->local_write($location, $data);
            return "secondary reached, chain was: $server0$server1... verification: " . HERE;
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

    protected function mediate($server0, $server1, $location)
    {
        $readurl = ".dev.nerdev.io/giftron/api/v1/storage/read?";
        $writeurl = ".dev.nerdev.io/giftron/api/v1/storage/write/?";
        $server0raw = file_get_contents("http://$server0$readurl$location");
        $server0result = json_decode($server0raw);

        $server1raw = file_get_contents("http://$server1$readurl$location");
        $server1result = json_decode($server1raw);

        if ($server0result && $server1result)
        {
            //got a valid response from both servers, compare hashes
            if ($server0result->hash && $server1result->hash)
            {
                if ($server0result->hash != $server1result->hash)
                {
                    //discrepancy, over-write whichever is older
                }

                if ($server0result->hash == $server1result->hash)
                {
                    //success, everything checks out
                    return $server0result;
                }
            }            
        }

        if (!$server0result->data || !$server1result->data)
        {
            //one of them screwed up... figure out which
            if (!$server0result->data && !$server1result->data)
            {
                //shit's broke, fam
                return "oshit";
            }

            if (!$server0result->data && $server1result->data)
            {
                //local is blank, write remote locally
                $this->remote_write("http://$server0$writeurl$location", $server1result->data);
                return $server1result;
            }

            if ($server0result->data && !$server1result->data)
            {
                //remote is blank, write local remotely
                $this->remote_write("http://$server0$writeurl$location", $server0result->data);
                return $server0result;
            }
        }
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
            //select 2 letters
            return preg_replace('/[0-9]+/', '', $hash);
        }
    }

    protected function unhash($hash)
    {
        //inverse of hash()
    }

    protected function local_read($query)
    {
        //add location and id to return parameters
        $data = json_decode(file_get_contents("$this->basedir/" . HERE . "/$query"));
        $encodeddata = json_encode($data);
        $hash = $data ? md5($encodeddata) : null;
        return (object)["data" => $data, "hash" => $hash, "time" => filectime("$this->basedir/" . HERE . "/$query")];
    }

    protected function local_write($query, $data)
    {
        $path = explode('/', $query);
        array_pop($path);
        $parentdir = HERE . implode('/', $path);
        mkdir("$this->basedir/$parentdir", 0755, TRUE);
        return file_put_contents("$this->basedir/" . HERE . "/$query", json_encode($data));
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