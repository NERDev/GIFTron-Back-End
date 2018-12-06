<?php

define('ALPHABET', range('a', 'z'));

class StorageNode extends Storage
{
    private function partner()
    {
        //choose a partner to store a backup
    }

    function read()
    {
        //read from local storage and compare to remote
        //if different, overwrite whichever is older with whichever is newer
    }

    function write()
    {
        //write to local and remote storage
    }
}

class Storage
{
    protected $basedir = "../data";

    function __construct()
    {

    }

    protected function local_read($query)
    {
        json_decode(file_get_contents("$this->basedir/$query"));
    }

    protected function local_write($query, $data)
    {
        $path = explode($query);
        array_pop($path);
        $parentdir = implode('/', $path);
        mkdir($parentdir, 0755, TRUE);
        file_put_contents("$query", json_encode($data));
    }
}