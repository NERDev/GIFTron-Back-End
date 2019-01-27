<?php

function minify($stream)
{
    $expressions = array(
        'MULTILINE_COMMENT'     => '\Q/*\E[\s\S]+?\Q*/\E',
        'SINGLELINE_COMMENT'    => '(?:http|ftp)s?://(*SKIP)(*FAIL)|//.+',
        'WHITESPACE'            => '^\s+|\R\s*'
    );

    foreach ($expressions as $key => $expr) {
        $stream = preg_replace('~'.$expr.'~m', '', $stream);
    }

    return $stream;
}

$path = realpath("../../git/GIFTron/GIFTron-Front-End");

$dom = new DOMDocument;
$dom->loadHTML(file_get_contents("$path/app.html"));
$scripts = $dom->getElementsByTagName('script');
$styles = $dom->getElementsByTagName('style');

foreach ($scripts as $script)
{
    $attr = $script->getAttribute('src');
    if (!filter_var($attr, FILTER_VALIDATE_URL))
    {
        $script->removeAttribute('src');
        $script->textContent = minify(file_get_contents("$path/$attr"));
    }
}
echo $dom->saveHTML();