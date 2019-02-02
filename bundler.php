<?php

$path = realpath("../../git/GIFTron/GIFTron-Front-End");

if (!$qs = $_SERVER['QUERY_STRING'])
{
    echo file_get_contents("$path/app.html");
}
else
{
    $type = end(explode('.', $qs));
    if ($type == 'css')
    {
        header("Content-type: text/$type");
    }
    echo file_get_contents("$path/$qs");
}