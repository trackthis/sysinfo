<?php

// Prepare constants
define('DS',DIRECTORY_SEPARATOR);
$logfile = dirname(__DIR__).DS.'islive-api.log';
ini_set('default_socket_timeout',10);
error_reporting(0);

// Prepare data
header('Content-Type: application/json');
$fd = fopen(dirname(__DIR__).DS.'islive-api.log','c+');
if(!$fd) die("{\"error\":\"Could not open log\"}");

// Log filtering & grouping
$now    = time();
$newlog = array();
$avg    = array(
   '0' => false,
   '1' => array(),
   '5' => array(),
  '15' => array()
);

// Read, filter & group log
while($line=fgets($fd)) {
  $log = json_decode($line,true);
  if($log['iat']<($now-900)) continue;
  array_push($newlog,json_encode($log));
  array_push($avg['15'],$log['rtt']);
  if($log['iat']>=($now-300)) array_push($avg['5'],$log['rtt']);
  if($log['iat']>=($now-60)) array_push($avg['1'],$log['rtt']);
}

// Build new log entry
$start = microtime(true);
$ctx   = stream_context_create(array('http'=>array('timeout'=>10)));
$res   = file_get_contents('http://www.islive.nl/api/', false, $ctx);
$end   = microtime(true);
if(!$res) {
  $end = $start + 10;
  $res = json_encode(array('result'=>array()));
}
$log   = array(
  'iat' => time(),
  'res' => count(json_decode($res,true)['result']),
  'rtt' => $end-$start,
);

// Add it to everything
$avg['0'] = $log;
array_push($newlog,json_encode($log));

// Write new log
fclose($fd);
$fd = fopen($logfile,'w');
fwrite($fd,implode(PHP_EOL,$newlog));
fclose($fd);

function sum($list) {
  return array_reduce($list,function($sum,$el) {
    return $sum+$el;
  },0);
}

// Build output
die(json_encode(array(
   '0' => $avg['0']['rtt'],
   '1' => sum($avg[ '1'])/max(1,count($avg[ '1'])),
   '5' => sum($avg[ '5'])/max(1,count($avg[ '5'])),
  '15' => sum($avg['15'])/max(1,count($avg['15'])),
)));
