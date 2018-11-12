<?php

// Prepare constants
require(dirname(__DIR__).DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'autoload.php');
$logfile = dirname(__DIR__).DS.'pings.log';
ini_set('default_socket_timeout',10);
error_reporting(0);

// Helper
function sum($list) {
    return array_reduce($list,function($a,$b) {return $a+$b;},0);
}

// Fetch history
$now     = round(microtime(true)*1000);
$db      = new \DB\Collection($logfile, \Entity\Ping::class);
$data    = $db->reduce(function($accumulator, \Entity\Ping $ping) use ($db,$now) {
    if(is_null($ping->getExp())) {
        $db->delete($ping);
        return $accumulator;
    }
    if($ping->getExp()<$now) {
        $db->delete($ping);
        return $accumulator;
    }
    if(!$ping->getUrl()) return $accumulator;
    if(!key_exists($ping->getUrl(),$accumulator))
        $accumulator[$ping->getUrl()] = array();
    $accumulator[$ping->getUrl()][] = $ping;
    return $accumulator;
}, array());

// Make new pings
$fd = fopen(dirname(__DIR__).DS.'ping_urls.txt','c+');
while(($line=fgets($fd))!==false) {
    $line = trim($line);
    $start = round(microtime(true)*1000);
    $ctx   = stream_context_create(array('http'=>array('timeout'=>10)));
    $res   = file_get_contents($line, false, $ctx);
    $end   = round(microtime(true)*1000);
    $ping  = new \Entity\Ping(array(
        'rtt' => $end - $start,
        'url' => $line
    ));
    $db->save($ping);
    $data[$line][] = $ping;
}

// Group the pings
$min_1 = round((microtime(true)-60)*1000);
$min_5 = round((microtime(true)-300)*1000);
$min_15 = round((microtime(true)-900)*1000);
foreach ($data as $url => $pings ) {
    $data[$url] = array_reduce($pings, function ($acc, \Entity\Ping $ping) use ($min_1,$min_5,$min_15) {
        $acc['now'] = $ping->getRtt();
        if($ping->getIat()>$min_1)  $acc['min_1'][]  = $ping->getRtt();
        if($ping->getIat()>$min_5)  $acc['min_5'][]  = $ping->getRtt();
        if($ping->getIat()>$min_15) $acc['min_15'][] = $ping->getRtt();
        return $acc;
    }, array(
        'now'    => 0,
        'min_1'  => array(),
        'min_5'  => array(),
        'min_15' => array(),
    ));
}

// Make averages
foreach ($data as $url => $list ) {
    $data[$url]['min_1']  = round(sum($data[$url]['min_1'])  / count($data[$url]['min_1']));
    $data[$url]['min_5']  = round(sum($data[$url]['min_5'])  / count($data[$url]['min_5']));
    $data[$url]['min_15'] = round(sum($data[$url]['min_15']) / count($data[$url]['min_15']));
}

// Output our findings
header('Content-Type: application/json');
echo json_encode($data);

//// Fetch url list
//$urls = file_get_contents(dirname(__DIR__).DS.'ping_urls.txt');
//$urls = str_replace("\r\n","\n",$urls);
//$urls = str_replace("\r","\n",$urls);
//$urls = explode("\n",$urls);


//var_dump($data);
//var_dump($dellist);
