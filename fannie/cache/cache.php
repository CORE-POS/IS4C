<?php

/* WFC Report Caching - not configurarable at the moment*/

/*
if (!class_exists("SQLManager"))
    require(__DIR__ . "/../src/SQLManager.php");

function db(){
    return new SQLManager('192.168.1.3','PGSQL','html_cache','wfc_pos','is4c');
}
*/

function get_cache($type){
    $type = strtolower($type);
    
    // match type
    if ($type[0]=='m') $type='monthly';
    elseif($type[0]=='d') $type='daily';
    else return False;

    $key = md5($_SERVER['REQUEST_URI']);

    if (file_exists(__DIR__ . '/cachefiles/'.$type.'/'.$key))
        return file_get_contents(__DIR__ . '/cachefiles/'.$type.'/'.$key);
    else
        return False;
}

function put_cache($type,$content){
    $type = strtolower($type);

    // match type
    if ($type[0]=='m') $type='monthly';
    elseif($type[0]=='d') $type='daily';
    else return False;

    $key = md5($_SERVER['REQUEST_URI']);
    $fp = fopen(__DIR__ . '/cachefiles/'.$type.'/'.$key,'w');
    fwrite($fp,$content);
    fclose($fp);
}

