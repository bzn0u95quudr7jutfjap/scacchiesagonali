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

    const xpad =  60;
    const ypad = -60;

    const latoEsagono = 20;
    const latoEsagonoSin = latoEsagono * 1/2;
    const latoEsagonoCos = latoEsagono * Math.sqrt(3)/2;

    const ctx = c.getContext("2d");
    const latoCanvas = window.innerHeight - 24;
    c.height = latoCanvas;
    c.width  = latoCanvas;
    c.style.width  = latoCanvas + 'px';
    c.style.height = latoCanvas + 'px';

    const celle = drawScacchiera(ctx);

    console.log(celle);

    const pezzi =
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

    function posByIdx(i,j){
      const x = xpad + ((latoEsagono + latoEsagonoSin) * i) + 0;
      const y = ypad + (latoEsagonoCos * 2 * j) + (latoEsagonoCos * i);
      return [x,y];
    }

    function verticiEsagono(x,y){
      const vertici = 
        [ {x : x                            , y : y                     }
        , {x : x+latoEsagono                , y : y                     }
        , {x : x+latoEsagono+latoEsagonoSin , y : y + latoEsagonoCos    }
        , {x : x+latoEsagono                , y : y + latoEsagonoCos * 2}
        , {x : x                            , y : y + latoEsagonoCos * 2}
        , {x : x-latoEsagonoSin             , y : y + latoEsagonoCos    }
        , {x : x                            , y : y                     }
        ];
      return vertici;
    }

    function selectCellaEsagono(ctx,cella){
      const x = cella.x;
      const y = cella.y;
      ctx.beginPath();
      ctx.moveTo(x,y); 
      ctx.lineWidth = 6;
      ctx.strokeStyle = '#6688ccff';
      verticiEsagono(x,y).forEach(o => ctx.lineTo(o.x,o.y));
      ctx.closePath();
      ctx.stroke();
    }

    function drawCellaEsagonoByIdx(ctx,i,j){
      const [x,y] = posByIdx(i,j);
      ctx.beginPath();
      ctx.moveTo(x,y); 
      ctx.lineWidth = 1;
      ctx.strokeStyle = '#000000ff';
      verticiEsagono(x,y).forEach(o => ctx.lineTo(o.x,o.y));
      ctx.closePath();
      const colori = ['#000000ff','#888888ff','#ffffffff'];
      ctx.fillStyle = colori[(((1 + (-j + i))%3)+3)%3];
      ctx.fill(); 
      ctx.stroke();
      var retval = {};
      retval.i=i;
      retval.j=j;
      retval.x=x;
      retval.y=y;
      return retval;
    }

    function drawScacchiera(ctx){
      const offsetVerticale = -3;
      var celle = [];
      var celleIdx = 0;
      var i2 = 0;
      var i3 = 0;

      ctx.clearRect(0,0,c.width,c.height);
      const df = drawCellaEsagonoByIdx;
      for(var i = 0; i <  6; i++){celle[celleIdx++] = df(ctx,5 + i, 0);}
      for(var i = 0; i <  7; i++){celle[celleIdx++] = df(ctx,4 + i, 1);}
      for(var i = 0; i <  8; i++){celle[celleIdx++] = df(ctx,3 + i, 2);}
      for(var i = 0; i <  9; i++){celle[celleIdx++] = df(ctx,2 + i, 3);}
      for(var i = 0; i < 10; i++){celle[celleIdx++] = df(ctx,1 + i, 4);}
      for(var i = 0; i < 11; i++){celle[celleIdx++] = df(ctx,0 + i, 5);}
      for(var i = 0; i < 10; i++){celle[celleIdx++] = df(ctx,0 + i, 6);}
      for(var i = 0; i <  9; i++){celle[celleIdx++] = df(ctx,0 + i, 7);}
      for(var i = 0; i <  8; i++){celle[celleIdx++] = df(ctx,0 + i, 8);}
      for(var i = 0; i <  7; i++){celle[celleIdx++] = df(ctx,0 + i, 9);}
      for(var i = 0; i <  6; i++){celle[celleIdx++] = df(ctx,0 + i,10);}

      return celle;
    }

    function isCellaCoprenteInPos(cella,x,y) {
      const TODO = 0;
      if(TODO){
      const rad3 = Math.sqrt(3);

      const y1 = cella.y;
      const y2 = cella.y + 2 * latoEsagonoCos;
      const centroY = y1 <= y && y <= y2;

      const xEsagonoSinistra = cella.x + (latoEsagono * 1/2);
      const yEsagonoSinistra = cella.y + (latoEsagono * (-(rad3 / 2)));

      const xEsagonoDestra = cella.x + (latoEsagono * (1/2 - 1));
      const yEsagonoDestra = cella.y + (latoEsagono * (rad3 / 2));

      const ydb = (-rad3 * x) - (-rad3 * xdbaa - ydbaa);
      const yda = (-rad3 * x) - (-rad3 * xdaab - ydaab);
      const centroYd = ydb <= y && y <= yda;

      const yab = (rad3 * x) + (rad3 * xdaab - ydbdaabaa);
      const yaa = (rad3 * x) + (rad3 * xdbaa - ydbdaabaa);
      const centroYa = yab <= y && y <= yaa;

      console.log('y  :',y1,'<=',y,'<=',y2);
      console.log('yd :',ydb,'<=',y,'<=',yda);
      console.log('ya :',yab,'<=',y,'<=',yaa);

      return (centroY && centroYd && centroYa);
      }else{
        return cella.x < x && x < (cella.x + latoEsagono) && cella.y < y && y < (cella.y + 2 * latoEsagonoCos);
      }
    }

c.addEventListener('mousedown', function(e) {
    const rect = this.getBoundingClientRect();
    const x = event.clientX - rect.left;
    const y = event.clientY - rect.top;
    var cella = celle.filter(o => isCellaCoprenteInPos(o,x,y));
    if (1 == cella.length) {
      cella = cella[0];
      console.log('cella',cella);
      drawScacchiera(ctx);
      drawPezzi(ctx,celle,pezzi);
      selectCellaEsagono(ctx,cella);
    }
})


    function drawPezzi(ctx,pezzi){
      const dimensioneTesto = (latoEsagonoCos * 2);
      const allineamentoX = 0;
      const allineamentoY = -8;
    var deathcounter = 0;
      function disegnaPezzo(pezzo){
        const i = pezzo.i;
        const j = pezzo.j;
        const [x,y] = posByIdx(i,j);
        ctx.fillText  (pezzo.nome, x + allineamentoX, y + dimensioneTesto + allineamentoY);
        ctx.strokeText(pezzo.nome, x + allineamentoX, y + dimensioneTesto + allineamentoY);
        ctx.fill();
        ctx.stroke();
    if(0 > --deathcounter){
        console.log(pezzo,x,y);
        throw deathcounter;
    }
      }

      ctx.font = 'bold ' + dimensioneTesto + 'px monospace';

      ctx.fillStyle   = 'white';
      ctx.strokeStyle = 'black';
      pezzi.bianchi.forEach(disegnaPezzo);

      ctx.fillStyle   = 'black';
      ctx.strokeStyle = 'white';
      pezzi.neri.forEach(disegnaPezzo);

    }

    drawScacchiera(ctx);
    drawPezzi(ctx,pezzi);

    </script>
