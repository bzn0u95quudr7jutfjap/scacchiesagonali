<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

$api = php_sapi_name();

if ('cli-server' == $api) {
  goto SERVER;
}

if('cli' !== $api){
  echo "errore: php_sapi_name() invalido: richiesto 'cli': '$api'\n";
  die(0);
}

$outdir = __DIR__ . '/.partite/';
$b_outdir_exists = file_exists($outdir);
$b_outdir_isdir  = is_dir($outdir);
if ($b_outdir_exists && !$b_outdir_isdir) {
  die("outdir esiste e non è una cartella :: $outdir\n");
}
if (!$b_outdir_exists) {
  mkdir($outdir,0700);
}
if (!chdir($outdir)){
  die("errore: outdir invalida: $outdir\n");
}
echo "pwd: ".getcwd()."\n";

$i=1; $portaBrowser = ((1+$i) <= $argc) ? (int)($argv[$i]) : 8888;
$i++; $portaWebSock = ((1+$i) <= $argc) ? (int)($argv[$i]) : 8889;
$i++; $internalSock = ((1+$i) <= $argc) ? (int)($argv[$i]) : '.stdin.usock';

$netscan =  shell_exec('ip a');
$netscan .= shell_exec('ifconfig');
preg_match_all('/(?<!\d)(?!255)\d+\.(?!255)\d+\.(?!255)\d+\.(?!255)\d+/',$netscan,$ips);
$ips = $ips[0];
$ips = array_unique($ips);
print_r($ips);
echo "seleziona ip esposto: ";
fscanf(STDIN,"%d",$i);
$indirizzoRemoto = $ips[$i];

echo "portaBrowser : $portaBrowser\n";
echo "portaWebSock : $portaWebSock\n";
echo "internalSock : $internalSock\n";
echo "indirizzoRemoto: $indirizzoRemoto\n";

$websocketCode = <<<eof
<?php
  \$portaWebSock = (int)$portaWebSock;
  \$internalSock = '$internalSock';
eof . <<<'eof'
  ini_set('display_errors', 2);
  ini_set('display_startup_errors', 2);
  error_reporting(E_ALL);
//
  $stdin = socket_create(AF_UNIX,SOCK_DGRAM,0);
  $a = socket_create(AF_INET,SOCK_STREAM,0);
  $sessioni = [];
//
  $sighandler = function ($sig) use ($a,$stdin,$sessioni) {
    echo "CATTURA SEGNALE -> $sig\n";
    socket_close($a);
    socket_close($stdin);
    foreach($sessioni as $s){ socket_close($s); }
    die(0);
  };
//
  pcntl_signal(SIGTERM, $sighandler);
  pcntl_signal(SIGINT , $sighandler);
//
  unlink($internalSock);
  socket_set_option($a,SOL_SOCKET,SO_REUSEADDR,1);
  while(!socket_bind($stdin,$internalSock)){       echo "1\n"; sleep(6); }
  while(!socket_bind($a,'0.0.0.0',$portaWebSock)){ echo "2\n"; sleep(6); }
  echo "0\n";
  socket_listen($a,64);
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
            echo socket_strerror(socket_last_error($sess))."\n";
            $eliminiabili[] = $k;
          }
        }
        foreach($eliminiabili as $e){
          unset($sessioni[$e]);
        }
      }
    }
  }
eof;
$websocketProc = proc_open(['php'],[['pipe','r'],['pipe','w'],['pipe','w']],$websocketProcPipes);
fputs($websocketProcPipes[0],$websocketCode);
fclose($websocketProcPipes[0]);
if('0' !== ($line = stream_get_line($websocketProcPipes[1],2,"\n"))){
  echo "errore: websocketProc mal inizializzato: $line\n";
  fclose($websocketProcPipes[1]);
  fclose($websocketProcPipes[2]);
  proc_terminate($websocketProc);
  die(0);
}
echo "websocketProc inizializzato at '0.0.0.0:$portaWebSock' e '$internalSock'\n";
shell_exec("W='$indirizzoRemoto:$portaWebSock' U='$internalSock' php --server $indirizzoRemoto:$portaBrowser ".escapeshellarg(__FILE__));
die(0);

