<?php

$path = realpath("../../git/GIFTron/GIFTron-Front-End");

if (!$qs = $_SERVER['QUERY_STRING'])
{
    echo file_get_contents("$path/app.html");
}
else
{
    $ext = end(explode('.', $qs));
    switch ($ext) {
        case 'css':
            $type = "text/$ext";
            break;

        case 'svg':
            $type = "image/$ext+xml";
            break;
        
        default:
            $type = "text/html";
            break;
    }
    header("Content-type: $type");
    echo file_get_contents("$path/$qs");
}