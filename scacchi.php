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

?>
<!DOCTYPE html>
<html>
  <head>
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
    </style>
  </head>
  <body>
    <canvas id=c></canvas>
    <pre id='data'></pre>
    <script>

    const latoCanvas = window.innerHeight - 24;
    c.height = latoCanvas;
    c.width  = latoCanvas;
    c.style.width  = latoCanvas + 'px';
    c.style.height = latoCanvas + 'px';

    const g = function init(){
      var g = {};
      g.debug = 1;
      g.c    = c;
      g.ctx  = c.getContext("2d");
      g.xpad =  60;
      g.ypad = -60;
      g.latoEsagono    = 20;
      g.latoEsagonoSin = g.latoEsagono * 1/2;
      g.latoEsagonoCos = g.latoEsagono * Math.sqrt(3)/2;
      g.pezzi =
        { bianchi:
          [ { nome: 'A', i: 5, j:10 }
          , { nome: 'A', i: 5, j: 9 }
          , { nome: 'A', i: 5, j: 8 }
          , { nome: 'P', i: 1, j:10 }
          , { nome: 'P', i: 2, j: 9 }
          , { nome: 'P', i: 3, j: 8 }
          , { nome: 'P', i: 4, j: 7 }
          , { nome: 'P', i: 5, j: 6 }
          , { nome: 'P', i: 6, j: 6 }
          , { nome: 'P', i: 7, j: 6 }
          , { nome: 'P', i: 8, j: 6 }
          , { nome: 'P', i: 9, j: 6 }
          , { nome: 'T', i: 2, j:10 }
          , { nome: 'T', i: 8, j: 7 }
          , { nome: 'C', i: 3, j:10 }
          , { nome: 'C', i: 7, j: 8 }
          , { nome: 'D', i: 4, j:10 }
          , { nome: 'R', i: 6, j: 9 }
          ]
        , neri:
          [ { nome: 'P', i: 1, j: 4 }
          , { nome: 'P', i: 2, j: 4 }
          , { nome: 'P', i: 3, j: 4 }
          , { nome: 'P', i: 4, j: 4 }
          , { nome: 'P', i: 5, j: 4 }
          , { nome: 'P', i: 6, j: 3 }
          , { nome: 'P', i: 7, j: 2 }
          , { nome: 'P', i: 8, j: 1 }
          , { nome: 'P', i: 9, j: 0 }
          , { nome: 'A', i: 5, j: 0 }
          , { nome: 'A', i: 5, j: 1 }
          , { nome: 'A', i: 5, j: 2 }
          , { nome: 'T', i: 2, j: 3 }
          , { nome: 'T', i: 8, j: 0 }
          , { nome: 'C', i: 3, j: 2 }
          , { nome: 'C', i: 7, j: 0 }
          , { nome: 'D', i: 4, j: 1 }
          , { nome: 'R', i: 6, j: 0 }
          ]
        };
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


      // Coordindate dentro alla tavola
      if(10 < j || 10 < i || j < 0 || i < 0 || (j < 5 && (i < 5 - j)) || (5 < j && ((15 - j) < i))) {
        return [0,0];
      }

      const v = verticiEsagono(i,j);

      g.ctx.lineWidth = 6;
      g.ctx.strokeStyle = '#6688ccff';

      g.ctx.beginPath();
      g.ctx.moveTo(v[0].x,v[0].y); 
      v.forEach(function (o) { g.ctx.lineTo(o.x,o.y); });
      g.ctx.closePath();
      g.ctx.stroke();
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
      function disegnaPezzo(pezzo){
        g.ctx.beginPath();
        const i = pezzo.i;
        const j = pezzo.j;
        const [x,y] = posByIdx(i,j);
        g.ctx.fillText  (pezzo.nome, x + allineamentoX, y + dimensioneTesto + allineamentoY);
        g.ctx.strokeText(pezzo.nome, x + allineamentoX, y + dimensioneTesto + allineamentoY);
        g.ctx.fill();
        g.ctx.stroke();
      }

      g.ctx.font = 'bold ' + dimensioneTesto + 'px monospace';

      g.ctx.fillStyle   = 'white';
      g.ctx.strokeStyle = 'black';
      g.pezzi.bianchi.forEach(disegnaPezzo);

      g.ctx.fillStyle   = 'black';
      g.ctx.strokeStyle = 'white';
      g.pezzi.neri.forEach(disegnaPezzo);
     

      g.ctx.fillStyle   = '';

    }

    drawScacchiera();
    drawPezzi();

    </script>
