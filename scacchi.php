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

function get_ultima_mossa($partita){
  $mossa_tipo = "R,N,06,00,06,00\n";
  $mossa_size = strlen($mossa_tipo);
  fseek($partita,0,SEEK_END);
  $partita_size = ftell($partita);
  $conto_mosse = $partita_size / $mossa_size;
  fseek($partita,($conto_mosse - 1) * $mossa_size,SEEK_SET);
  $mossa = fgetcsv($partita,escape:'\\');
  $mossa = array_combine(['nome','colore','da_i','da_j','a_i','a_j'],$mossa);
  $k = 'da_i'; $mossa[$k] = (int)$mossa[$k];
  $k = 'da_j'; $mossa[$k] = (int)$mossa[$k];
  $k =  'a_i'; $mossa[$k] = (int)$mossa[$k];
  $k =  'a_j'; $mossa[$k] = (int)$mossa[$k];
  return $mossa;
}

$method = 'method';
$method = $_GET[$method];
switch($method){
case 'nuova_partita': {
    $condizioni_iniziali = <<<eof
      A,B,05,10,05,10
      A,B,05,09,05,09
      A,B,05,08,05,08
      P,B,01,10,01,10
      P,B,02,09,02,09
      P,B,03,08,03,08
      P,B,04,07,04,07
      P,B,05,06,05,06
      P,B,06,06,06,06
      P,B,07,06,07,06
      P,B,08,06,08,06
      P,B,09,06,09,06
      T,B,02,10,02,10
      T,B,08,07,08,07
      C,B,03,10,03,10
      C,B,07,08,07,08
      D,B,04,10,04,10
      R,B,06,09,06,09
      P,N,01,04,01,04
      P,N,02,04,02,04
      P,N,03,04,03,04
      P,N,04,04,04,04
      P,N,05,04,05,04
      P,N,06,03,06,03
      P,N,07,02,07,02
      P,N,08,01,08,01
      P,N,09,00,09,00
      A,N,05,00,05,00
      A,N,05,01,05,01
      A,N,05,02,05,02
      T,N,02,03,02,03
      T,N,08,00,08,00
      C,N,03,02,03,02
      C,N,07,00,07,00
      D,N,04,01,04,01
      R,N,06,00,06,00\n
      eof;
    $condizioni_iniziali = str_replace(' ',"\n",$condizioni_iniziali);
    $nuova_partita = '*';
    $nuova_partita = glob($nuova_partita);
    $nuova_partita = count($nuova_partita);
    $nuova_partita = sprintf('%04d',$nuova_partita);
    echo "$nuova_partita\n";
    $nuova_partita = file_put_contents($nuova_partita,$condizioni_iniziali);
    if(false === $nuova_partita){
      die('errore scrittura di una nuova partita');
    }
    header('Location: ?');
  } break;
case 'muovi': {
    $mossa_k =
      [ 'nome'    => fn ($a) => str_contains('PTCADR',$a)
      , 'colore'  => fn ($a) => str_contains('BN',$a)
      , 'da_i'    => fn ($a) => true
      , 'da_j'    => fn ($a) => true
      , 'a_i'     => fn ($a) => true
      , 'a_j'     => fn ($a) => true
      ];
    $mossa = [];
    foreach($mossa_k as $k => $f){
      if (!array_key_exists($k,$_GET)) {
        die("errore chiave $k non presente in \$_GET");
      }
      $v = $_GET[$k];
      if (!$f($v)) {
        die("errore valore $k non soddisfa la correttezza");
      }
      $mossa[] = $v;
    }
    $k = 2; $mossa[$k] = sprintf('%02d',$mossa[$k]);
    $k = 3; $mossa[$k] = sprintf('%02d',$mossa[$k]);
    $k = 4; $mossa[$k] = sprintf('%02d',$mossa[$k]);
    $k = 5; $mossa[$k] = sprintf('%02d',$mossa[$k]);
    $partita = 'partita';
    if (!array_key_exists($partita,$_GET)) { die('errore nessuna partita specificata'); }
    $partita_id = $_GET[$partita];
    $partita = fopen($partita_id,'a');
    if(false === $partita) { die("errore nell'apertura di $partita_id"); }
    if(fputcsv($partita,$mossa, escape : '\\'));
    fclose($partita);
    $partita = fopen($partita_id,'r');
    $ultimamossa = $mossa;
    $ultimamossajson = json_encode($ultimamossa,JSON_FORCE_OBJECT);
    $mossa = get_ultima_mossa($partita);
    $mossajson = json_encode($mossa,JSON_FORCE_OBJECT);
    if (0 == strcmp($mossajson,$ultimamossajson)) {
      die("errore mossa scritta differisce dalla mossa riletta\n-$mossajson\n-$ultimamossajson");
    }
    echo $mossajson;
    fclose($partita);
  } break;
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
    <a href='<?php echo $_SERVER['REQUEST_URI'] . '&g=B' ; ?>'>Bianco</a>
    <a href='<?php echo $_SERVER['REQUEST_URI'] . '&g=N' ; ?>'>Nero</a>
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
      <label>Pezzo: </label>
      <select name=nome>
        <option value='P'>P</option>
        <option value='T'>T</option>
        <option value='C'>C</option>
        <option value='A'>A</option>
        <option value='D'>D</option>
        <option value='R'>R</option>
      </select><br>
      <label>Colore: </label>
      <select name=colore>
        <option value='B'>B</option>
        <option value='N'>N</option>
      </select><br>
      <label>da I:</label><input type=number name=da_i><br>
      <label>da J:</label><input type=number name=da_j><br>
      <label>a I: </label><input type=number name=a_i><br>
      <label>a J: </label><input type=number name=a_j><br>
      <input type=submit value='muovi'>
    </form>
    <script>

    function addPezzo(i,j,c,n) {
      if(!(i in g.pezzi)){ g.pezzi[i] = {}; }
      g.pezzi[i][j] = {colore:c,nome:n};
    }

    function muoviPezzo(i0,j0,i1,j1,c,n,g) {
      if (i0 in g.pezzi) { delete g.pezzi[i0][j0];}
      if (!(i1 in g.pezzi)) { g.pezzi[i1] = {}; }
      g.pezzi[i1][j1] = {colore:c,nome:n};
    }

    function ultimaMossa(){
      var xhttp = new XMLHttpRequest();
      xhttp.open("GET", "/?method=ultima_mossa&partita=<?php echo $partita_id; ?>", false);
      xhttp.send();
      if (200 != xhttp.status) { alert("ERRORE NEL FETCHING DELL'ULTIMA MOSSA"); throw 0; }
      return xhttp.responseText;
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
      g.giocatore.value = <?php echo json_encode($giocatore); ?>;
      g.ultimaMossa = JSON.parse(ultimaMossa());
      const partita = <?php echo json_encode($partita,JSON_FORCE_OBJECT); ?>;
      const strlen_partita = partita.length;
      for(var i = 0; i < strlen_partita;){
        const n  = partita.at(i); i += 2;
        const c  = partita.at(i); i += 2;
        const i0 = Number(partita.substr(i,2)); i += 3;
        const j0 = Number(partita.substr(i,2)); i += 3;
        const i1 = Number(partita.substr(i,2)); i += 3;
        const j1 = Number(partita.substr(i,2)); i += 3;
        muoviPezzo(i0,j0,i1,j1,c,n,g);
      }
      return g;
    }();

    function posByIdx(i,j){
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

        return [i,j];
    }

    function verticiEsagono(i,j){
      const [x,y] = posByIdx(i,j);
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

    function coloraBordoEsagono(i,j,colore){
      const v = verticiEsagono(i,j);
      g.ctx.lineWidth = 6;
      g.ctx.strokeStyle = colore;
      g.ctx.beginPath();
      g.ctx.moveTo(v[0].x,v[0].y);
      v.forEach(function (o) { g.ctx.lineTo(o.x,o.y); });
      g.ctx.closePath();
      g.ctx.stroke();
    }

    function cellaFuoriTavola(i,j){
        return 10 < j || 10 < i || j < 0 || i < 0 || (j < 5 && (i < 5 - j)) || (5 < j && ((15 - j) < i));
    }

    function cellaLibera(i,j){
      return !(i in g.pezzi) || !(j in g.pezzi[i]);
    }

    function spostaVerticale(i,j,o){
      return [i,j + o];
    }
    function spostaAscendente(i,j,o){
      return [i + o,j];
    }
    function spostaDiscendente(i,j,o){
      return [i + o,j - o];
    }
    function spostaDiagonaleOrizzontale(i,j,o){
      return [i + 2 * o,j - o];
    }
    function spostaDiagonaleAscendente(i,j,o){
      return [i + o,j + o];
    }
    function spostaDiagonaleDiscendente(i,j,o){
      return [i + o,j - 2 * o];
    }
    function spostaCavalloAscendenteRipida(i,j,o){
      return [i + o,j + 2 * o];
    }
    function spostaCavalloAscendentePiana(i,j,o){
      return [i + 3 * o,j - o];
    }
    function spostaCavalloAscendenteMedia(i,j,o){
      return [i + 2 * o,j + o];
    }
    function spostaCavalloDiscendenteRipida(i,j,o){
      return [i + o,j - 3 * o];
    }
    function spostaCavalloDiscendentePiana(i,j,o){
      return [i + 2 * o,j - 3 * o];
    }
    function spostaCavalloDiscendenteMedia(i,j,o){
    return [i + 3 * o,j - 2 * o];
    }
    function spostaPedoneVerticale(i,j,o){
      const iniziali =
        { 'B' : { 1 : 10, 2 : 9, 3 : 8, 4 : 7, 5 : 6, 6 : 6, 7 : 6, 8 : 6, 9 : 6 }
        , 'N' : { 1 : 4, 2 : 4, 3 : 4, 4 : 4, 5 : 4, 6 : 3, 7 : 2, 8 : 1, 9 : 0 }
        }[g.pezzi[i][j].colore];
      const mossaIniziale = i in iniziali && j == iniziali[i];
      const limite = 1 + mossaIniziale;
      const dentroLimiti = -limite <= o && o <= limite;
      const cella = [i, j + o];
      const [i1,j1] = cella;
      if (!dentroLimiti || !cellaLibera(i1,j1)) {return [0,0]}
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
      const {colore:c, nome:n, da_i:i0, da_j:j0, a_i:i1, a_j:j1 } = g.ultimaMossa;
      const pedoneNemico        = c != g.giocatore.value && 'P' == n;
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
      const {colore:c, nome:n, da_i:i0, da_j:j0, a_i:i1, a_j:j1 } = g.ultimaMossa;
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

      if (cellaFuoriTavola(i,j) || cellaLibera(i,j)) {
        return null;
      }

      if (!(i in g.movimenti)) { g.movimenti[i] = {}; }
      g.movimenti[i][j] = null;

      const pezzo = g.pezzi[i][j];

      switch (pezzo.nome) {

        case 'P': {
          const direzione = ('B' == pezzo.colore) ? (-1) : (1);
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
      g.ultimaMossa = JSON.parse(ultimaMossaStr);
      const {da_i:i0,da_j:j0,a_i:i1,a_j:j1,colore:c,nome:n} = g.ultimaMossa;
      muoviPezzo(i0,j0,i1,j1,c,n,g);
      updateMovimenti();
      drawScacchiera();
      drawPezzi();
    }

    function selezionaPezzo(x,y){

      g.ultimaMossa = JSON.parse(ultimaMossa());
      if (g.ultimaMossa.colore == g.giocatore.value) {
        return;
      }

      const [i,j] = idxByPos(x,y);

      if(cellaFuoriTavola(i,j)){
        return;
      }

      if (null != g.pezzoAttivo){
        const [i0,j0] = g.pezzoAttivo;
        if (1 == g.movimenti[i0][j0].filter(([i1,j1]) => i == i1 && j == j1).length) {
          const pezzo = g.pezzi[i0][j0];
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

      if(cellaLibera(i,j)) {
        return;
      }

      if(g.giocatore.value != g.pezzi[i][j].colore){
        return;
      }

      g.pezzoAttivo = [i,j];
      coloraBordoEsagono(i,j,'#6688ccff');
      const movimenti = g.movimenti[i][j];
      for(const [i,j] of movimenti){
        const cellaOccupata = !cellaLibera(i,j)
        if (cellaFuoriTavola(i,j) || (cellaOccupata && g.pezzi[i][j].colore == g.giocatore.value)) {
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

      const v = verticiEsagono(i,j);
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

      coloraBordoEsagono(g.ultimaMossa.da_i,g.ultimaMossa.da_j,'#00ff00');
      coloraBordoEsagono(g.ultimaMossa.a_i,g.ultimaMossa.a_j  ,'#00ff00');
    }

    function drawPezzi(){
      const dimensioneTesto = (g.latoEsagonoCos * 2);
      const allineamentoX = 0;
      const allineamentoY = -8;
      function disegnaPezzo(i,j,pezzo){
        const [x,y] = posByIdx(i,j);

        g.ctx.font = 'bold ' + dimensioneTesto + 'px monospace';
        if ('B' == pezzo.colore) {
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
        g.ctx.fillText  (pezzo.nome, x + allineamentoX, y + dimensioneTesto + allineamentoY);
        g.ctx.strokeText(pezzo.nome, x + allineamentoX, y + dimensioneTesto + allineamentoY);
        g.ctx.fill();
        g.ctx.stroke();
      }

      //g.pezzi.bianchi.forEach(disegnaPezzo);
      //g.pezzi.neri.forEach(disegnaPezzo);

      Object.keys(g.pezzi).forEach(i => {
        Object.keys(g.pezzi[i]).forEach(j => {
          disegnaPezzo(i,j,g.pezzi[i][j])
        });
      });

    }

    function updateMovimenti(){
      g.scacco = null;
      g.movimenti = {};
      g.pezziDifesi = [];
      g.pezziScaccabili = {};

      var pezziNemici  = [];
      var pezziAlleati = [];
      var idxRe = [];
      for(const i of Object.keys(g.pezzi)){
        for(const j of Object.keys(g.pezzi[i])){
          const pezzo = g.pezzi[i][j];
          if(pezzo.colore != g.giocatore.value){
            pezziNemici.push([i,j]);
          }else if('R' == pezzo.nome){
            idxRe.push([i,j]);
          }else{
            pezziAlleati.push([i,j]);
          }
        }
      }

      function erranteNemico(i,j,limite,movimenti,possibili){
        const massimo = 256;
        limite = ((((limite) % massimo) + massimo) % massimo) + 1;
        for(const {m:m, d:d} of movimenti){
          var consentite = [];
          consentite.push([i,j]);
          for(var k = 1; k < limite; k++){
            const cella = m(i,j,d*k);
            const [i0,j0] = cella;
            if(cellaFuoriTavola(i0,j0)){
              break;
            } else if(cellaLibera(i0,j0)){
              possibili.push(cella);
              consentite.push(cella);
              continue;
            }else if(g.pezzi[i][j].colore == g.pezzi[i0][j0]){
              g.pezziDifesi.push(cella);
              break;
            }else if(cella in idxRe){
              g.scacco = cella;
              break;
            }else{
              possibili.push(cella);
              for(l = k+1; l < limite; l++){
                const minaccia = m(i,j,d*l);
                const [i1,j1] = minaccia;
                if(cellaFuoriTavola(i1,j1)){
                  break;
                } else if (cellaLibera(i1,j1)) {
                  consentite.push(minaccia);
                  continue;
                } else {
                  if (minaccia in idxRe) {
                    const pezzoScaccabile = {pezzo:[i,j],consentite:consentite};
                    g.pezziScaccabili[i0][j0] = pezzoScaccabile;
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
      for(const [i,j] of pezziNemici){
        var possibili = [];
        movimentiPezzo(i,j,erranteNemico,possibili);
        g.movimenti[i][j] = possibili;
        for(const [i,j] of possibili){
          if(!(i in celleMovimentiNemici)){celleMovimentiNemici[i] = {};}
          if(!(j in celleMovimentiNemici[i])){celleMovimentiNemici[i][j] = 0;}
          celleMovimentiNemici[i][j]++;
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
            if(cellaFuoriTavola(i0,j0)){
              break;
            } else if(cellaLibera(i0,j0)){
              potenzialiPossibili.push(cella);
              continue;
            } else if(g.pezzi[i0][j0].colore != g.pezzi[i][j].colore){
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
        var possibili = [];
        movimentiPezzo(i,j,erranteAlleato,possibili);
        g.movimenti[i][j] = possibili;
      }

      function erranteRe(i,j,limite,movimenti,possibili){
        const massimo = 256;
        limite = ((((limite) % massimo) + massimo) % massimo) + 1;
        for(const {m:m, d:d} of movimenti){
          for(var k = 1; k < limite; k++){
            const cella = m(i,j,d*k);
            const [i0,j0] = cella;
            if(cellaFuoriTavola(i0,j0)){
              break;
            } else if(i0 in celleMovimentiNemici && j0 in celleMovimentiNemici[i0] && 0 < celleMovimentiNemici[i0][j0]){
              break;
            } else if(cellaLibera(i0,j0)){
              possibili.push(cella);
              continue;
            } else if(g.pezzi[i0][j0].colore != g.pezzi[i][j].colore){
              possibili.push(cella);
              break;
            } else {
              break;
            }
          }
        }
      }
      for(const [i,j] of idxRe){
        var possibili = [];
        movimentiPezzo(i,j,erranteRe,possibili);
        g.movimenti[i][j] = possibili;
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

    if (g.ultimaMossa.colore == g.giocatore.value) {
      aggiornaUltimaMossa();
    }
    updateMovimenti();
    drawScacchiera();
    drawPezzi();

    </script>
<?php

  } break;
}

