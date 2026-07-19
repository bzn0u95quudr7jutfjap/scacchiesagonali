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

if(!array_key_exists('method',$_GET)){
  $a_partite = '*';
  $a_partite = glob($a_partite);
  $a_partite = array_map(fn($a) => <<<eof
    <div class='wrap' >
      <h3>$a</h3>
      <div class='giocaCome'>
        <a href='?method=partita&partita=$a&g=B'>Gioca come bianco</a>
        <div> &lt;--&gt; </div>
        <a href='?method=partita&partita=$a&g=N'>Gioca come nero</a>
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
      <a href='?method=nuova_partita'>Crea una nuova partita</a>
      <h2> Partite attive </h2>
      {$a_partite}
    </body>
  </html>
  eof;
  echo $html;
  die();
}

$method = 'method';
$method = $_GET[$method];
switch($method){
case 'nuova_partita': {
    $condizioni_iniziali = <<<eof
      PBBKBK
      PBCJCJ
      PBDIDI
      PBEHEH
      PBFGFG
      PBGGGG
      PBHGHG
      PBIGIG
      PBJGJG
      PNBEBE
      PNCECE
      PNDEDE
      PNEEEE
      PNFEFE
      PNGDGD
      PNHCHC
      PNIBIB
      PNJAJA
      ABFKFK
      ABFJFJ
      ABFIFI
      TBCKCK
      TBIHIH
      CBDKDK
      CBHIHI
      DBEKEK
      RBGJGJ
      ANFAFA
      ANFBFB
      ANFCFC
      TNCDCD
      TNIAIA
      CNDCDC
      CNHAHA
      DNEBEB
      RNGAGA\n
      eof;
    $nuova_partita = '*';
    $nuova_partita = glob($nuova_partita);
    $nuova_partita = count($nuova_partita);
    $nuova_partita = sprintf('%04d',$nuova_partita);
    echo "$nuova_partita\n";
    $nuova_partita = file_put_contents($nuova_partita,$condizioni_iniziali);
    if(false === $nuova_partita){ die('ERRORE: SCRITTURA DI UNA NUOVA PARTITA'); }
    header('Location: ?');
    die();
  } break;
case 'muovi': {
    $mossa = $_GET['mossa'];
    $is_mossa_valida = preg_match('/[PTCADR][BN][A-Z][A-Z][A-Z][A-Z]/',$mossa);
    if (1 != $is_mossa_valida) { die('ERRORE: MOSSA INVALIDA'); }
    $partita_id = $_GET[$partita];
    $partita = fopen($partita_id,'a');
    if(false === $partita) { die("ERRORE: NELL'APERTURA DI $partita_id"); }
    if(false === fputs($partita,$mossa)) {}
    fclose($partita);
    $partita = fopen($partita_id,'r');
    $ultimamossa = $mossa;
    $ultimamossajson = json_encode($ultimamossa,JSON_FORCE_OBJECT);
    $mossa = get_ultima_mossa($partita);
    $mossajson = json_encode($mossa,JSON_FORCE_OBJECT);
    if (0 == strcmp($mossa,$ultima_mossa)) {
      die("errore mossa scritta differisce dalla mossa riletta\n-$mossajson\n-$ultimamossajson");
    }
    echo $mossa;
    fclose($partita);
  } break;
case 'leggi_mosse' :{
$partita = 'partita';
$partita = $_GET[$partita];
  echo file_get_contents($partita);
  break;
};
case 'ultima_mossa': {
    $partita = 'partita';
    if (!array_key_exists($partita,$_GET)) {
      die('errore nessuna partita specificata');
    }
    $partita = $_GET[$partita];
    $partita = fopen($partita,'r');
    if(false === $partita) {
      die("errore nell'apertura di $partita");
    }
    $mossa = get_ultima_mossa($partita);
    $mossa = json_encode($mossa,JSON_FORCE_OBJECT);
    echo $mossa;
    fclose($partita);
  } break;
case 'partita': {
    $giocatore = 'g';
    if(!array_key_exists($giocatore,$_GET)) {
?>
<!DOCTYPE html>
<html>
  <head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
  </head>
  <body>
    <a href='<?php echo $_SERVER['REQUEST_URI']; ?>&g=B'>Bianco</a>
    <a href='<?php echo $_SERVER['REQUEST_URI']; ?>&g=N'>Nero</a>
  </body>
</html>
<?php
      break;
    }
    $giocatore = $_GET[$giocatore];
    $partita = 'partita';
    $partita = $_GET[$partita];
    $partita_id = $partita;
    $partita = file_get_contents($partita);
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
      canvas {
        border: solid 1px black;
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
    <select class=giocatore id='htmlGiocatore'>
      <option value='s'>Spettatore</option>
      <option value='B'>Bianco</option>
      <option value='N'>Nero</option>
    </select>
    <canvas id=c></canvas>
    <form>
      <input name=mossa type=text id=mossa pattern="[PTCADR][BN][A-Z][A-Z][A-Z][A-Z]" value="">
      <script>
        const mossaChrArr = [' ',' ',' ',' ',' ',' '];
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
<div class=d>
<?php
foreach(
[ ['TBAAFF-TNAAEB-RBAAJG-', 't: mov']
, ['CBAAFF-TNAAEB-RBAAJG-', 'c: mov']
, ['ABAAFF-TNAAEB-RBAAJG-', 'a: mov']
, ['DBAAFF-TNAAEB-RBAAJG-', 'd: mov']
, ['RBAAFF-CNAAFA-TBAAJG-', 'r: mov']
, ['TBAAFF-TNAAFD-RBAAEF-', 't: cattura / alleato']
, ['CBAAFF-TNAAED-RBAAGC-', 'c: cattura / alleato']
, ['ABAAFF-TNAAEE-RBAADG-', 'a: cattura / alleato']
, ['DBAAFF-TNAAFD-RBAAEF-', 'd: cattura / alleato']
, ['RBAAFF-TNAAEE-TBAADG-', 'r: cattura / alleato']
, ['TNAAFF-TNAADF-TBAAFD-RBAAJG-', 't nemico: cattura / alleato']
, ['CNAAFF-TBAAED-TNAAGC-RBAAJG-', 'c nemico: cattura / alleato']
, ['ANAAFF-TBAAEE-TNAAGD-RBAAJG-', 'a nemico: cattura / alleato']
, ['DNAAFF-TBAAFD-TNAAGD-RBAAJG-', 'd nemico: cattura / alleato']
, ['RNAAFF-TBAAEE-TNAAGD-RBAAJG-', 'r nemico: cattura / alleato']
, ['PNAAFF-RBAAJG-', 'p nemico: mov']
, ['PNAAFE-RBAAJG-', 'p nemico: mov init']
, ['PNAAFE-TNAAEF-CBAAGE-CBAAFF-RBAAJG-', 'p nemico: cattura / alleato']
, ['RBAAFG-PNAAFE-CNAAEF-', 're vs pedone nemico']
, ['TBAAFF-TNAADF-RBAAHF-', 'torre in difesa del re']
, ['TBAAFE-TNAADF-RBAAHF-', 'torre deve difendere il re']
, ['PBAAFF-RBAAJG-', 'p: mov']
, ['PBAAFG-RBAAJG-', 'p: mov init']
, ['PBAAFG-CNAAEG-CBAAGF-CNAAFF-RBAAJG-', 'p: cattura alleato']
, ['PBAAFG-TNAAEG-RBAAJG-', 'p in difesa del re']
, ['PBAAFG-TNAAEF-RBAAKF-', 'p deve difendere il re']
] as $a){
[$m,$n] = $a;
echo "<a href='?method=partita&partita=$partita_id&g=B&debug=3&t=$m'>$n</a>";
}
?>
</div>
    <script>

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

    const MOSSA_LEN = 'PBAAAA.'.length;
    const MOSSA_RGX = /[PTCADR][BN][A-Z][A-Z][A-Z][A-Z]/;

    function ultimaMossa(){
      return cronologia.innerHTML.substr(-MOSSA_LEN,MOSSA_LEN);
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

    const g = function init(){
      const altezzaCanvas = window.innerHeight;
      const larghezzaCanvas = window.innerWidth;
      const latoCanvas = (altezzaCanvas < larghezzaCanvas ? altezzaCanvas : larghezzaCanvas) - 24;
      // c.style.width  = latoCanvas + 'px';
      // c.style.height = latoCanvas + 'px';
      c.height = latoCanvas;
      c.width  = latoCanvas;
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
      g.giocatore = htmlGiocatore;
      g.giocatore.value = "<?php echo $giocatore; ?>";
      httpGet("/?method=leggi_mosse&partita=<?php echo $partita_id; ?>",function (txt) {
<?php if (2 == $debug) { ?>
        drawScacchiera();
        for (var i = 0; i < 12; i++) {
          for (var j = 0; j < 12; j++) {
            const p = (i & 0xf) << 4 | (j & 0xf);
            coloraBordoEsagono(p,cellaFuoriTavola(p) ? '#ff0000' : '#00ff00');
          }
        }
<?php } else if (3 == $debug) { ?>
txt = <?php echo json_encode($_GET['t']); ?>;
        cronologia.innerHTML = txt;
        eseguiMosse(txt);
        drawScacchiera();
        drawPezzi();
        updateMovimenti();
        var p0 = (txt.charCodeAt(4) - 65) << 4 | (txt.charCodeAt(5) - 65);
        const colore = g.pezzi[p0].at(1) == g.giocatore.value ? '#00ff00' : '#ff0000';
        for(const p of g.movimenti[p0]){
          coloraBordoEsagono(p,colore);
        }
<?php } else { ?>
        cronologia.innerHTML = txt;
        eseguiMosse(txt);
        drawScacchiera();
        drawPezzi();
        updateMovimenti();
<?php } ?>
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
<?php if(1 == $debug) { ?>
        debugLine(x,Infinity,'#00ff00');
        debugLine(g.xpad,Infinity,'#0000ff');
        debugLine(bAscZero,a0);
        debugLine(bAsc,a0,'#00ff00');
<?php } ?>
        const j0 = Math.floor((bAsc - bAscZero) / altezzaEsagono);
        const i0 = Math.floor((x - g.xpad) / larghezzaEsagono);
<?php if(1 == $debug) { ?>
        const x0 = g.xpad + i0 * larghezzaEsagono;
        debugLine(j0 * altezzaEsagono + bAscZero,a0);
        debugLine((j0 + 1) * altezzaEsagono + bAscZero,a0);
        debugLine(x0,Infinity,'#0000ff');
        debugLine(x0 + larghezzaEsagono,Infinity,'#0000ff');
<?php } ?>
        let v = verticiEsagono(((i0 & 0xf) << 4) | (j0 & 0xf));
        v = v.map(o => ({x:o.x-g.xpad,y:o.y-g.ypad}));
        v = v[3];
        const a1 = Math.sqrt(3);
        const bAsc0 = v.y - (+a1 * (v.x + g.xpad)) + g.ypad;
        const bDis0 = v.y - (-a1 * (v.x + g.xpad)) + g.ypad;
        const yAsc =  a1 * x + bAsc0;
        const yDis = -a1 * x + bDis0;
        const yCst = g.ypad + v.y;
<?php if(1 == $debug) { ?>
        debugLine(yAsc,0,'#00ff00');
        debugLine(yDis,0,'#0000ff');
        debugLine(yCst,0,);
<?php } ?>
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
      g.ctx.lineWidth = 6;
      g.ctx.strokeStyle = colore;
      g.ctx.beginPath();
      g.ctx.moveTo(v[0].x,v[0].y);
      v.forEach(function (o) { g.ctx.lineTo(o.x,o.y); });
      g.ctx.closePath();
      g.ctx.stroke();
    }

    function cellaFuoriTavola(pos){
        const i = (pos & 0xf0) >> 4;
        const j = pos & 0xf;
        const s = i+j;
        return !((5 <= s && s <= 15) && (0 <= i && i <= 10) && (0 <= j && j <= 10));
    }

    function aggiornaUltimaMossa(){
      if (undefined == this.i) { this.i = 0; };
      const ultimaMossaStr = ultimaMossa();
      const gUltimaMossaStr = JSON.stringify(g.ultimaMossa);
      if (gUltimaMossaStr == ultimaMossaStr) {
        this.i++;
        const i = this.i;
        setTimeout(aggiornaUltimaMossa,1000 * (1 + (2<i) + (2*(4<i)) + (4*(8<i))));
        return;
      }
      this.i = 0;
      g.ultimaMossa = ultimaMossaStr;
      const {da_i:i0,da_j:j0,a_i:i1,a_j:j1,colore:c,nome:n} = g.ultimaMossa;
      muoviPezzo(i0,j0,i1,j1,c,n,g);
      updateMovimenti();
      drawScacchiera();
      drawPezzi();
    }

    function selezionaPezzo(x,y){
      const ultimaMossaStr = ultimaMossa();
      if (ultimaMossaStr.at(1) == g.giocatore.value) {
        return;
      }
      const pos = idxByPos(x,y);
      if(cellaFuoriTavola(pos)){
        return;
      }
      if (g.pezzoAttivo in g.pezzi){
        if (g.movimenti[g.pezzoAttivo].includes(pos)) {
          const pezzo = g.pezzi[g.pezzoAttivo];
          const url = "/?method=muovi&partita=<?php echo $partita_id; ?>"
            + "&colore=" + pezzo.colore + "&nome=" + pezzo.nome
            + "&da_i=" + i0 + "&da_j=" + j0 + "&a_i=" + i + "&a_j=" + j;
          var xhttp = new XMLHttpRequest();
          xhttp.open("GET", url, false);
          xhttp.send();
          if (200 != xhttp.status) { alert("ERRORE NEL FETCHING DELL'ULTIMA MOSSA"); throw 0; }
          const ultimaMossaScrittaStr = xhttp.responseText;
          g.ultimaMossa = JSON.parse(ultimaMossaScrittaStr);
          if(1){
          const {nome:n, colore:c, da_i:i0, da_j:j0, a_i:i1, a_j:j1} = g.ultimaMossa;
          if (!(i1 in g.pezzi)) { g.pezzi[i1]={}; }
          g.pezzi[i1][j1] = pezzo;
          delete g.pezzi[i0][j0];
          g.pezzoAttivo = null;
          }
          updateMovimenti();
          drawScacchiera();
          drawPezzi();
          aggiornaUltimaMossa();
          return;
        }
      }
      g.pezzoAttivo = null;
      if(!(pos in g.pezzi)) {
        return;
      }
      if(g.giocatore.value != g.pezzi[pos].at(1)){
        return;
      }
      g.pezzoAttivo = pos;
      coloraBordoEsagono(pos,'#6688ccff');
      const movimenti = g.movimenti[pos];
      for(const pos of movimenti){
        const cellaOccupata = pos in g.pezzi;
        if (cellaFuoriTavola(pos) || (cellaOccupata && g.pezzi[pos].at(1) == g.giocatore.value)) {
          continue;
        }
        if(cellaOccupata){
          coloraBordoEsagono(pos, '#cc0000');
        }else{
          coloraBordoEsagono(pos, '#cccc00');
        }
      }
    }

    function drawCellaEsagonoByIdx(pos){
      const colori = ['#000000ff','#888888ff','#ffffffff'];
      const v = verticiEsagono(pos);
      const [i,j] = [(pos & 0xf0) >> 4,pos & 0xf];
      const cIdx = (((1 + (-j + i))%3)+3)%3;
      const dimensioneTesto = (10);
//
      g.ctx.beginPath();
      g.ctx.lineWidth = 1;
      g.ctx.strokeStyle = '#000000ff';
      g.ctx.fillStyle = colori[cIdx];
      g.ctx.moveTo(v[0].x,v[0].y);
      v.forEach(function (o) { g.ctx.lineTo(o.x,o.y); });
      g.ctx.closePath();
      g.ctx.fill();
      g.ctx.stroke();
//
      g.ctx.beginPath();
      g.ctx.lineWidth = 1;
      g.ctx.font = '' + dimensioneTesto + 'px monospace';
      g.ctx.strokeStyle = colori[(cIdx+1)%3];
      g.ctx.fillText  (i + "." + j, v[0].x, v[0].y + dimensioneTesto);
      g.ctx.strokeText(i + "." + j, v[0].x, v[0].y + dimensioneTesto);
      g.ctx.fill();
      g.ctx.stroke();
    }

    function drawScacchiera(){
      g.ctx.clearRect(0,0,c.width,c.height);
      for(var i = 0; i <  6; i++){ drawCellaEsagonoByIdx((((5 + i) & 0xf) << 4) | 0); }
      for(var i = 0; i <  7; i++){ drawCellaEsagonoByIdx((((4 + i) & 0xf) << 4) | 1); }
      for(var i = 0; i <  8; i++){ drawCellaEsagonoByIdx((((3 + i) & 0xf) << 4) | 2); }
      for(var i = 0; i <  9; i++){ drawCellaEsagonoByIdx((((2 + i) & 0xf) << 4) | 3); }
      for(var i = 0; i < 10; i++){ drawCellaEsagonoByIdx((((1 + i) & 0xf) << 4) | 4); }
      for(var i = 0; i < 11; i++){ drawCellaEsagonoByIdx((((0 + i) & 0xf) << 4) | 5); }
      for(var i = 0; i < 10; i++){ drawCellaEsagonoByIdx((((0 + i) & 0xf) << 4) | 6); }
      for(var i = 0; i <  9; i++){ drawCellaEsagonoByIdx((((0 + i) & 0xf) << 4) | 7); }
      for(var i = 0; i <  8; i++){ drawCellaEsagonoByIdx((((0 + i) & 0xf) << 4) | 8); }
      for(var i = 0; i <  7; i++){ drawCellaEsagonoByIdx((((0 + i) & 0xf) << 4) | 9); }
      for(var i = 0; i <  6; i++){ drawCellaEsagonoByIdx((((0 + i) & 0xf) << 4) |10); }
    }

    function coloraUltimaMossa(){
      coloraBordoEsagono(g.ultimaMossa.da_i,g.ultimaMossa.da_j,'#00ff00');
      coloraBordoEsagono(g.ultimaMossa.a_i,g.ultimaMossa.a_j  ,'#00ff00');
    }

    function drawPezzi(){
      const dimensioneTesto = (g.latoEsagonoCos * 2);
      const allineamentoX = 0;
      const allineamentoY = -8;
      for(pos of Object.keys(g.pezzi)){
        const [n,c] = g.pezzi[pos];
        const [x,y] = posByIdx(pos);
        g.ctx.font = 'bold ' + dimensioneTesto + 'px monospace';
        if ('B' == c) {
          // BIANCO
          g.ctx.lineWidth   = 2;
          g.ctx.fillStyle   = '#dddddd';
          g.ctx.strokeStyle = '#222222';
        } else {
          // NERO
          g.ctx.lineWidth   = 2;
          g.ctx.fillStyle   = '#222222';
          g.ctx.strokeStyle = '#dddddd';
        }
        g.ctx.beginPath();
        g.ctx.fillText  (n, x + allineamentoX, y + dimensioneTesto + allineamentoY);
        g.ctx.strokeText(n, x + allineamentoX, y + dimensioneTesto + allineamentoY);
        g.ctx.fill();
        g.ctx.stroke();
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
      var idxRe = 0;
      for(var pos of Object.keys(g.pezzi)){
        var pezzo = g.pezzi[pos];
        if(pezzo.at(1) != g.giocatore.value){
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
      for(var p0 of pedoniNemici) {
        p0 = Number(p0);
        var possibili = [];
        var p1 = 0;
        p1 = p0 +  1; if (!cellaFuoriTavola(p1) && !(p1 in g.pezzi)) {
        possibili.push(p1);
        p1 = p0 +  2; if (gPosInizialiPedoni[g.pezzi[p0].at(1)].has(p0) && !cellaFuoriTavola(p1) && !(p1 in g.pezzi)) { possibili.push(p1); }
        }
        p1 = p0 + 16; if (p1 == idxRe) { pezziScaccanti.push([p1]); } if (!cellaFuoriTavola(p1)) { possibili.push(p1); movimentiNemici.add(p1); }
        p1 = p0 - 15; if (p1 == idxRe) { pezziScaccanti.push([p1]); } if (!cellaFuoriTavola(p1)) { possibili.push(p1); movimentiNemici.add(p1); }
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
      for(var p0 of pedoniAlleati) {
        p0 = Number(p0);
        var possibili = [];
        var p1 = 0;
        const c = g.pezzi[p0].at(1);
        p1 = p0 -  1; if (!cellaFuoriTavola(p1) && !(p1 in g.pezzi)) {
        possibili.push(p1);
        p1 = p0 -  2; if (gPosInizialiPedoni[c].has(p0) && !cellaFuoriTavola(p1) && !(p1 in g.pezzi)) { possibili.push(p1); }
        }
        p1 = p0 + 15; if (p1 in g.pezzi && c != g.pezzi[p1].at(1)) { possibili.push(p1); }
        p1 = p0 - 16; if (p1 in g.pezzi && c != g.pezzi[p1].at(1)) { possibili.push(p1); }
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
    drawScacchiera();
    drawPezzi();
    selezionaPezzo(x,y);
})

    // =============================================
    // MAIN
    // =============================================

    </script>
<?php

  } break;
}

