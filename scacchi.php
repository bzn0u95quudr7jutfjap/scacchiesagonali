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
  $a_partite = array_map(fn($a)=> "<li><a href='?method=partita&partita=$a'>$a</a></li>",$a_partite);
  $a_partite = implode('',$a_partite);
  $html = <<<eof
  <!DOCTYPE html>
  <html>
    <head>
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <style>
      </style>
    </head>
    <body>
      <a href='?method=nuova_partita'>Crea una nuova partita</a>
      <h1> Partite attive </h1>
      <o> {$a_partite} </o>
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
      R,N,06,00,06,00
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
case 'partita': {
    $partita = 'partita';
    $partita = $_GET[$partita];
    $partita = fopen($partita,'r+');
    $pezzi = [];
    while($mossa = fgetcsv($partita,escape: '\\')){
      $pezzo  = $mossa[0];
      $colore = $mossa[1];
      $da_i = (int)$mossa[2];
      $da_j = (int)$mossa[3];
      $a_i = (int)$mossa[4];
      $a_j = (int)$mossa[5];
      unset($pezzi[$da_i][$da_j]);
      $pezzi[$a_i][$a_j] = ['nome' => $pezzo, 'colore' => $colore];
    }
    $giocatore = 'g';
    $giocatore = array_key_exists($giocatore,$_GET) ? $_GET[$giocatore] : 's';
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
        width:  98%;
        height: 98%;
      }
      select {
        display: block;
      }
    </style>
  </head>
  <body>
    <select id='htmlGiocatore'>
      <option value='s'>Spettatore</option>
      <option value='B'>Bianco</option>
      <option value='N'>Nero</option>
    </select>
    <canvas id=c></canvas>
    <pre id='data'></pre>
    <script>

    function addPezzo(i,j,c,n) {
      if(!(i in g.pezzi)){
        g.pezzi[i] = {};
      }
      g.pezzi[i][j] = {colore:c,nome:n};
    }

    const g = function init(){
    const latoCanvas = window.innerHeight - 24;
    c.height = latoCanvas;
    c.width  = latoCanvas;
    c.style.width  = latoCanvas + 'px';
    c.style.height = latoCanvas + 'px';

      var g = {};
      g.debug = 1;
      g.c    = c;
      g.ctx  = c.getContext("2d");
      g.xpad =  60;
      g.ypad = -60;
      g.latoEsagono    = 20;
      g.latoEsagonoSin = g.latoEsagono * 1/2;
      g.latoEsagonoCos = g.latoEsagono * Math.sqrt(3)/2;
      g.pezzi = <?php echo json_encode($pezzi,JSON_FORCE_OBJECT); ?>;
      g.giocatore = htmlGiocatore;
      g.giocatore.value = <?php echo json_encode($giocatore); ?>;
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
    

    function selezionaPezzo(x,y){
      function cellaFuoriTavola(i,j){
          return 10 < j || 10 < i || j < 0 || i < 0 || (j < 5 && (i < 5 - j)) || (5 < j && ((15 - j) < i));
      }
      function cellaLibera(i,j){
        return !(i in g.pezzi) || !(j in g.pezzi[i]);
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
      function coloraCellaMovimento([i,j]){
        const cellaOccupata = !cellaLibera(i,j)
        if (cellaFuoriTavola(i,j) || (cellaOccupata && g.pezzi[i][j].colore == pezzo.colore)) {
          return 0;
        }
        if (cellaOccupata) {
          coloraBordoEsagono(i,j, '#cc0000');
          return 0;
        } 
        coloraBordoEsagono(i,j, '#cccc00');
        return 1;
      }
      function applicaMovimenti(i,j,movimenti){
        for(var k = 1; k < 64; k++) {
          var movimentiValidi = 0;
          for(var loopCounter = 0; loopCounter < movimenti.length; loopCounter++){
            const m = movimenti[loopCounter];
            const p = m.p && coloraCellaMovimento(m.m(i,j,k*m.d));
            movimenti[loopCounter].p = p;
            movimentiValidi += p;
          }
          if (!movimentiValidi) {
            return;
          }
        }
      }

      const [i,j] = idxByPos(x,y);

      if(cellaFuoriTavola(i,j) || cellaLibera(i,j)) {
        return;
      }

      const pezzo = g.pezzi[i][j];

      if(g.giocatore.value != pezzo.colore){
        return;
      }
      
      coloraBordoEsagono(i,j,'#6688ccff');

      switch (pezzo.nome) {

        case 'P': {
          const posizioniInizialiPedone =
            { 'B' : { "1" : 10, "2" : 9, "3" : 8, "4" : 7, "5" : 6, "6" : 6, "7" : 6, "8" : 6, "9" : 6 }
            , 'N' : { "1" :  4, "2" : 4, "3" : 4, "4" : 4, "5" : 4, "6" : 3, "7" : 2, "8" : 1, "9" : 0 }
            };
          const celleMovibiliPedone =
            { 'B' : [ { i : 0, j : -1 }, { i : 0, j : -2 } ]
            , 'N' : [ { i : 0, j : +1 }, { i : 0, j : +2 } ]
            };
          const celleMangiabiliPedone =
            { 'B' : [ { i : -1, j : 0 },{ i : +1, j : -1 } ]
            , 'N' : [ { i : +1, j : 0 },{ i : -1, j : +1 } ]
            };
          const posizioniIniziali = posizioniInizialiPedone[pezzo.colore];
          const celleMovibili     = celleMovibiliPedone[pezzo.colore];
          const celleMangiabili   = celleMangiabiliPedone[pezzo.colore];
          for(const o of celleMovibili){
            const i0 = i + o.i;
            const j0 = j + o.j;
            if (!cellaLibera(i0,j0) || cellaFuoriTavola(i0,j0)) {
              break;
            }
            coloraBordoEsagono(i0,j0, '#cccc00');
          }
          for(const o of celleMangiabili){
            const i0 = i + o.i;
            const j0 = j + o.j;
            if (!cellaLibera(i0,j0) && g.pezzi[i0][j0].colore != pezzo.colore) {
              coloraBordoEsagono(i0,j0, '#cc0000');
            }
          }
        } break;

        case 'A': {
          const movimenti =
            [ { p : 1, d : -1, m : spostaDiagonaleOrizzontale }
            , { p : 1, d : +1, m : spostaDiagonaleOrizzontale }
            , { p : 1, d : -1, m : spostaDiagonaleAscendente  }
            , { p : 1, d : +1, m : spostaDiagonaleAscendente  }
            , { p : 1, d : -1, m : spostaDiagonaleDiscendente }
            , { p : 1, d : +1, m : spostaDiagonaleDiscendente }
            ]
            applicaMovimenti(i,j,movimenti);
        } break;

        case 'T': {
          const movimenti =
            [ { p : 1, d : -1, m : spostaVerticale   }
            , { p : 1, d : +1, m : spostaVerticale   }
            , { p : 1, d : -1, m : spostaAscendente  }
            , { p : 1, d : +1, m : spostaAscendente  }
            , { p : 1, d : -1, m : spostaDiscendente }
            , { p : 1, d : +1, m : spostaDiscendente }
            ]
            applicaMovimenti(i,j,movimenti);
        } break;

        case 'D': {
          const movimenti =
            [ { p : 1, d : -1, m : spostaVerticale            }
            , { p : 1, d : +1, m : spostaVerticale            }
            , { p : 1, d : -1, m : spostaAscendente           }
            , { p : 1, d : +1, m : spostaAscendente           }
            , { p : 1, d : -1, m : spostaDiscendente          }
            , { p : 1, d : +1, m : spostaDiscendente          }
            , { p : 1, d : -1, m : spostaDiagonaleOrizzontale }
            , { p : 1, d : +1, m : spostaDiagonaleOrizzontale }
            , { p : 1, d : -1, m : spostaDiagonaleAscendente  }
            , { p : 1, d : +1, m : spostaDiagonaleAscendente  }
            , { p : 1, d : -1, m : spostaDiagonaleDiscendente }
            , { p : 1, d : +1, m : spostaDiagonaleDiscendente }
            ]
            applicaMovimenti(i,j,movimenti);
        } break;

        case 'R': {
          const movimenti =
            [ { p : 1, d : -1, m : spostaVerticale            }
            , { p : 1, d : +1, m : spostaVerticale            }
            , { p : 1, d : -1, m : spostaAscendente           }
            , { p : 1, d : +1, m : spostaAscendente           }
            , { p : 1, d : -1, m : spostaDiscendente          }
            , { p : 1, d : +1, m : spostaDiscendente          }
            , { p : 1, d : -1, m : spostaDiagonaleOrizzontale }
            , { p : 1, d : +1, m : spostaDiagonaleOrizzontale }
            , { p : 1, d : -1, m : spostaDiagonaleAscendente  }
            , { p : 1, d : +1, m : spostaDiagonaleAscendente  }
            , { p : 1, d : -1, m : spostaDiagonaleDiscendente }
            , { p : 1, d : +1, m : spostaDiagonaleDiscendente }
            ]
            function applicaMovimentiRe(m){
              const cella = m.m(i,j,m.d);
              if(1){
                coloraCellaMovimento(cella);
              }
            }
            movimenti.forEach(applicaMovimentiRe);
        } break;

        case 'C': {
            coloraCellaMovimento([i - 1,j - 2]);
            coloraCellaMovimento([i + 1,j - 3]);
            coloraCellaMovimento([i + 2,j - 3]);
            coloraCellaMovimento([i + 3,j - 2]);
            coloraCellaMovimento([i + 3,j - 1]);
            coloraCellaMovimento([i + 2,j + 1]);
            coloraCellaMovimento([i + 1,j + 2]);
            coloraCellaMovimento([i - 1,j + 3]);
            coloraCellaMovimento([i - 2,j + 3]);
            coloraCellaMovimento([i - 3,j + 2]);
            coloraCellaMovimento([i - 3,j + 1]);
            coloraCellaMovimento([i - 2,j - 1]);
        } break;

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

c.addEventListener('mousedown', function(e) {
    const rect = this.getBoundingClientRect();
    const x = event.clientX - rect.left;
    const y = event.clientY - rect.top;
    drawScacchiera();
    drawPezzi();
    selezionaPezzo(x,y);
})


    function drawPezzi(){
      const dimensioneTesto = (g.latoEsagonoCos * 2);
      const allineamentoX = 0;
      const allineamentoY = -8;
      function disegnaPezzo(i,j,pezzo){
        const [x,y] = posByIdx(i,j);
        
        g.ctx.font = 'bold ' + dimensioneTesto + 'px monospace';
        if ('B' == pezzo.colore) {
          // BIANCO
          g.ctx.fillStyle   = '#dddddd';
          g.ctx.strokeStyle = '#222222';
        } else {
          // NERO
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

    drawScacchiera();
    drawPezzi();

    </script>
<?php

  } break;
}

