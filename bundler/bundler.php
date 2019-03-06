<?php

//error_reporting(E_ALL); ini_set('display_errors', 1);

$path = dirname(__FILE__);
require_once $path . '/minify/Minify.php';
require_once $path . '/minify/CSS.php';
require_once $path . '/minify/JS.php';
require_once $path . '/minify/Exception.php';
require_once $path . '/minify/Exceptions/BasicException.php';
require_once $path . '/minify/Exceptions/FileImportException.php';
require_once $path . '/minify/Exceptions/IOException.php';

use MatthiasMullie\Minify;

function rglob($pattern, $flags = 0)
{
    $files = glob($pattern, $flags); 
    foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
        $files = array_merge($files, rglob($dir.'/'.basename($pattern), $flags));
    }
    return $files;
}

function compile($jsfiles)
{
    global $path;
    $doc = new DOMDocument();
    $doc->loadHTML(file_get_contents("$path/app.html"));
    $app = new Minify\JS("$path/js/app.js");
    foreach ($jsfiles as $jsfile)
    {
        $app->add($jsfile);
    }
    $script = $doc->createElement('script', $app->minify());
    $doc->getElementsByTagName('body')->item(0)->appendChild($script);
    return $doc->saveHTML();
}

$path = realpath("../../git/GIFTron/GIFTron-Front-End");
header_remove("X-Powered-By");
if (!$qs = preg_replace('/(\.{2,})|(\.git)/', '', $_SERVER['QUERY_STRING']))
{
    foreach($jsfiles = rglob("$path/js/components/*.js") as $i => $jsfile)
    {
        if ((($i == 0) && !file_exists("$path/compiled.html")) || filemtime($jsfile) > filemtime("$path/compiled.html"))
        {
            file_put_contents("$path/compiled.html", compile($jsfiles));
            break;
        }
    }
    echo file_get_contents("$path/compiled.html");
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