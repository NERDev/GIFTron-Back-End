<?php

//error_reporting(E_ALL); ini_set('display_errors', 1);

$path = realpath("../../git/GIFTron/GIFTron-Front-End");
header_remove("X-Powered-By");
if (!$qs = preg_replace('/\.{2,}/', '', $_SERVER['QUERY_STRING']))
{
    echo file_get_contents("$path/app.html");
}
else
{
    $ext = end(explode('.', $qs));
    switch ($ext) {
        case 'svg':
            $type = "image/$ext+xml";
            break;
        
        case 'png':
            $type = "image/$ext";
            break;

        case 'ico':
            $type = "image/x-icon";
            break;

        default:
            $type = "text/$ext";
            break;
    }
    header("Content-type: $type");
    echo file_get_contents("$path/$qs");
}