<?php

define('HERE', array_shift(explode('.', $_SERVER['SERVER_NAME'])));
define('ALPHABET', range('a', 'z'));

class StorageNode extends Storage
{
    function read($location)
    {
        $cachedData = str_replace('/', '_', $location);

        if ($this->$cachedData)
        {
            return $cachedData;
        }

        //read from local storage and compare to remote
        //if different, overwrite whichever is older with whichever is newer
        $filename = array_pop(explode('/', $location));
        $server0 = $this->locate($filename)[0];
        $server1 = $this->locate($filename)[1];

        $primary = HERE == $server0;
        $secondary = HERE == $server1;
        
        if (!$secondary && !$primary)
        {
            //this must be the remote server
            return $this->mediate($server0, $server1, $location);
        }

        return $this->$cachedData = $this->local_read($location);
    }

    function write($location, $data)
    {
        //sanitize location input


        //write to local and remote storage
        $filename = array_pop(explode('/', $location));
        $server0 = $this->locate($filename)[0];
        $server1 = $this->locate($filename)[1];

        $primary = HERE == $server0;
        $secondary = HERE == $server1;

        if (!$secondary && !$primary)
        {
            //this must be the remote server, handle the request to the primary to get the ball rolling
            //delegate server pair unless $partnered is already set
            //$partner = $this->partner();
            $receipt0 = json_decode($this->remote_write("http://$server0.dev.nerdev.io/giftron/api/v1/storage/write/?$location", $data));
            $receipt1 = json_decode($this->remote_write("http://$server1.dev.nerdev.io/giftron/api/v1/storage/write/?$location", $data));

            if (!$receipt0->hash && !$receipt1->hash)
            {
                return "both $server0 and $server1 are down... hell hath frozen over";
            }
            if (!$receipt0->hash xor !$receipt1->hash)
            {
                return $receipt0->hash ? "problem with $server1" : "problem with $server0";
            }

            return $receipt0;
        }

        return $this->local_write($location, $data);
    }
}

class Storage
{
    protected $basedir = ROOT . "/data/giftron";

    function __construct()
    {
        $limit = count(ALPHABET);
        $others = $limit - 1;
        for ($i = 0; $i < $limit; $i++)
        {
            for ($j = 0; $j < $others; $j++)
            {
                $pairs[] = ALPHABET[$i] . ALPHABET[($i + $j + 1) % $limit];
            }
        }
        $this->base650 = $pairs;
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
                    //discrepancy, throw out data that's old
                    $servertimes = [$server0result->time, $server1result->time];
                    $var = "server" . array_search(max($servertimes), $servertimes) . "result";
                    unset($$var->data);
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
            //one of them is blank... figure out which
            if (!$server0result->data && !$server1result->data)
            {
                //file not found
                return false;
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
                $this->remote_write("http://$server1$writeurl$location", $server0result->data);
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

    protected function locate($id)
    {
        $hash = sha1($id);
        $fragment = substr($hash, 0, 4);
        $int = base_convert($fragment, 16, 10) * 131;
        $remainder = (($int / 127) | 0) % 650;
        return $this->base650[$remainder];
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
        $id = array_pop($path);
        $parentdir = HERE . '/' . implode('/', $path);
        //file_put_contents("$this->basedir/log.txt", $query);
        mkdir("$this->basedir/$parentdir", 0755, TRUE);
        $jsondata = json_encode($data);
        file_put_contents("$this->basedir/" . HERE . "/$query", $jsondata);
        return ["id" => $id, "hash" => md5($jsondata)];
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