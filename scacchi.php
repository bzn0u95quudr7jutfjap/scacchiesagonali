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

    var selettoreColore = 0;

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
      var x = xpad + ((latoEsagono + latoEsagonoSin) * i) + 0;
      var y = ypad + (latoEsagonoCos * 2 * j) + (latoEsagonoCos * i);
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
      drawScacchiera(ctx);
      cella = cella[0];
      selectCellaEsagono(ctx,cella);
    }
})

    const ctx = c.getContext("2d");
    const latoCanvas = window.innerHeight - 24;
    c.height = latoCanvas;
    c.width  = latoCanvas;
    c.style.width  = latoCanvas + 'px';
    c.style.height = latoCanvas + 'px';

    const celle = drawScacchiera(ctx);

    console.log(celle);


    </script>