SERVER:
['W' => $webSocketAddr, 'U' => $internalSock] = getenv();
  if(!array_key_exists('a',$_GET)){
    $a_partite = '*';
    $a_partite = glob($a_partite);
    $a_partite = array_map(fn($a) => <<<eof
      <div class='wrap' >
        <h3>$a</h3>
        <div class='giocaCome'>
          <a href='?a=B{$a}P'>Gioca come bianco</a>
          <div> &lt;--&gt; </div>
          <a href='?a=N{$a}P'>Gioca come nero</a>
        </div>
      </div>
      eof,$a_partite);
    $a_partite = implode('',$a_partite);
    $html = <<<eof
    <!DOCTYPE html>
    <html>
      <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
          html, body, div { margin: 0px; padding 0px; }
          div.wrap { border: 1px solid black; margin: 20px; padding: 20px; }
          div.giocaCome { display:flex; margin: 10px; }
          div.giocaCome a { display:block; width: 100px; margin: 10px; text-align: center; }
        </style>
      </head>
      <body>
        <h1> Scacchi Esagonali </h1>
        <a href='?a=N'>Crea una nuova partita</a>
        <h2> Partite attive </h2>
        {$a_partite}
      </body>
    </html>
    eof;
    echo $html;
    die(0);
  }

  $a = $_GET['a'];
  if ('N' === $a) {
    $condizioni_iniziali = <<<eof
      PBBKBK PBCJCJ PBDIDI PBEHEH PBFGFG PBGGGG PBHGHG PBIGIG PBJGJG
      PNBEBE PNCECE PNDEDE PNEEEE PNFEFE PNGDGD PNHCHC PNIBIB PNJAJA
      ABFKFK ABFJFJ ABFIFI
      TBCKCK TBIHIH
      CBDKDK CBHIHI
      DBEKEK
      RBGJGJ
      ANFAFA ANFBFB ANFCFC
      TNCDCD TNIAIA
      CNDCDC CNHAHA
      DNEBEB
      RNGAGA\n
      eof;
    $nuova_partita = chr(65 + count(glob('*')));
    echo "$nuova_partita\n";
    $nuova_partita = file_put_contents($nuova_partita,$condizioni_iniziali);
    if(false === $nuova_partita){ die('ERRORE: SCRITTURA DI UNA NUOVA PARTITA'); }
    header('Location: ?');
    die(0);
  }

  $l = strlen($a);
  $c = $a[$l - 1];
  $p = $a[$l - 1 - 1];
  switch($c){
  case 'L' :{
    echo file_get_contents($p);
    break;
  };
  case 'U': {
      $partita = fopen($p,'r');
      if(false === $partita) {
        die("errore nell'apertura di $partita");
      }
      $mossalen = 6;
      fseek($partita,-($mossalen+1),SEEK_END);
      $mossa = fread($partita,$mossalen);
      echo $mossa;
      fclose($partita);
    } break;
  case 'M': {
      $mossa = substr($a,$l-1-1-6,6);
      $is_mossa_valida = preg_match('/[PTCADR][BN][A-Z][A-Z][A-Z][A-Z]/',$mossa);
      if (1 != $is_mossa_valida) { die('ERRORE: MOSSA INVALIDA: '.$mossa); }
      $partita = fopen($p,'a');
      if(false === $partita) { die("ERRORE: NELL'APERTURA DI $p"); }
      if(false === fputs($partita,$mossa)) { die("errore: scrivendo la mossa"); }
      if(false === fputs($partita,"\n")) { die("errore: scrivendo il char di fine mossa"); }
      fclose($partita);
      $ultimamossa = $mossa;
      if(false === ($partita = fopen($p,'r'))) {die("error: fopen");}
      if(0 != fseek($partita,-(strlen($mossa)+1),SEEK_END)) {die("error: fseek");}
      $mossa = fread($partita,strlen($mossa));
      if (false === $mossa) { die("errore mossa è falso"); }
      if (0 != strcmp($mossa,$ultimamossa)) {
        die("errore mossa scritta differisce dalla mossa riletta\n-$mossa\n-$ultimamossa");
      }
      fclose($partita);
      echo $mossa;
      $a = socket_create(AF_UNIX,SOCK_DGRAM,0);
      socket_sendto($a,$mossa,strlen($mossa),0,$internalSock);
      die(0);
    } break;
  case 'P': {
      $giocatore = substr($a,$l-1-4-1,1);
      $partita = file_get_contents($p);
      $debug = array_key_exists('debug',$_GET) ? $_GET['debug'] : 0;
  ?>

  <!DOCTYPE html>
  <html>
    <head>
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <style>
        html body {
          margin: 0px;
          padding: 0px;
          border: none;
          width:  98%;
          height: 98%;
        }
        canvas.tavola {
          position: absolute;
          top:0px;
          left:0px;
          border: solid 1px black;
          width: 420px;
          height: 420px;
        }
        div.tavola {
          border: solid 1px black;
          width: 420px;
          min-width: 420px;
          height: 420px;
          min-height: 420px;
        }
        select.giocatore {
          display: none;
        }
        body { display : flex; }
        div.d {
          height: 400px;
          display:flex;
          flex-direction : column;
          overflow : scroll;
        }
        @media (max-width: 768px) {
          body { flex-direction : column; }
        }
      </style>

    </head>
    <body>
  <script>
  const gUrl = window.location.search;
  const gPartita = gUrl[gUrl.length -1 -1];
  var   gColoreGiocatore = gUrl[gUrl.length -1 -1 -1];
  </script>
      <div class=tavola>
      <canvas class=tavola width=420 height=420 id=cScacchiera></canvas>
      <canvas class=tavola width=420 height=420 id=cPezzi     ></canvas>
      <canvas class=tavola width=420 height=420 id=c          ></canvas>
      </div>
      <form>
        <input type=text readonly name="mossa"   id=mossa pattern="[PTCADR][BN][A-Z][A-Z][A-Z][A-Z][A-Z]M" value="">
        <script>
          const mossaChrArr = [' ',' ',' ',' ',' ',' ',gPartita,'M'];
          mossa.value = mossaChrArr.join('');
          function setL(i,o){ mossaChrArr[i] = o.innerHTML; mossa.value = mossaChrArr.join(''); }
          function setN(i,n){ mossaChrArr[i] = String.fromCharCode(65 + n); mossa.value = mossaChrArr.join(''); }
        </script>
        <br>
        <label>Pezzo: </label>
        <button type=button onclick="setL(0,this);">P</button>
        <button type=button onclick="setL(0,this);">T</button>
        <button type=button onclick="setL(0,this);">C</button>
        <button type=button onclick="setL(0,this);">A</button>
        <button type=button onclick="setL(0,this);">D</button>
        <button type=button onclick="setL(0,this);">R</button>
        <br>
        <label>Colore: </label>
        <button type=button onclick="setL(1,this);">B</button>
        <button type=button onclick="setL(1,this);">N</button>
        <br>
        <label>da I:</label>
        <button type=button onclick="setN(2, 0);"> 0</button>
        <button type=button onclick="setN(2, 1);"> 1</button>
        <button type=button onclick="setN(2, 2);"> 2</button>
        <button type=button onclick="setN(2, 3);"> 3</button>
        <button type=button onclick="setN(2, 4);"> 4</button>
        <button type=button onclick="setN(2, 5);"> 5</button>
        <button type=button onclick="setN(2, 6);"> 6</button>
        <button type=button onclick="setN(2, 7);"> 7</button>
        <button type=button onclick="setN(2, 8);"> 8</button>
        <button type=button onclick="setN(2, 9);"> 9</button>
        <button type=button onclick="setN(2,10);">10</button>
        <br>
        <label>da J:</label>
        <button type=button onclick="setN(3, 0);"> 0</button>
        <button type=button onclick="setN(3, 1);"> 1</button>
        <button type=button onclick="setN(3, 2);"> 2</button>
        <button type=button onclick="setN(3, 3);"> 3</button>
        <button type=button onclick="setN(3, 4);"> 4</button>
        <button type=button onclick="setN(3, 5);"> 5</button>
        <button type=button onclick="setN(3, 6);"> 6</button>
        <button type=button onclick="setN(3, 7);"> 7</button>
        <button type=button onclick="setN(3, 8);"> 8</button>
        <button type=button onclick="setN(3, 9);"> 9</button>
        <button type=button onclick="setN(3,10);">10</button>
        <br>
        <label>a I: </label>
        <button type=button onclick="setN(4, 0);"> 0</button>
        <button type=button onclick="setN(4, 1);"> 1</button>
        <button type=button onclick="setN(4, 2);"> 2</button>
        <button type=button onclick="setN(4, 3);"> 3</button>
        <button type=button onclick="setN(4, 4);"> 4</button>
        <button type=button onclick="setN(4, 5);"> 5</button>
        <button type=button onclick="setN(4, 6);"> 6</button>
        <button type=button onclick="setN(4, 7);"> 7</button>
        <button type=button onclick="setN(4, 8);"> 8</button>
        <button type=button onclick="setN(4, 9);"> 9</button>
        <button type=button onclick="setN(4,10);">10</button>
        <br>
        <label>a J: </label>
        <button type=button onclick="setN(5, 0);"> 0</button>
        <button type=button onclick="setN(5, 1);"> 1</button>
        <button type=button onclick="setN(5, 2);"> 2</button>
        <button type=button onclick="setN(5, 3);"> 3</button>
        <button type=button onclick="setN(5, 4);"> 4</button>
        <button type=button onclick="setN(5, 5);"> 5</button>
        <button type=button onclick="setN(5, 6);"> 6</button>
        <button type=button onclick="setN(5, 7);"> 7</button>
        <button type=button onclick="setN(5, 8);"> 8</button>
        <button type=button onclick="setN(5, 9);"> 9</button>
        <button type=button onclick="setN(5,10);">10</button>
        <br>
        <input type=submit value='muovi'>
      </form>
      <textarea id=cronologia readonly></textarea>
  <div class=d id=menuDebug>
    <p><input type=checkbox id=gDebug>Linee di debug</p>
    <script>
      function debugMovimenti(b){
        const txt = b.value;
        gPezzi = {};
        gColoreGiocatore = 'B';
        eseguiMosse(txt);
        updateMovimenti();
        cCtx.clearRect(0,0,c.width,c.height);
        drawPezzi(gPezziCtx);
        var p0 = (txt.charCodeAt(4) - 65) << 4 | (txt.charCodeAt(5) - 65);
        const colore = gPezzi[p0].at(1) == gColoreGiocatore ? '#00ff00' : '#ff0000';
        for(const p of gMovimenti[p0]){
          coloraBordoEsagono(p,colore);
        }
      }
    </script>
    <button onclick='debugMovimenti(this)' value='TBAAFF-TNAAEB-RBAAJG-RNAAAG-'              >t: mov                     </button>
    <button onclick='debugMovimenti(this)' value='CBAAFF-TNAAEB-RBAAJG-RNAAAG-'              >c: mov                     </button>
    <button onclick='debugMovimenti(this)' value='ABAAFF-TNAAEB-RBAAJG-RNAAAG-'              >a: mov                     </button>
    <button onclick='debugMovimenti(this)' value='DBAAFF-TNAAEB-RBAAJG-RNAAAG-'              >d: mov                     </button>
    <button onclick='debugMovimenti(this)' value='RBAAFF-CNAAFA-TBAAJG-RNAAAG-'              >r: mov                     </button>
    <button onclick='debugMovimenti(this)' value='TBAAFF-TNAAFD-RBAAEF-RNAAAG-'              >t: cattura / alleato       </button>
    <button onclick='debugMovimenti(this)' value='CBAAFF-TNAAED-RBAAGC-RNAAAG-'              >c: cattura / alleato       </button>
    <button onclick='debugMovimenti(this)' value='ABAAFF-TNAAEE-RBAADG-RNAAAG-'              >a: cattura / alleato       </button>
    <button onclick='debugMovimenti(this)' value='DBAAFF-TNAAFD-RBAAEF-RNAAAG-'              >d: cattura / alleato       </button>
    <button onclick='debugMovimenti(this)' value='RBAAFF-TNAAEE-TBAADG-RNAAAG-'              >r: cattura / alleato       </button>
    <button onclick='debugMovimenti(this)' value='TNAAFF-TNAADF-TBAAFD-RBAAJG-RNAAAG-'       >t nemico: cattura / alleato</button>
    <button onclick='debugMovimenti(this)' value='CNAAFF-TBAAED-TNAAGC-RBAAJG-RNAAAG-'       >c nemico: cattura / alleato</button>
    <button onclick='debugMovimenti(this)' value='ANAAFF-TBAAEE-TNAAGD-RBAAJG-RNAAAG-'       >a nemico: cattura / alleato</button>
    <button onclick='debugMovimenti(this)' value='DNAAFF-TBAAFD-TNAAGD-RBAAJG-RNAAAG-'       >d nemico: cattura / alleato</button>
    <button onclick='debugMovimenti(this)' value='RNAAFF-TBAAEE-TNAAGD-RBAAJG-RNAAAG-'       >r nemico: cattura / alleato</button>
    <button onclick='debugMovimenti(this)' value='PNAAFF-RBAAJG-RNAAAG-'                     >p nemico: mov              </button>
    <button onclick='debugMovimenti(this)' value='PNAAFE-RBAAJG-RNAAAG-'                     >p nemico: mov init         </button>
    <button onclick='debugMovimenti(this)' value='PNAAFE-TNAAEF-CBAAGE-CBAAFF-RBAAJG-RNAAAG-'>p nemico: cattura / alleato</button>
    <button onclick='debugMovimenti(this)' value='RBAAFG-PNAAFE-CNAAEF-RNAAAG-'              >re vs pedone nemico        </button>
    <button onclick='debugMovimenti(this)' value='TBAAFF-TNAADF-RBAAHF-RNAAAG-'              >torre in difesa del re     </button>
    <button onclick='debugMovimenti(this)' value='TBAAFE-TNAADF-RBAAHF-RNAAAG-'              >torre deve difendere il re </button>
    <button onclick='debugMovimenti(this)' value='PBAAFF-RBAAJG-RNAAAG-'                     >p: mov                     </button>
    <button onclick='debugMovimenti(this)' value='PBAAFG-RBAAJG-RNAAAG-'                     >p: mov init                </button>
    <button onclick='debugMovimenti(this)' value='PBAAFG-CNAAEG-CBAAGF-CNAAFF-RBAAJG-RNAAAG-'>p: cattura alleato         </button>
    <button onclick='debugMovimenti(this)' value='PBAAFG-TNAAEG-RBAAJG-RNAAAG-'              >p in difesa del re         </button>
    <button onclick='debugMovimenti(this)' value='PBAAFG-TNAAEF-RBAAKF-RNAAAG-'              >p deve difendere il re     </button>
  </div>
      <script>

  const MOSSA_LEN = 'PBAAAA.'.length;
  const MOSSA_RGX = /[PTCADR][BN][A-Z][A-Z][A-Z][A-Z]/;
  var   gPollCount = 0;
  const gPosInizialiPedoni = {
    'N' : new Set([0x14,0x24,0x34,0x44,0x54,0x63,0x72,0x81,0x90])
  , 'B' : new Set([0x1a,0x29,0x38,0x47,0x56,0x66,0x76,0x86,0x96])
  };
  const gMovimentiPezzi = {
    'T' : [10, [-1,1,-16,16,-15,15]]
  , 'C' : [ 1, [-13,13,-18,18,-33,33,-47,47,-46,46,-29,29]]
  , 'A' : [10, [-17,17,-14,14,-31,31]]
  , 'D' : [10, [-1,1,-16,16,-15,15,-17,17,-14,14,-31,31]]
  , 'R' : [ 1, [-1,1,-16,16,-15,15,-17,17,-14,14,-31,31]]
  , 'P' : [ 1, [1,16,-15]]
  };
  const gPezziCtx = cPezzi.getContext("2d");
  const cCtx = c.getContext("2d");
  const gXpad =  20;
  const gYpad = -80;
  const gLatoEsagono    = 20;
  const gLatoEsagonoSin = gLatoEsagono * 1/2;
  const gLatoEsagonoCos = gLatoEsagono * Math.sqrt(3)/2;
  var gPezzi = {};
  var gMovimenti = {};
  var gPezziScaccanti = [];
  var gPezzoAttivo = 0;
  var gIdxRe = 0;

      function debugLine(b,a,colore = '#ff0000'){
        cCtx.lineWidth = 1;
        cCtx.strokeStyle = colore;
        cCtx.beginPath();
        if (Infinity != a) {
          cCtx.moveTo(0,b);
          cCtx.lineTo(c.width,a*(c.width)+b);
        } else {
          cCtx.moveTo(b,0);
          cCtx.lineTo(b,c.height);
        }
        cCtx.closePath();
        cCtx.stroke();
      }

      function eseguiMosse(mosse){
        var da = null;
        var a  = null;
        const len = mosse.length;
        for(var i = 0; i < len; i+=MOSSA_LEN){
          da  = ((mosse.charCodeAt(i+2) - 65) & 0xf) << 4;
          da |= ((mosse.charCodeAt(i+3) - 65) & 0xf);
          a   = ((mosse.charCodeAt(i+4) - 65) & 0xf) << 4;
          a  |= ((mosse.charCodeAt(i+5) - 65) & 0xf);
          delete gPezzi[da];
          gPezzi[a] = mosse.substr(i,2);
        }
      }

      function httpGet(url, callback){
        const a = new XMLHttpRequest();
        a.onreadystatechange = function (){
          if (this.readyState == 4 && this.status == 200) {
             callback(this.responseText, this);
          }
        };
        a.open("GET",url,true);
        a.send();
      }

      function posByIdx(pos){
        const i = ((pos >> 4) & 0xf);
        const j = (pos & 0xf);
        const x = gXpad + ((gLatoEsagono + gLatoEsagonoSin) * i);
        const y = gYpad + (gLatoEsagonoCos * 2 * j) + (gLatoEsagonoCos * i);
        return [x,y];
      }

      function idxByPos(x,y){
          const altezzaEsagono   = gLatoEsagonoCos * 2;
          const larghezzaEsagono = gLatoEsagonoSin + gLatoEsagono;
          const a0               = 1 / Math.sqrt(3);
          const bAscZero = 0 + gYpad - gLatoEsagonoCos + 6;
          const bAsc = y - (a0 * x);
  if(gDebug.checked) {
  debugLine(x,Infinity,'#00ff00');
  debugLine(gXpad,Infinity,'#0000ff');
  debugLine(bAscZero,a0);
  debugLine(bAsc,a0,'#00ff00');
  }
          const j0 = Math.floor((bAsc - bAscZero) / altezzaEsagono);
          const i0 = Math.floor((x - gXpad) / larghezzaEsagono);
  if(gDebug.checked) {
  const x0 = gXpad + i0 * larghezzaEsagono;
  debugLine(j0 * altezzaEsagono + bAscZero,a0);
  debugLine((j0 + 1) * altezzaEsagono + bAscZero,a0);
  debugLine(x0,Infinity,'#0000ff');
  debugLine(x0 + larghezzaEsagono,Infinity,'#0000ff');
  }
          let v = verticiEsagono(((i0 & 0xf) << 4) | (j0 & 0xf));
          v = v.map(o => ({x:o.x-gXpad,y:o.y-gYpad}));
          v = v[3];
          const a1 = Math.sqrt(3);
          const bAsc0 = v.y - (+a1 * (v.x + gXpad)) + gYpad;
          const bDis0 = v.y - (-a1 * (v.x + gXpad)) + gYpad;
          const yAsc =  a1 * x + bAsc0;
          const yDis = -a1 * x + bDis0;
          const yCst = gYpad + v.y;
  if(gDebug.checked) {
  debugLine(yAsc,0,'#00ff00');
  debugLine(yDis,0,'#0000ff');
  debugLine(yCst,0,);
  }
          var i = i0 + (yDis < y && y < yAsc);
          var j = j0 + (yCst < y && yAsc < y);
          if (i != (i & 0xf)) { i = 0xf; }
          if (j != (j & 0xf)) { j = 0xf; }
          const p = ((i & 0xf) << 4) | (j & 0xf);
          return p;
      }

      function verticiEsagono(p){
        const [x,y] = posByIdx(p);
        const vertici =
          [ {x : x                              , y : y                      }
          , {x : x+gLatoEsagono                 , y : y                      }
          , {x : x+gLatoEsagono+gLatoEsagonoSin , y : y + gLatoEsagonoCos    }
          , {x : x+gLatoEsagono                 , y : y + gLatoEsagonoCos * 2}
          , {x : x                              , y : y + gLatoEsagonoCos * 2}
          , {x : x-gLatoEsagonoSin              , y : y + gLatoEsagonoCos    }
          , {x : x                              , y : y                      }
          ];
        return vertici;
      }

      function coloraBordoEsagono(pos,colore){
        if ('N' == gColoreGiocatore) { pos = 0xaa - pos; }
        const v = verticiEsagono(pos);
        cCtx.lineWidth = 6;
        cCtx.strokeStyle = colore;
        cCtx.beginPath();
        cCtx.moveTo(v[0].x,v[0].y);
        v.forEach(function (o) { cCtx.lineTo(o.x,o.y); });
        cCtx.closePath();
        cCtx.stroke();
      }

      function cellaFuoriTavola(pos){
          const i = (pos & 0xf0) >> 4;
          const j = pos & 0xf;
          const s = i+j;
          return !((5 <= s && s <= 15) && (0 <= i && i <= 10) && (0 <= j && j <= 10));
      }

      function selezionaPezzo(p1){
        const cr = cronologia.value;
        const u = cr.length - 7;
        if (cr.at(u + 1) == gColoreGiocatore) {
          return;
        }
        if(cellaFuoriTavola(p1)){
          return;
        }
        if (0 != gPezzoAttivo){
          const p0 = gPezzoAttivo;
          if (gMovimenti[p0].includes(p1)) {
            const mossaBytes = new Uint8Array(4);
            mossaBytes[0] = 65 + ((p0 & 0xf0) >> 4);
            mossaBytes[1] = 65 +  (p0 & 0xf);
            mossaBytes[2] = 65 + ((p1 & 0xf0) >> 4);
            mossaBytes[3] = 65 +  (p1 & 0xf);
            const decoder = new TextDecoder('ascii');
            const mossa = gPezzi[p0] + decoder.decode(mossaBytes);;
            const url = "/?a="+mossa+gPartita+"M";
            httpGet(url, function aggiornaUltimaMossa(mossaSrv) {
              if (mossaSrv == mossa) {
                gPezzoAttivo = 0;
                cronologia.value += mossa;
                cronologia.value += "\n";
                eseguiMosse(mossa);
                updateMovimenti();
                drawPezzi(gPezziCtx);
                coloraUltimaMossa();
              } else {
                console.log("Errore ultima mossa",mossa,mossaSrv);
              }
            });
            return;
          }
        }
        gPezzoAttivo = 0;
        if(!(p1 in gPezzi)) {
          return;
        }
        if(gColoreGiocatore != gPezzi[p1].at(1)){
          return;
        }
        gPezzoAttivo = p1;
        coloraBordoEsagono(p1,'#6688ccff');
        const movimenti = gMovimenti[p1];
        for(const pos of movimenti){
          const cellaOccupata = pos in gPezzi;
          if (cellaFuoriTavola(pos) || (cellaOccupata && gPezzi[pos].at(1) == gColoreGiocatore)) {
            continue;
          }
          if(cellaOccupata){
            coloraBordoEsagono(pos, '#cc0000');
          }else{
            coloraBordoEsagono(pos, '#cccc00');
          }
        }
      }

      function drawCellaEsagonoByIdx(ctx,pos){
        const colori = ['#000000ff','#aaaaaaff','#ffffffff'];
        const [i,j] = [(pos & 0xf0) >> 4,pos & 0xf];
        if ('N' == gColoreGiocatore) { pos = 0xaa - pos; }
        const v = verticiEsagono(pos);
        const cIdx = (((1 + (-j + i))%3)+3)%3;
        const dimensioneTesto = (10);
  //
        ctx.beginPath();
        ctx.lineWidth = 1;
        ctx.strokeStyle = '#000000ff';
        ctx.fillStyle = colori[cIdx];
        ctx.moveTo(v[0].x,v[0].y);
        v.forEach(function (o) { ctx.lineTo(o.x,o.y); });
        ctx.closePath();
        ctx.fill();
        ctx.stroke();
  //
        ctx.beginPath();
        ctx.lineWidth = 1;
        ctx.font = '' + dimensioneTesto + 'px monospace';
        ctx.strokeStyle = colori[0 == cIdx ? 2 : 0];
        ctx.fillText  (i + "." + j, v[0].x, v[0].y + dimensioneTesto);
        ctx.strokeText(i + "." + j, v[0].x, v[0].y + dimensioneTesto);
        ctx.fill();
        ctx.stroke();
      }

      function drawScacchiera(ctx){
        ctx.clearRect(0,0,c.width,c.height);
        for(var i = 0; i <  6; i++){ drawCellaEsagonoByIdx(ctx, (((5 + i) & 0xf) << 4) | 0); }
        for(var i = 0; i <  7; i++){ drawCellaEsagonoByIdx(ctx, (((4 + i) & 0xf) << 4) | 1); }
        for(var i = 0; i <  8; i++){ drawCellaEsagonoByIdx(ctx, (((3 + i) & 0xf) << 4) | 2); }
        for(var i = 0; i <  9; i++){ drawCellaEsagonoByIdx(ctx, (((2 + i) & 0xf) << 4) | 3); }
        for(var i = 0; i < 10; i++){ drawCellaEsagonoByIdx(ctx, (((1 + i) & 0xf) << 4) | 4); }
        for(var i = 0; i < 11; i++){ drawCellaEsagonoByIdx(ctx, (((0 + i) & 0xf) << 4) | 5); }
        for(var i = 0; i < 10; i++){ drawCellaEsagonoByIdx(ctx, (((0 + i) & 0xf) << 4) | 6); }
        for(var i = 0; i <  9; i++){ drawCellaEsagonoByIdx(ctx, (((0 + i) & 0xf) << 4) | 7); }
        for(var i = 0; i <  8; i++){ drawCellaEsagonoByIdx(ctx, (((0 + i) & 0xf) << 4) | 8); }
        for(var i = 0; i <  7; i++){ drawCellaEsagonoByIdx(ctx, (((0 + i) & 0xf) << 4) | 9); }
        for(var i = 0; i <  6; i++){ drawCellaEsagonoByIdx(ctx, (((0 + i) & 0xf) << 4) |10); }
      }

      function coloraUltimaMossa(){
        cCtx.clearRect(0,0,c.width,c.height);
        const nPezzi = 36;
        const cr = cronologia.value;
        const u = cr.length - 7;
        if(!((MOSSA_LEN * nPezzi) < cr.length)) {
          return;
        }
        var p0 = 0;
        p0 |= ((cr.charCodeAt(u + 2) - 65) & 0xf) << 4;
        p0 |= ((cr.charCodeAt(u + 3) - 65) & 0xf);
        var p1 = 0;
        p1 |= ((cr.charCodeAt(u + 4) - 65) & 0xf) << 4;
        p1 |= ((cr.charCodeAt(u + 5) - 65) & 0xf);
        coloraBordoEsagono(p0,'#00ff00');
        coloraBordoEsagono(p1,'#00ff00');
        for(p of gPezziScaccanti) { coloraBordoEsagono(p1,'#ff0000'); }
        if (0 < gPezziScaccanti.length) {coloraBordoEsagono(gIdxRe,'#ff0000');}
      }

      function drawPezzi(ctx){
        ctx.clearRect(0,0,c.width,c.height);
        const dimensioneTesto = (gLatoEsagonoCos * 2);
        const gPezziColori = ['#222222','#dddddd'];
        const r = dimensioneTesto / 2;
        ctx.font = 'bold ' + dimensioneTesto + 'px monospace';
        ctx.lineWidth   = 2;
        for(pos of Object.keys(gPezzi)){
          const [n,c] = gPezzi[pos];
          const b = 'B' == c;
          if ('N' == gColoreGiocatore) { pos = 0xaa - pos; }
          const [x,y] = posByIdx(pos);
          ctx.beginPath();
          ctx.fillStyle = gPezziColori[ b&1];
          ctx.arc(x + gLatoEsagonoSin, y + gLatoEsagonoCos, gLatoEsagono*0.8, 0, 2 * Math.PI, false);
          ctx.fill();
          ctx.beginPath();
          ctx.fillStyle = gPezziColori[!b&1];
          ctx.fillText(n, x, y + dimensioneTesto -6);
          ctx.fill();
        }
      }

      function updateMovimenti(){
        gScacco = null;
        gMovimenti = {};
        gPezziDifesi = [];
        gPezziScaccabili = {};
        gPezziScaccanti = [];
        gIdxRe = 0;
        var pezziScaccanti = [];
        var movimentiNemici = new Set();
        var pezziADifesa = {};
        var pezziNemici  = [];
        var pedoniNemici = [];
        var pezziAlleati = [];
        var pedoniAlleati = [];
        var pDirezione = 0;
        for(var pos of Object.keys(gPezzi)){
          var pezzo = gPezzi[pos];
          if(pezzo.at(1) != gColoreGiocatore){
            if ('P' == pezzo.at(0)) {
              pedoniNemici.push(pos);
            } else {
              pezziNemici.push(pos);
            }
          }else if('R' == pezzo.at(0)){
            gIdxRe = pos;
          }else{
            if('P' == pezzo.at(0)){
              pedoniAlleati.push(pos);
            } else {
              pezziAlleati.push(pos);
            }
          }
        }
        pDirezione = ('N' == gColoreGiocatore) ? -1 : 1;
        for(var p0 of pedoniNemici) {
          const psl = pezziScaccanti.length;
          p0 = Number(p0);
          var possibili = [];
          var p1 = 0;
          p1 = p0 +  1 * pDirezione; if (!cellaFuoriTavola(p1) && !(p1 in gPezzi)) {
          possibili.push(p1);
          p1 = p0 +  2 * pDirezione; if (gPosInizialiPedoni[gPezzi[p0].at(1)].has(p0) && !cellaFuoriTavola(p1) && !(p1 in gPezzi)) { possibili.push(p1); }
          }
          p1 = p0 + 16 * pDirezione; if (p1 == gIdxRe) { pezziScaccanti.push([p1]); } if (!cellaFuoriTavola(p1)) { possibili.push(p1); movimentiNemici.add(p1); }
          p1 = p0 - 15 * pDirezione; if (p1 == gIdxRe) { pezziScaccanti.push([p1]); } if (!cellaFuoriTavola(p1)) { possibili.push(p1); movimentiNemici.add(p1); }
          gMovimenti[p0] = possibili;
          if (psl < pezziScaccanti.length) { gPezziScaccanti.push(p0); }
        }
        for(var p0 of pezziNemici) {
          const psl = pezziScaccanti.length;
          p0 = Number(p0);
          var [lim,mov] = gMovimentiPezzi[gPezzi[p0].at(0)];
          var possibili = [];
          for(const m of mov){
            var direzione = [];
            direzione.push(p0);
            for(var k = 1; k <= lim; k++){
              var p1 = p0 + m * k;
              if (cellaFuoriTavola(p1)) { break; }
              if (p1 in gPezzi) {
                if (p1 == gIdxRe) { pezziScaccanti.push(direzione); break; }
                possibili.push(p1);
                if (gPezzi[p1].at(1) != gPezzi[p0].at(1)) {
                  for (var k1 = k+1; k1 <= lim; k1++){
                    var p2 = p0 + m * k1;
                    if(cellaFuoriTavola(p2)) { break; }
                    if(p2 in gPezzi) {
                      if(p2 == gIdxRe) {
                        pezziADifesa[p1] = direzione;
                      }
                      break;
                    }
                    direzione.push(p2);
                  }
                }
                break;
              }
              movimentiNemici.add(p1);
              possibili.push(p1);
              direzione.push(p1);
            }
          }
          gMovimenti[p0] = possibili;
          if (psl < pezziScaccanti.length) { gPezziScaccanti.push(p0); }
        }
        pDirezione = ('B' == gColoreGiocatore) ? 1 : -1;
if (2 > gPezziScaccanti.length) {
        for(var p0 of pedoniAlleati) {
          p0 = Number(p0);
          var possibili = [];
          var p1 = 0;
          const c = gPezzi[p0].at(1);
          p1 = p0 -  1 * pDirezione; if (!cellaFuoriTavola(p1) && !(p1 in gPezzi)) {
          possibili.push(p1);
          p1 = p0 -  2 * pDirezione; if (gPosInizialiPedoni[c].has(p0) && !cellaFuoriTavola(p1) && !(p1 in gPezzi)) { possibili.push(p1); }
          }
          p1 = p0 + 15 * pDirezione; if (p1 in gPezzi && c != gPezzi[p1].at(1)) { possibili.push(p1); }
          p1 = p0 - 16 * pDirezione; if (p1 in gPezzi && c != gPezzi[p1].at(1)) { possibili.push(p1); }
          if (p0 in pezziADifesa) { possibili = possibili.filter(p1 => pezziADifesa[p0].includes(p1)); }
          if (1 == pezziScaccanti.length) { possibili = possibili.filter(p1 => pezziScaccanti[0].includes(p1)); }
          gMovimenti[p0] = possibili;
        }
        for(var p0 of pezziAlleati){
          if ( 1 < pezziScaccanti.length ) { break; }
          p0 = Number(p0);
          var possibili = [];
          var [lim,mov] = gMovimentiPezzi[gPezzi[p0].at(0)];
          for(const m of mov){
            for(var k = 1; k <= lim; k++){
              var p1 = p0 + m * k;
              if (cellaFuoriTavola(p1)) { break; }
              if (p0 in pezziADifesa && !pezziADifesa[p0].includes(p1)) { continue; }
              if (1 == pezziScaccanti.length && !pezziScaccanti[0].includes(p1)) { continue; }
              if (p1 in gPezzi) {
                if (gPezzi[p1].at(1) != gPezzi[p0].at(1)) { possibili.push(p1); }
                break;
              }
              possibili.push(p1);
            }
          }
          gMovimenti[p0] = possibili;
        }
}
        {
          var p0 = gIdxRe;
          p0 = Number(p0);
          var possibili = [];
          var [lim,mov] = gMovimentiPezzi[gPezzi[p0].at(0)];
          for(const m of mov){
            for(var k = 1; k <= lim; k++){
              var p1 = p0 + m * k;
              if (cellaFuoriTavola(p1)) { break; }
              if (movimentiNemici.has(p1)) { break; }
              if (p1 in gPezzi) {
                if (gPezzi[p1].at(1) != gPezzi[p0].at(1)) { possibili.push(p1); }
                break;
              }
              possibili.push(p1);
            }
          }
          gMovimenti[p0] = possibili;
        }
      }

      // =============================================
      // LISTENERS
      // =============================================

  c.addEventListener('mousedown', function(e) {
      const rect = this.getBoundingClientRect();
      const x = (event.clientX - rect.left) * c.width  / rect.width ;
      const y = (event.clientY - rect.top ) * c.height / rect.height;
      coloraUltimaMossa();
      var pos = idxByPos(x,y);
      if ('N' == gColoreGiocatore) { pos = 0xaa - pos; }
      selezionaPezzo(pos);
  })

      // =============================================
      // MAIN
      // =============================================

      drawScacchiera(cScacchiera.getContext("2d"));
      drawPezzi(gPezziCtx);

      httpGet("/?a="+gPartita+"L",function (txt) {
        cronologia.value = txt;
        eseguiMosse(txt);
        updateMovimenti();
        drawPezzi(gPezziCtx);
        coloraUltimaMossa();
      });

var gWs = new WebSocket("http://<?php echo $webSocketAddr; ?>");
  gWs.onmessage = function (e) {
    const ultimaMossa = e.data;
    if (6 != ultimaMossa.length) {
      alert('errore websocket: '+ultimaMossa);
      return;
    }
    cronologia.value += ultimaMossa;
    cronologia.value += "\n";
    eseguiMosse(ultimaMossa);
    updateMovimenti();
    drawPezzi(gPezziCtx);
    coloraUltimaMossa();
  };

      </script>
  <?php

    } break;
  }
