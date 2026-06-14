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
      <button type=button onclick="setN(2,11);">11</button>
      <button type=button onclick="setN(2,12);">12</button>
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
      <button type=button onclick="setN(3,11);">11</button>
      <button type=button onclick="setN(3,12);">12</button>
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
      <button type=button onclick="setN(4,11);">11</button>
      <button type=button onclick="setN(4,12);">12</button>
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
      <button type=button onclick="setN(5,11);">11</button>
      <button type=button onclick="setN(5,12);">12</button>
      <br>
      <input type=submit value='muovi'>
    </form>
    <textarea id=cronologia readonly></textarea>
    <script>

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
      g.giocatore = htmlGiocatore;
      g.giocatore.value = "<?php echo $giocatore; ?>";
      httpGet("/?method=leggi_mosse&partita=<?php echo $partita_id; ?>",function (txt) {
        cronologia.innerHTML = txt;
        eseguiMosse(txt);
        drawScacchiera();
        drawPezzi();
        updateMovimenti();
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
        const x0 = x - g.xpad;
        const y0 = y - g.ypad;
        const y1 = y0 - g.latoEsagonoCos;

        if (x0 < 0) { return [0,0]; }

        const altezzaEsagono   = g.latoEsagonoCos * 2;
        const larghezzaEsagono = g.latoEsagonoSin + g.latoEsagono;

        const a0     = 1 / Math.sqrt(3);
        const bAsc   = y1 - (+a0*x0);
        const deltaY = y1 - bAsc;
        const bDis = Math.sqrt(deltaY*deltaY + x0*x0);
        const j0 = Math.round(bAsc / altezzaEsagono);
        const i0 = (bDis - (bDis % altezzaEsagono)) / altezzaEsagono;

        let v = verticiEsagono(i0,j0);
        v = v.map(o => ({x:o.x-g.xpad,y:o.y-g.ypad}));
        const a1 = Math.sqrt(3);
        const bAsc0 = v[3].y - (+a1 * v[3].x);
        const bDis0 = v[3].y - (-a1 * v[3].x);

        const yAsc =  a1 * x0 + bAsc0;
        const yDis = -a1 * x0 + bDis0;
        const yCst = v[3].y;

        const i = i0 + (yDis < y0 && y0 < yAsc);
        const j = j0 + (yCst < y0 && yAsc < y0);

        return ((i & 0xf) << 4) | (j & 0xf);
    }

    function verticiEsagono(pos){
      const [x,y] = posByIdx(pos);
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

    function cellaLibera(i,j) {
      const pos = ((i & 0xf) << 4) | (j & 0xf);
      return !(pos in g.pezzi);
    }

    function cellaFuoriTavola(pos){
        const i = (pos & 0xf0) >> 4;
        const j = pos & 0xf;
        return 10 < j || 10 < i || j < 0 || i < 0 || (j < 5 && (i < 5 - j)) || (5 < j && ((15 - j) < i));
    }

    function spostaVerticale(pos,o){
      // return [i,j+o];
      return (pos & 0xf0) | ((pos + o) & 0xf);
    }
    function spostaAscendente(pos,o){
      // return [i+o,j];
      return ((pos + (o << 4)) & 0xf0) | (pos & 0xf);
    }
    function spostaDiscendente(pos,o){
      // return [i+o,j-o];
      return ((pos + (o << 4)) & 0xf0) | ((pos - o) & 0xf);
    }
    function spostaDiagonaleOrizzontale(pos,o){
      // return [i + 2 * o,j - o];
      return ((pos + (o << 5)) & 0xf0) | ((pos - o) & 0xf);
    }
    function spostaDiagonaleAscendente(pos,o){
      // return [i + o,j + o];
      return ((pos + (o << 4)) & 0xf0) | ((pos + o) & 0xf);
    }
    function spostaDiagonaleDiscendente(pos,o){
      // return [i + o,j - 2 * o];
      return ((pos + (o << 4)) & 0xf0) | ((pos - 2 * o) & 0xf);
    }
    function spostaCavalloAscendenteRipida(pos,o){
      // return [i + o,j + 2 * o];
      return ((pos + (o << 4)) & 0xf0) | ((pos + 2 * o) & 0xf);
    }
    function spostaCavalloAscendentePiana(pos,o){
      // return [i + 3 * o,j - o];
      return ((pos + (o << 5) + (0 << 4)) & 0xf0) | ((pos - o) & 0xf);
    }
    function spostaCavalloAscendenteMedia(pos,o){
      // return [i + 2 * o,j + o];
      return ((pos + (o << 5)) & 0xf0) | ((pos + o) & 0xf);
    }
    function spostaCavalloDiscendenteRipida(pos,o){
      // return [i + o,j - 3 * o];
      return ((pos + (o << 4)) & 0xf0) | ((pos - 3 * o) & 0xf);
    }
    function spostaCavalloDiscendentePiana(pos,o){
      // return [i + 2 * o,j - 3 * o];
      return ((pos + (o << 5)) & 0xf0) | ((pos - 3 * o) & 0xf);
    }
    function spostaCavalloDiscendenteMedia(pos,o){
      // return [i + 3 * o,j - 2 * o];
      return ((pos + (o << 5) + (0 << 4)) & 0xf0) | ((pos - (2 * o)) & 0xf);
    }
    function spostaPedoneVerticale(pos,o){
      const iniziali =
        { 'B' : { 1 : 10, 2 : 9, 3 : 8, 4 : 7, 5 : 6, 6 : 6, 7 : 6, 8 : 6, 9 : 6 }
        , 'N' : { 1 : 4, 2 : 4, 3 : 4, 4 : 4, 5 : 4, 6 : 3, 7 : 2, 8 : 1, 9 : 0 }
        }[g.pezzi[pos].at(1)];
      const mossaIniziale = i in iniziali && j == iniziali[i];
      const limite = 1 + mossaIniziale;
      const dentroLimiti = -limite <= o && o <= limite;
      const cella = (pos & 0xf0) | ((pos + o) & 0xf);
      if (!dentroLimiti || !cellaLibera(cella)) { return 0; }
      return cella;
    }
    function spostaPedoneAscendente(i,j,o){
      const dentroLimiti = -1 <= o && o <= 1;
      const cella = [i + o, j];
      const [i2,j2] = cella;
      const iniziali =
        { 'B' : { 1 : 10, 2 : 9, 3 : 8, 4 : 7, 5 : 6, 6 : 6, 7 : 6, 8 : 6, 9 : 6 }
        , 'N' : { 1 : 4, 2 : 4, 3 : 4, 4 : 4, 5 : 4, 6 : 3, 7 : 2, 8 : 1, 9 : 0 }
        };
      const ultimaMossaStr      = ultimaMossa();
      const [n,c,...r]          = ultimaMossaStr;
      const [i0,j0,i1,j1]       = r.map(c => c.charCodeAt(0) - 65);
      const pedoneNemico        = ultimaMossaStr[1] != g.giocatore.value && 'P' == ultimaMossaStr[0];
      const pedonePassoIniziale = i0 in iniziali[c] && j0 == iniziali[c][i0];
      const pedonePassoDoppio   = i0 == i1 && 2 == ((j0 < j1) ? (j1 - j0) : (j0 - j1));
      const pedoneMangiabile    = i2 == i1 && j2 == (('N' == c) ? (j1 - 1) : (j1 + 1));
      const enPassant = pedoneNemico && pedonePassoIniziale && pedonePassoDoppio && pedoneMangiabile;
      if (!dentroLimiti || (!enPassant && cellaLibera(i2,j2))) { return [0,0]; }
      return cella;
    }
    function spostaPedoneDiscendente(i,j,o){
      const dentroLimiti = -1 <= o && o <= 1;
      const cella = [i + o, j - o];
      const [i2,j2] = cella;
      const iniziali =
        { 'B' : { 1 : 10, 2 : 9, 3 : 8, 4 : 7, 5 : 6, 6 : 6, 7 : 6, 8 : 6, 9 : 6 }
        , 'N' : { 1 : 4, 2 : 4, 3 : 4, 4 : 4, 5 : 4, 6 : 3, 7 : 2, 8 : 1, 9 : 0 }
        };
      const ultimaMossaStr      = ultimaMossa();
      const [n,c,...r]          = ultimaMossaStr;
      const [i0,j0,i1,j1]       = r.map(c => c.charCodeAt(0) - 65);
      const pedoneNemico        = c != g.giocatore.value && 'P' == n;
      const pedonePassoIniziale = i0 in iniziali[c] && j0 == iniziali[c][i0];
      const pedonePassoDoppio   = i0 == i1 && 2 == ((j0 < j1) ? (j1 - j0) : (j0 - j1));
      const pedoneMangiabile    = i2 == i1 && j2 == (('N' == c) ? (j1 - 1) : (j1 + 1));
      const enPassant = pedoneNemico && pedonePassoIniziale && pedonePassoDoppio && pedoneMangiabile;
      if (!dentroLimiti || (!enPassant && cellaLibera(i2,j2))) { return [0,0]; }
      return cella;
    }

    function movimentiPezzo(i,j,errante,possibili){

      i = Number(i);
      j = Number(j);
      const pos = String.fromCharCode(65 + i) + String.fromCharCode(65 + j);

      if (cellaFuoriTavola(i,j) || !(pos in g.pezzi)) {
        return null;
      }

      g.movimenti[pos] = null;

      const pezzo = g.pezzi[pos];

      switch (pezzo.at(0)) {

        case 'P': {
          const direzione = ('B' == pezzo.at(1)) ? (-1) : (1);
          const movimenti =
            [ { d : +direzione, m : spostaPedoneVerticale   }
            , { d : +direzione, m : spostaPedoneAscendente  }
            , { d : -direzione, m : spostaPedoneDiscendente }
            ]
          errante(i,j,2,movimenti,possibili);
        } break;

        case 'A': {
          const movimenti =
            [ { d : -1, m : spostaDiagonaleOrizzontale }
            , { d : +1, m : spostaDiagonaleOrizzontale }
            , { d : -1, m : spostaDiagonaleAscendente  }
            , { d : +1, m : spostaDiagonaleAscendente  }
            , { d : -1, m : spostaDiagonaleDiscendente }
            , { d : +1, m : spostaDiagonaleDiscendente }
            ]
          errante(i,j,-1,movimenti,possibili);
        } break;

        case 'T': {
          const movimenti =
            [ { d : -1, m : spostaVerticale   }
            , { d : +1, m : spostaVerticale   }
            , { d : -1, m : spostaAscendente  }
            , { d : +1, m : spostaAscendente  }
            , { d : -1, m : spostaDiscendente }
            , { d : +1, m : spostaDiscendente }
            ]
          errante(i,j,-1,movimenti,possibili);
        } break;

        case 'D': {
          const movimenti =
            [ { d : -1, m : spostaVerticale            }
            , { d : +1, m : spostaVerticale            }
            , { d : -1, m : spostaAscendente           }
            , { d : +1, m : spostaAscendente           }
            , { d : -1, m : spostaDiscendente          }
            , { d : +1, m : spostaDiscendente          }
            , { d : -1, m : spostaDiagonaleOrizzontale }
            , { d : +1, m : spostaDiagonaleOrizzontale }
            , { d : -1, m : spostaDiagonaleAscendente  }
            , { d : +1, m : spostaDiagonaleAscendente  }
            , { d : -1, m : spostaDiagonaleDiscendente }
            , { d : +1, m : spostaDiagonaleDiscendente }
            ];
          errante(i,j,-1,movimenti,possibili);
        } break;

        case 'R': {
          const movimenti =
            [ { d : -1, m : spostaVerticale            }
            , { d : +1, m : spostaVerticale            }
            , { d : -1, m : spostaAscendente           }
            , { d : +1, m : spostaAscendente           }
            , { d : -1, m : spostaDiscendente          }
            , { d : +1, m : spostaDiscendente          }
            , { d : -1, m : spostaDiagonaleOrizzontale }
            , { d : +1, m : spostaDiagonaleOrizzontale }
            , { d : -1, m : spostaDiagonaleAscendente  }
            , { d : +1, m : spostaDiagonaleAscendente  }
            , { d : -1, m : spostaDiagonaleDiscendente }
            , { d : +1, m : spostaDiagonaleDiscendente }
            ]
          errante(i,j,1,movimenti,possibili);
        } break;

        case 'C': {
          const movimenti =
            [ { d : +1, m : spostaCavalloAscendenteRipida  }
            , { d : -1, m : spostaCavalloAscendenteRipida  }
            , { d : +1, m : spostaCavalloAscendentePiana   }
            , { d : -1, m : spostaCavalloAscendentePiana   }
            , { d : +1, m : spostaCavalloAscendenteMedia   }
            , { d : -1, m : spostaCavalloAscendenteMedia   }
            , { d : +1, m : spostaCavalloDiscendenteRipida }
            , { d : -1, m : spostaCavalloDiscendenteRipida }
            , { d : +1, m : spostaCavalloDiscendentePiana  }
            , { d : -1, m : spostaCavalloDiscendentePiana  }
            , { d : +1, m : spostaCavalloDiscendenteMedia  }
            , { d : -1, m : spostaCavalloDiscendenteMedia  }
            ];
          errante(i,j,1,movimenti,possibili);
        } break;

      }
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

      if (null != g.pezzoAttivo){
        if (1 == g.movimenti[g.pezzoAttivo].filter(([i1,j1]) => i == i1 && j == j1).length) {
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
      for(const [i,j] of movimenti){
        const cellaOccupata = pos in g.pezzi;
        if (cellaFuoriTavola(i,j) || (cellaOccupata && g.pezzi[pos].at(1) == g.giocatore.value)) {
          continue;
        }
        if(cellaOccupata){
          coloraBordoEsagono(i,j, '#cc0000');
        }else{
          coloraBordoEsagono(i,j, '#cccc00');
        }
      }

    }

    function drawCellaEsagonoByIdx(i,j){
      const colori = ['#000000ff','#888888ff','#ffffffff'];

      g.ctx.beginPath();
      g.ctx.lineWidth = 1;
      g.ctx.strokeStyle = '#000000ff';
      g.ctx.fillStyle = colori[(((1 + (-j + i))%3)+3)%3];

      const pos = ((i & 0xf) << 4) | (j & 0xf);
      const v = verticiEsagono(pos);
      g.ctx.moveTo(v[0].x,v[0].y);
      v.forEach(function (o) { g.ctx.lineTo(o.x,o.y); });
      g.ctx.closePath();

      g.ctx.fill();
      g.ctx.stroke();

      if (i in g.pezzi && j in g.pezzi[i]) { return; }

      const dimensioneTesto = (10);
      g.ctx.beginPath();
      g.ctx.lineWidth = 1;
      g.ctx.font = '' + dimensioneTesto + 'px monospace';
      g.ctx.strokeStyle = colori[(((1 + (-j + i))%3)+3 + 1)%3];;
      g.ctx.fillText  (i + "." + j, v[0].x, v[0].y + dimensioneTesto);
      g.ctx.strokeText(i + "." + j, v[0].x, v[0].y + dimensioneTesto);
      g.ctx.fill();
      g.ctx.stroke();

    }

    function drawScacchiera(){
      g.ctx.clearRect(0,0,c.width,c.height);
      for(var i = 0; i <  6; i++){ drawCellaEsagonoByIdx(5 + i, 0); }
      for(var i = 0; i <  7; i++){ drawCellaEsagonoByIdx(4 + i, 1); }
      for(var i = 0; i <  8; i++){ drawCellaEsagonoByIdx(3 + i, 2); }
      for(var i = 0; i <  9; i++){ drawCellaEsagonoByIdx(2 + i, 3); }
      for(var i = 0; i < 10; i++){ drawCellaEsagonoByIdx(1 + i, 4); }
      for(var i = 0; i < 11; i++){ drawCellaEsagonoByIdx(0 + i, 5); }
      for(var i = 0; i < 10; i++){ drawCellaEsagonoByIdx(0 + i, 6); }
      for(var i = 0; i <  9; i++){ drawCellaEsagonoByIdx(0 + i, 7); }
      for(var i = 0; i <  8; i++){ drawCellaEsagonoByIdx(0 + i, 8); }
      for(var i = 0; i <  7; i++){ drawCellaEsagonoByIdx(0 + i, 9); }
      for(var i = 0; i <  6; i++){ drawCellaEsagonoByIdx(0 + i,10); }
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

      var pezziNemici  = [];
      var pezziAlleati = [];
      var idxRe = [];
      for(var pos of Object.keys(g.pezzi)){
        var pezzo = g.pezzi[pos];
        if(pezzo.at(1) != g.giocatore.value){
          pezziNemici.push(pos);
        }else if('R' == pezzo.at(0)){
          idxRe.push(pos);
        }else{
          pezziAlleati.push(pos);
        }
      }

      function erranteNemico(pos,limite,movimenti,possibili){
        const massimo = 256;
        limite = ((((limite) % massimo) + massimo) % massimo) + 1;
        for(const {m:m, d:d} of movimenti){
          var consentite = [];
          consentite.push(pos);
          for(var k = 1; k < limite; k++){
            const cella = m(pos,d*k);
            if(cellaFuoriTavola(cella)){
              break;
            } else if(cellaLibera(cella)){
              possibili.push(cella);
              consentite.push(cella);
              continue;
            }else if(g.pezzi[pos].at(1) == g.pezzi[cella]){
              g.pezziDifesi.push(cella);
              break;
            }else if(cella in idxRe){
              g.scacco = cella;
              break;
            }else{
              possibili.push(cella);
              for(l = k+1; l < limite; l++){
                const minaccia = m(pos,d*l);
                if(cellaFuoriTavola(minaccia)){
                  break;
                } else if (cellaLibera(minaccia)) {
                  consentite.push(minaccia);
                  continue;
                } else {
                  if (minaccia in idxRe) {
                    const pezzoScaccabile = {pezzo:pos,consentite:consentite};
                    g.pezziScaccabili[cella] = pezzoScaccabile;
                  }
                  break;
                }
              }
              break;
            }
          }
        }
      }

      var celleMovimentiNemici = {};
      for(pos of pezziNemici){
        var possibili = [];
        movimentiPezzo(pos,erranteNemico,possibili);
        g.movimenti[pos] = possibili;
        for(const pos of possibili){
          if(!(pos in celleMovimentiNemici)){celleMovimentiNemici[pos] = 0;}
          celleMovimentiNemici[pos]++;
        }
      }

      function erranteAlleato(i,j,limite,movimenti,possibili){
        var potenzialiPossibili = [];
        const massimo = 256;
        limite = ((((limite) % massimo) + massimo) % massimo) + 1;
        for(const {m:m, d:d} of movimenti){
          for(var k = 1; k < limite; k++){
            const cella = m(i,j,d*k);
            const [i0,j0] = cella;
            const pos0 = String.fromCharCode(65 + i0) + String.fromCharCode(65 + j0);
            if(cellaFuoriTavola(i0,j0)){
              break;
            } else if(cellaLibera(i0,j0)){
              potenzialiPossibili.push(cella);
              continue;
            } else if(g.pezzi[pos0].at(1) != g.pezzi[pos].at(1)){
              potenzialiPossibili.push(cella);
              break;
            } else {
              break;
            }
          }
        }
        if (null != g.scacco || (i in g.pezziScaccabili && j in g.pezziScaccabili[i])) {
          const {cella:minacciante, consentite:consentite} = g.pezziScaccabili[i][j];
          potenzialiPossibili = consentite.filter(c => c in potenzialiPossibili);
        }
        for(const c of potenzialiPossibili){
          possibili.push(c);
        }
      }
      for(const [i,j] of pezziAlleati){
        const pos = String.fromCharCode(65 + i) + String.fromCharCode(65 + j);
        var possibili = [];
        movimentiPezzo(i,j,erranteAlleato,possibili);
        g.movimenti[pos] = possibili;
      }

      function erranteRe(i,j,limite,movimenti,possibili){
        const massimo = 256;
        limite = ((((limite) % massimo) + massimo) % massimo) + 1;
        for(const {m:m, d:d} of movimenti){
          for(var k = 1; k < limite; k++){
            const cella = m(i,j,d*k);
            const [i0,j0] = cella;
            const pos0 = String.fromCharCode(65 + i0) + String.fromCharCode(65 + j0);
            if(cellaFuoriTavola(i0,j0)){
              break;
            } else if(i0 in celleMovimentiNemici && j0 in celleMovimentiNemici[i0] && 0 < celleMovimentiNemici[i0][j0]){
              break;
            } else if(cellaLibera(i0,j0)){
              possibili.push(cella);
              continue;
            } else if(g.pezzi[pos0].at(1) != g.pezzi[pos].at(1)){
              possibili.push(cella);
              break;
            } else {
              break;
            }
          }
        }
      }
      for(const [i,j] of idxRe){
        const pos = String.fromCharCode(65 + i) + String.fromCharCode(65 + j);
        var possibili = [];
        movimentiPezzo(i,j,erranteRe,possibili);
        g.movimenti[pos] = possibili;
      }
    }


    // =============================================
    // LISTENERS
    // =============================================

c.addEventListener('mousedown', function(e) {
    const rect = this.getBoundingClientRect();
    const x = event.clientX - rect.left;
    const y = event.clientY - rect.top;
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

