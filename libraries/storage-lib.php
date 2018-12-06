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