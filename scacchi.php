<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

$api = php_sapi_name();
if('cli-server' !== $api){
      $file = __FILE__;
      $file = escapeshellarg($file);
      shell_exec("php --server localhost:8888 $file");
      die();
};

$outdir = __DIR__ . '/.partite/';
$b_outdir_exists = file_exists($outdir);
$b_outdir_isdir  = is_dir($outdir);
if ($b_outdir_exists && !$b_outdir_isdir) {
  die("outdir esiste e non è una cartella :: $outdir\n");
}
if (!$b_outdir_exists && mkdir($outdir,0700)) {
  die("errore nella creazione di outdir :: $outdir\n");
}
chdir($outdir);

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
  die();
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
  die();
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
    $mossa = substr($a,$l-1-4-6,6);
    $is_mossa_valida = preg_match('/[PTCADR][BN][A-Z][A-Z][A-Z][A-Z]/',$mossa);
    if (1 != $is_mossa_valida) { die('ERRORE: MOSSA INVALIDA'); }
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
    echo $mossa;
    fclose($partita);
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
      g.pezzi = {};
      gColoreGiocatore = 'B';
      eseguiMosse(txt);
      updateMovimenti();
      cCtx.clearRect(0,0,c.width,c.height);
      drawPezzi(gPezziCtx);
      var p0 = (txt.charCodeAt(4) - 65) << 4 | (txt.charCodeAt(5) - 65);
      const colore = g.pezzi[p0].at(1) == gColoreGiocatore ? '#00ff00' : '#ff0000';
      for(const p of g.movimenti[p0]){
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

    function debugLine(b,a,colore = '#ff0000'){
      g.ctx.lineWidth = 1;
      g.ctx.strokeStyle = colore;
      g.ctx.beginPath();
      if (Infinity != a) {
        g.ctx.moveTo(0,b);
        g.ctx.lineTo(c.width,a*(c.width)+b);
      } else {
        g.ctx.moveTo(b,0);
        g.ctx.lineTo(b,c.height);
      }
      g.ctx.closePath();
      g.ctx.stroke();
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
        delete g.pezzi[da];
        g.pezzi[a] = mosse.substr(i,2);
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

    function pollUltimaMossa () {
      gPollCount = 0;
      setTimeout(function loop() {
        httpGet("?a="+gPartita+"U",function (ultimaMossa){
          gPollCount += 1;
          const delay = 32 - Math.clz32(gPollCount);
          if (gColoreGiocatore == ultimaMossa.at(1)) {
            setTimeout(loop,1000*delay);
          } else {
            cronologia.value += ultimaMossa;
            cronologia.value += "\n";
            eseguiMosse(ultimaMossa);
            updateMovimenti();
            drawPezzi(gPezziCtx);
            coloraUltimaMossa();
          }
        });
      },1000);
    }

    const g = function init(){
      var g = {};
      g.debug = 1;
      g.c    = c;
      g.ctx  = c.getContext("2d");
      g.xpad =  20;
      g.ypad = -80;
      g.latoEsagono    = 20;
      g.latoEsagonoSin = g.latoEsagono * 1/2;
      g.latoEsagonoCos = g.latoEsagono * Math.sqrt(3)/2;
      g.pezzi = {};
      g.movimenti = {};
      g.pezzoAttivo = 0;
      httpGet("/?a="+gPartita+"L",function (txt) {
        cronologia.value = txt;
        eseguiMosse(txt);
        updateMovimenti();
        drawPezzi(gPezziCtx);
        const cr = cronologia.value;
        const u = cr.length - 7;
        coloraUltimaMossa();
        if (cr.at(u + 1) == gColoreGiocatore) {
          pollUltimaMossa();
        }
      });
      return g;
    }();

    function posByIdx(pos){
      const i = ((pos >> 4) & 0xf);
      const j = (pos & 0xf);
      const x = g.xpad + ((g.latoEsagono + g.latoEsagonoSin) * i);
      const y = g.ypad + (g.latoEsagonoCos * 2 * j) + (g.latoEsagonoCos * i);
      return [x,y];
    }

    function idxByPos(x,y){
        const altezzaEsagono   = g.latoEsagonoCos * 2;
        const larghezzaEsagono = g.latoEsagonoSin + g.latoEsagono;
        const a0               = 1 / Math.sqrt(3);
        const bAscZero = 0 + g.ypad - g.latoEsagonoCos + 6;
        const bAsc = y - (a0 * x);
if(gDebug.checked) {
debugLine(x,Infinity,'#00ff00');
debugLine(g.xpad,Infinity,'#0000ff');
debugLine(bAscZero,a0);
debugLine(bAsc,a0,'#00ff00');
}
        const j0 = Math.floor((bAsc - bAscZero) / altezzaEsagono);
        const i0 = Math.floor((x - g.xpad) / larghezzaEsagono);
if(gDebug.checked) {
const x0 = g.xpad + i0 * larghezzaEsagono;
debugLine(j0 * altezzaEsagono + bAscZero,a0);
debugLine((j0 + 1) * altezzaEsagono + bAscZero,a0);
debugLine(x0,Infinity,'#0000ff');
debugLine(x0 + larghezzaEsagono,Infinity,'#0000ff');
}
        let v = verticiEsagono(((i0 & 0xf) << 4) | (j0 & 0xf));
        v = v.map(o => ({x:o.x-g.xpad,y:o.y-g.ypad}));
        v = v[3];
        const a1 = Math.sqrt(3);
        const bAsc0 = v.y - (+a1 * (v.x + g.xpad)) + g.ypad;
        const bDis0 = v.y - (-a1 * (v.x + g.xpad)) + g.ypad;
        const yAsc =  a1 * x + bAsc0;
        const yDis = -a1 * x + bDis0;
        const yCst = g.ypad + v.y;
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
        [ {x : x                                , y : y                       }
        , {x : x+g.latoEsagono                  , y : y                       }
        , {x : x+g.latoEsagono+g.latoEsagonoSin , y : y + g.latoEsagonoCos    }
        , {x : x+g.latoEsagono                  , y : y + g.latoEsagonoCos * 2}
        , {x : x                                , y : y + g.latoEsagonoCos * 2}
        , {x : x-g.latoEsagonoSin               , y : y + g.latoEsagonoCos    }
        , {x : x                                , y : y                       }
        ];
      return vertici;
    }

    function coloraBordoEsagono(pos,colore){
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

    function selezionaPezzo(x,y){
      const cr = cronologia.value;
      const u = cr.length - 7;
      if (cr.at(u + 1) == gColoreGiocatore) {
        return;
      }
      const p1 = idxByPos(x,y);
      if(cellaFuoriTavola(p1)){
        return;
      }
      if (0 != g.pezzoAttivo){
        const p0 = g.pezzoAttivo;
        if (g.movimenti[p0].includes(p1)) {
          const mossaBytes = new Uint8Array(4);
          mossaBytes[0] = 65 + ((p0 & 0xf0) >> 4);
          mossaBytes[1] = 65 +  (p0 & 0xf);
          mossaBytes[2] = 65 + ((p1 & 0xf0) >> 4);
          mossaBytes[3] = 65 +  (p1 & 0xf);
          const decoder = new TextDecoder('ascii');
          const mossa = g.pezzi[p0] + decoder.decode(mossaBytes);;
          const url = "/?a="+mossa+gPartita+"M";
          httpGet(url, function aggiornaUltimaMossa(mossaSrv) {
            if (mossaSrv == mossa) {
              g.pezzoAttivo = 0;
              cronologia.value += mossa;
              cronologia.value += "\n";
              eseguiMosse(mossa);
              updateMovimenti();
              drawPezzi(gPezziCtx);
              coloraUltimaMossa();
              pollUltimaMossa();
            } else {
              console.log("Errore ultima mossa",mossa,mossaSrv);
            }
          });
          return;
        }
      }
      g.pezzoAttivo = 0;
      if(!(p1 in g.pezzi)) {
        return;
      }
      if(gColoreGiocatore != g.pezzi[p1].at(1)){
        return;
      }
      g.pezzoAttivo = p1;
      coloraBordoEsagono(p1,'#6688ccff');
      const movimenti = g.movimenti[p1];
      for(const pos of movimenti){
        const cellaOccupata = pos in g.pezzi;
        if (cellaFuoriTavola(pos) || (cellaOccupata && g.pezzi[pos].at(1) == gColoreGiocatore)) {
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
      const colori = ['#000000ff','#888888ff','#ffffffff'];
      const v = verticiEsagono(pos);
      const [i,j] = [(pos & 0xf0) >> 4,pos & 0xf];
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
      ctx.strokeStyle = colori[(cIdx+1)%3];
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
    }

    function drawPezzi(ctx){
      ctx.clearRect(0,0,c.width,c.height);
      const dimensioneTesto = (g.latoEsagonoCos * 2);
      const allineamentoX = 0;
      const allineamentoY = -8;
      for(pos of Object.keys(g.pezzi)){
        const [n,c] = g.pezzi[pos];
        const [x,y] = posByIdx(pos);
        ctx.font = 'bold ' + dimensioneTesto + 'px monospace';
        if ('B' == c) {
          // BIANCO
          ctx.lineWidth   = 2;
          ctx.fillStyle   = '#dddddd';
          ctx.strokeStyle = '#222222';
        } else {
          // NERO
          ctx.lineWidth   = 2;
          ctx.fillStyle   = '#222222';
          ctx.strokeStyle = '#dddddd';
        }
        ctx.beginPath();
        ctx.fillText  (n, x + allineamentoX, y + dimensioneTesto + allineamentoY);
        ctx.strokeText(n, x + allineamentoX, y + dimensioneTesto + allineamentoY);
        ctx.fill();
        ctx.stroke();
      }
    }

    function updateMovimenti(){
      g.scacco = null;
      g.movimenti = {};
      g.pezziDifesi = [];
      g.pezziScaccabili = {};
      var movimentiNemici = new Set();
      var pezziScaccanti = [];
      var pezziADifesa = {};
      var pezziNemici  = [];
      var pedoniNemici = [];
      var pezziAlleati = [];
      var pedoniAlleati = [];
      var pDirezione = 0;
      var idxRe = 0;
      for(var pos of Object.keys(g.pezzi)){
        var pezzo = g.pezzi[pos];
        if(pezzo.at(1) != gColoreGiocatore){
          if ('P' == pezzo.at(0)) {
            pedoniNemici.push(pos);
          } else {
            pezziNemici.push(pos);
          }
        }else if('R' == pezzo.at(0)){
          idxRe = pos;
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
        p0 = Number(p0);
        var possibili = [];
        var p1 = 0;
        p1 = p0 +  1 * pDirezione; if (!cellaFuoriTavola(p1) && !(p1 in g.pezzi)) {
        possibili.push(p1);
        p1 = p0 +  2 * pDirezione; if (gPosInizialiPedoni[g.pezzi[p0].at(1)].has(p0) && !cellaFuoriTavola(p1) && !(p1 in g.pezzi)) { possibili.push(p1); }
        }
        p1 = p0 + 16 * pDirezione; if (p1 == idxRe) { pezziScaccanti.push([p1]); } if (!cellaFuoriTavola(p1)) { possibili.push(p1); movimentiNemici.add(p1); }
        p1 = p0 - 15 * pDirezione; if (p1 == idxRe) { pezziScaccanti.push([p1]); } if (!cellaFuoriTavola(p1)) { possibili.push(p1); movimentiNemici.add(p1); }
        g.movimenti[p0] = possibili;
      }
      for(var p0 of pezziNemici) {
        p0 = Number(p0);
        var [lim,mov] = gMovimentiPezzi[g.pezzi[p0].at(0)];
        var possibili = [];
        for(const m of mov){
          var direzione = [];
          direzione.push(p0);
          for(var k = 1; k <= lim; k++){
            var p1 = p0 + m * k;
            if (cellaFuoriTavola(p1)) { break; }
            if (p1 in g.pezzi) {
              if (p1 == idxRe) { pezziScaccanti.push(direzione); break; }
              possibili.push(p1);
              if (g.pezzi[p1].at(1) != g.pezzi[p0].at(1)) {
                for (var k1 = k+1; k1 <= lim; k1++){
                  var p2 = p0 + m * k1;
                  if(cellaFuoriTavola(p2)) { break; }
                  if(p2 in g.pezzi) {
                    if(p2 == idxRe) {
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
        g.movimenti[p0] = possibili;
      }
      pDirezione = ('B' == gColoreGiocatore) ? 1 : -1;
      for(var p0 of pedoniAlleati) {
        p0 = Number(p0);
        var possibili = [];
        var p1 = 0;
        const c = g.pezzi[p0].at(1);
        p1 = p0 -  1 * pDirezione; if (!cellaFuoriTavola(p1) && !(p1 in g.pezzi)) {
        possibili.push(p1);
        p1 = p0 -  2 * pDirezione; if (gPosInizialiPedoni[c].has(p0) && !cellaFuoriTavola(p1) && !(p1 in g.pezzi)) { possibili.push(p1); }
        }
        p1 = p0 + 15 * pDirezione; if (p1 in g.pezzi && c != g.pezzi[p1].at(1)) { possibili.push(p1); }
        p1 = p0 - 16 * pDirezione; if (p1 in g.pezzi && c != g.pezzi[p1].at(1)) { possibili.push(p1); }
        if (p0 in pezziADifesa) { possibili = possibili.filter(p1 => pezziADifesa[p0].includes(p1)); }
        if (1 == pezziScaccanti.length) { possibili = possibili.filter(p1 => pezziScaccanti[0].includes(p1)); }
        g.movimenti[p0] = possibili;
      }
      for(var p0 of pezziAlleati){
        if ( 1 < pezziScaccanti.length ) { break; }
        p0 = Number(p0);
        var possibili = [];
        var [lim,mov] = gMovimentiPezzi[g.pezzi[p0].at(0)];
        for(const m of mov){
          for(var k = 1; k <= lim; k++){
            var p1 = p0 + m * k;
            if (cellaFuoriTavola(p1)) { break; }
            if (p0 in pezziADifesa && !pezziADifesa[p0].includes(p1)) { continue; }
            if (1 == pezziScaccanti.length && !pezziScaccanti[0].includes(p1)) { continue; }
            if (p1 in g.pezzi) {
              if (g.pezzi[p1].at(1) != g.pezzi[p0].at(1)) { possibili.push(p1); }
              break;
            }
            possibili.push(p1);
          }
        }
        g.movimenti[p0] = possibili;
      }
      {
        var p0 = idxRe;
        p0 = Number(p0);
        var possibili = [];
        var [lim,mov] = gMovimentiPezzi[g.pezzi[p0].at(0)];
        for(const m of mov){
          for(var k = 1; k <= lim; k++){
            var p1 = p0 + m * k;
            if (cellaFuoriTavola(p1)) { break; }
            if (movimentiNemici.has(p1)) { break; }
            if (p1 in g.pezzi) {
              if (g.pezzi[p1].at(1) != g.pezzi[p0].at(1)) { possibili.push(p1); }
              break;
            }
            possibili.push(p1);
          }
        }
        g.movimenti[p0] = possibili;
      }
    }


    // =============================================
    // LISTENERS
    // =============================================

c.addEventListener('mousedown', function(e) {
    const rect = this.getBoundingClientRect();
    const x = (event.clientX - rect.left) * c.width  / rect.width ;
    const y = (event.clientY - rect.top ) * c.height / rect.height;
    const pos = idxByPos(x,y);
    coloraUltimaMossa();
    selezionaPezzo(x,y);
})

    drawScacchiera(cScacchiera.getContext("2d"));
    drawPezzi(gPezziCtx);

    // =============================================
    // MAIN
    // =============================================

    </script>
<?php

  } break;
}

