<?php

// Our own basic one
if(!defined('DS')) define('DS',DIRECTORY_SEPARATOR);
spl_autoload_register(function($class_name) {
    $filename  = __DIR__.DS;
    $filename .= implode(DS,array_map('ucfirst',explode('\\',$class_name))).'.php';
    if(!file_exists($filename)) return;
    require($filename);
});

// Initialize composer
require(dirname(__DIR__).DS.'vendor'.DS.'autoload.php');
