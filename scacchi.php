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

    function selezionaPezzo(x,y){
      const [i,j] = idxByPos(x,y);

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

      function noOp() { return noOp; }

      function coloraCellaMovimento(i,j){
        const cellaOccupata = !cellaLibera(i,j)
        if (cellaFuoriTavola(i,j) || (cellaOccupata && g.pezzi[i][j].colore == pezzo.colore)) {
          return noOp;
        }
        if (cellaOccupata) {
          coloraBordoEsagono(i,j, '#cc0000');
          return noOp;
        } 
        coloraBordoEsagono(i,j, '#cccc00');
        return coloraCellaMovimento;
      }

      if(cellaFuoriTavola(i,j)) {
        return;
      }
      
      const pezzoAlleCoordinate = i in g.pezzi && j in g.pezzi[i];
      if (!pezzoAlleCoordinate){
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
            { 'B' :
              { "1" : 10
              , "2" :  9
              , "3" :  8
              , "4" :  7
              , "5" :  6
              , "6" :  6
              , "7" :  6
              , "8" :  6
              , "9" :  6
              }
            , 'N' :
              { "1" : 4
              , "2" : 4
              , "3" : 4
              , "4" : 4
              , "5" : 4
              , "6" : 3
              , "7" : 2
              , "8" : 1
              , "9" : 0
              }
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
          var raggiMovimento =
            [ coloraCellaMovimento
            , coloraCellaMovimento
            , coloraCellaMovimento
            , coloraCellaMovimento
            , coloraCellaMovimento
            , coloraCellaMovimento
            ]
          for(var k = 1; 1; k++){
            const i0 = i + 2 * k;
            const j0 = j - k;
            raggiMovimento[0] = raggiMovimento[0](i0,j0);
            const i1 = i - 2 * k;
            const j1 = j + k;
            raggiMovimento[1] = raggiMovimento[1](i1,j1);
            const i2 = i - k;
            const j2 = j - k;
            raggiMovimento[2] = raggiMovimento[2](i2,j2);
            const i3 = i + k;
            const j3 = j + k;
            raggiMovimento[3] = raggiMovimento[3](i3,j3);
            const i4 = i - k;
            const j4 = j + 2 * k;
            raggiMovimento[4] = raggiMovimento[4](i4,j4);
            const i5 = i + k;
            const j5 = j - 2 * k;
            raggiMovimento[5] = raggiMovimento[5](i5,j5);
            if (raggiMovimento.reduce((a,b)=>a && (b == noOp),1)) {
              break;
            }
          }
        } break;

        case 'T': {
          var raggiMovimento =
            [ coloraCellaMovimento
            , coloraCellaMovimento
            , coloraCellaMovimento
            , coloraCellaMovimento
            , coloraCellaMovimento
            , coloraCellaMovimento
            ]
          for(var k = 1; 1; k++){
            const i0 = i + k;
            const j0 = j;
            raggiMovimento[0] = raggiMovimento[0](i0,j0);
            const i1 = i - k;
            const j1 = j;
            raggiMovimento[1] = raggiMovimento[1](i1,j1);
            const i2 = i;
            const j2 = j - k;
            raggiMovimento[2] = raggiMovimento[2](i2,j2);
            const i3 = i;
            const j3 = j + k;
            raggiMovimento[3] = raggiMovimento[3](i3,j3);
            const i4 = i - k;
            const j4 = j + k;
            raggiMovimento[4] = raggiMovimento[4](i4,j4);
            const i5 = i + k;
            const j5 = j - k;
            raggiMovimento[5] = raggiMovimento[5](i5,j5);
            if (raggiMovimento.reduce((a,b)=>a && (b == noOp),1)) {
              break;
            }
          }
        } break;

        case 'R': {
            const k = 1;
            const i0 = i + k;
            const j0 = j;
            coloraCellaMovimento(i0,j0);
            const i1 = i - k;
            const j1 = j;
            coloraCellaMovimento(i1,j1);
            const i2 = i;
            const j2 = j - k;
            coloraCellaMovimento(i2,j2);
            const i3 = i;
            const j3 = j + k;
            coloraCellaMovimento(i3,j3);
            const i4 = i - k;
            const j4 = j + k;
            coloraCellaMovimento(i4,j4);
            const i5 = i + k;
            const j5 = j - k;
            coloraCellaMovimento(i5,j5);
            const i6 = i + 2 * k;
            const j6 = j - k;
            coloraCellaMovimento(i6,j6);
            const i7 = i - 2 * k;
            const j7 = j + k;
            coloraCellaMovimento(i7,j7);
            const i8 = i - k;
            const j8 = j - k;
            coloraCellaMovimento(i8,j8);
            const i9 = i + k;
            const j9 = j + k;
            coloraCellaMovimento(i9,j9);
            const iA = i - k;
            const jA = j + 2 * k;
            coloraCellaMovimento(iA,jA);
            const iB = i + k;
            const jB = j - 2 * k;
            coloraCellaMovimento(iB,jB);
        } break;

        case 'D': {
          var raggiMovimento =
            [ coloraCellaMovimento
            , coloraCellaMovimento
            , coloraCellaMovimento
            , coloraCellaMovimento
            , coloraCellaMovimento
            , coloraCellaMovimento
            , coloraCellaMovimento
            , coloraCellaMovimento
            , coloraCellaMovimento
            , coloraCellaMovimento
            , coloraCellaMovimento
            , coloraCellaMovimento
            ]
          const a = 10;
          const b = 11;
          for(var k = 1; 1; k++){
            const i0 = i + k;
            const j0 = j;
            raggiMovimento[0] = raggiMovimento[0](i0,j0);
            const i1 = i - k;
            const j1 = j;
            raggiMovimento[1] = raggiMovimento[1](i1,j1);
            const i2 = i;
            const j2 = j - k;
            raggiMovimento[2] = raggiMovimento[2](i2,j2);
            const i3 = i;
            const j3 = j + k;
            raggiMovimento[3] = raggiMovimento[3](i3,j3);
            const i4 = i - k;
            const j4 = j + k;
            raggiMovimento[4] = raggiMovimento[4](i4,j4);
            const i5 = i + k;
            const j5 = j - k;
            raggiMovimento[5] = raggiMovimento[5](i5,j5);
            const i6 = i + 2 * k;
            const j6 = j - k;
            raggiMovimento[6] = raggiMovimento[6](i6,j6);
            const i7 = i - 2 * k;
            const j7 = j + k;
            raggiMovimento[7] = raggiMovimento[7](i7,j7);
            const i8 = i - k;
            const j8 = j - k;
            raggiMovimento[8] = raggiMovimento[8](i8,j8);
            const i9 = i + k;
            const j9 = j + k;
            raggiMovimento[9] = raggiMovimento[9](i9,j9);
            const ia = i - k;
            const ja = j + 2 * k;
            raggiMovimento[a] = raggiMovimento[a](ia,ja);
            const ib = i + k;
            const jb = j - 2 * k;
            raggiMovimento[b] = raggiMovimento[b](ib,jb);
            if (raggiMovimento.reduce((a,b)=>a && (b == noOp),1)) {
              break;
            }
          }
        } break;

        case 'C': {
            coloraCellaMovimento(i - 1,j - 2);
            coloraCellaMovimento(i + 1,j - 3);
            coloraCellaMovimento(i + 2,j - 3);
            coloraCellaMovimento(i + 3,j - 2);
            coloraCellaMovimento(i + 3,j - 1);
            coloraCellaMovimento(i + 2,j + 1);
            coloraCellaMovimento(i + 1,j + 2);
            coloraCellaMovimento(i - 1,j + 3);
            coloraCellaMovimento(i - 2,j + 3);
            coloraCellaMovimento(i - 3,j + 2);
            coloraCellaMovimento(i - 3,j + 1);
            coloraCellaMovimento(i - 2,j - 1);
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

