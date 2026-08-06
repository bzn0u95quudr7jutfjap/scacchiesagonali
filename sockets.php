<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// $server = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
// socket_bind($server, '127.0.0.1', 8080);
// socket_listen($server);
//
// $clients = [];
//
// while (true) {
//     $read = array_merge([$server], $clients);
//
//     // Add STDIN
//     $stdin = fopen("php://stdin", "r");
//     $read[] = $stdin;
//
//     $write = null;
//     $except = null;
//
//     socket_select($read, $write, $except, null);
//
//     foreach ($read as $sock) {
//
//         // New connection
//         if ($sock === $server) {
//             $client = socket_accept($server);
//             $clients[] = $client;
//             echo "Client connected\n";
//             continue;
//         }
//
//         // Input from terminal
//         if ($sock === $stdin) {
//             $line = trim(fgets($stdin));
//
//             if (strlen($line) == 7) {
//                 foreach ($clients as $client) {
//                     socket_write($client, $line);
//                 }
//             }
//
//             continue;
//         }
//
//         // Client disconnected?
//         $buf = '';
//         $bytes = @socket_recv($sock, $buf, 1024, MSG_DONTWAIT);
//
//         if ($bytes === 0) {
//             $key = array_search($sock, $clients, true);
//             unset($clients[$key]);
//             socket_close($sock);
//             echo "Client disconnected\n";
//         }
//     }
// }
//
// die(0);
//
// print_r($_SERVER);
//
// $a = var_export($_SERVER,true);
// file_put_contents('/tmp/lumine/a.txt',$a);
//
// die(0);

$stdin = socket_create(AF_UNIX,SOCK_DGRAM,0);
$a = socket_create(AF_INET,SOCK_STREAM,0);

$sighandler = function ($sig) use ($a,$stdin) {
  echo "CATTURA SEGNALE -> $sig\n";
  socket_close($a);
  socket_close($stdin);
  die(0);
};
pcntl_signal(SIGTERM, $sighandler);
pcntl_signal(SIGINT , $sighandler);

while(!socket_bind($stdin,'.partite/websocket.stdin')){ sleep(6); }
while(!socket_bind($a,'localhost',8889)){ sleep(6); }

socket_listen($a,64);
$sessioni = [];
$readers = [$stdin, $a];
while (true) {
  $reads = $readers;
  $writes = null;
  $expect = null;
  $c = socket_select($reads,$writes,$expects,null);
  if (false === $c) {
    socket_close($a);
    socket_close($stdin);
    die(1);
  }
  foreach($reads as $r){
    if ($r === $a) {
      array_push($sessioni,$b = socket_accept($r));
      socket_recv($b, $httpresp, 2048, 0);
      if(1 !== preg_match('/(?<=Sec-WebSocket-Key: ).*\r?\n?/',$httpresp,$wsk)){
        socket_close($b);
        die(1);
      }
      $wsk = trim($wsk[0]);
      $acceptresp = base64_encode(sha1($wsk . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
      $acceptresp =
        "HTTP/1.1 101 Switching Protocols"
        . "\nUpgrade: websocket"
        . "\nConnection: Upgrade"
        . "\nSec-WebSocket-Accept: $acceptresp"
        . "\n"
        . "\n";
      socket_write($b, $acceptresp);
    }
    if ($r === $stdin) {
      $line = socket_read($r,1024);
      $line = chr(0x81) . chr(strlen($line)) . $line;
      $eliminiabili = [];
      foreach($sessioni as $k => $sess){
        $send = socket_write($sess,$line);
        if (false === $send) {
          echo socket_strerror(socket_last_error($socket))."\n";
          $eliminiabili[] = $k;
        }
      }
      foreach($eliminiabili as $e){
        unset($sessioni[$e]);
      }
    }
  }
}
