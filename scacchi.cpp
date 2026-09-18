#include <linux/tcp.h>
/*
    sha1.hpp - source code of

    ============
    SHA-1 in C++
    ============

    100% Public Domain.

    Original C Code
        -- Steve Reid <steve@edmweb.com>
    Small changes to fit into bglibs
        -- Bruce Guenter <bruce@untroubled.org>
    Translation to simpler C++ Code
        -- Volker Diels-Grabsch <v@njh.eu>
    Safety fixes
        -- Eugene Hopkinson <slowriot at voxelstorm dot com>
    Header-only library
        -- Zlatko Michailov <zlatko@michailov.org>
*/

#ifndef SHA1_HPP
#define SHA1_HPP


#include <cstdint>
#include <fstream>
#include <iomanip>
#include <iostream>
#include <sstream>
#include <string>


class SHA1
{
public:
    SHA1();
    void update(const std::string &s);
    void update(std::istream &is);
    std::string final();
    static std::string from_file(const std::string &filename);

public:
    uint32_t digest[5];
    std::string buffer;
    uint64_t transforms;
};


static const size_t BLOCK_INTS = 16;  /* number of 32bit integers per SHA1 block */
static const size_t BLOCK_BYTES = BLOCK_INTS * 4;


inline static void reset(uint32_t digest[], std::string &buffer, uint64_t &transforms)
{
    /* SHA1 initialization constants */
    digest[0] = 0x67452301;
    digest[1] = 0xefcdab89;
    digest[2] = 0x98badcfe;
    digest[3] = 0x10325476;
    digest[4] = 0xc3d2e1f0;

    /* Reset counters */
    buffer = "";
    transforms = 0;
}


inline static uint32_t rol(const uint32_t value, const size_t bits)
{
    return (value << bits) | (value >> (32 - bits));
}


inline static uint32_t blk(const uint32_t block[BLOCK_INTS], const size_t i)
{
    return rol(block[(i+13)&15] ^ block[(i+8)&15] ^ block[(i+2)&15] ^ block[i], 1);
}


/*
 * (R0+R1), R2, R3, R4 are the different operations used in SHA1
 */

inline static void R0(const uint32_t block[BLOCK_INTS], const uint32_t v, uint32_t &w, const uint32_t x, const uint32_t y, uint32_t &z, const size_t i)
{
    z += ((w&(x^y))^y) + block[i] + 0x5a827999 + rol(v, 5);
    w = rol(w, 30);
}


inline static void R1(uint32_t block[BLOCK_INTS], const uint32_t v, uint32_t &w, const uint32_t x, const uint32_t y, uint32_t &z, const size_t i)
{
    block[i] = blk(block, i);
    z += ((w&(x^y))^y) + block[i] + 0x5a827999 + rol(v, 5);
    w = rol(w, 30);
}


inline static void R2(uint32_t block[BLOCK_INTS], const uint32_t v, uint32_t &w, const uint32_t x, const uint32_t y, uint32_t &z, const size_t i)
{
    block[i] = blk(block, i);
    z += (w^x^y) + block[i] + 0x6ed9eba1 + rol(v, 5);
    w = rol(w, 30);
}


inline static void R3(uint32_t block[BLOCK_INTS], const uint32_t v, uint32_t &w, const uint32_t x, const uint32_t y, uint32_t &z, const size_t i)
{
    block[i] = blk(block, i);
    z += (((w|x)&y)|(w&x)) + block[i] + 0x8f1bbcdc + rol(v, 5);
    w = rol(w, 30);
}


inline static void R4(uint32_t block[BLOCK_INTS], const uint32_t v, uint32_t &w, const uint32_t x, const uint32_t y, uint32_t &z, const size_t i)
{
    block[i] = blk(block, i);
    z += (w^x^y) + block[i] + 0xca62c1d6 + rol(v, 5);
    w = rol(w, 30);
}


/*
 * Hash a single 512-bit block. This is the core of the algorithm.
 */

inline static void transform(uint32_t digest[], uint32_t block[BLOCK_INTS], uint64_t &transforms)
{
    /* Copy digest[] to working vars */
    uint32_t a = digest[0];
    uint32_t b = digest[1];
    uint32_t c = digest[2];
    uint32_t d = digest[3];
    uint32_t e = digest[4];

    /* 4 rounds of 20 operations each. Loop unrolled. */
    R0(block, a, b, c, d, e,  0);
    R0(block, e, a, b, c, d,  1);
    R0(block, d, e, a, b, c,  2);
    R0(block, c, d, e, a, b,  3);
    R0(block, b, c, d, e, a,  4);
    R0(block, a, b, c, d, e,  5);
    R0(block, e, a, b, c, d,  6);
    R0(block, d, e, a, b, c,  7);
    R0(block, c, d, e, a, b,  8);
    R0(block, b, c, d, e, a,  9);
    R0(block, a, b, c, d, e, 10);
    R0(block, e, a, b, c, d, 11);
    R0(block, d, e, a, b, c, 12);
    R0(block, c, d, e, a, b, 13);
    R0(block, b, c, d, e, a, 14);
    R0(block, a, b, c, d, e, 15);
    R1(block, e, a, b, c, d,  0);
    R1(block, d, e, a, b, c,  1);
    R1(block, c, d, e, a, b,  2);
    R1(block, b, c, d, e, a,  3);
    R2(block, a, b, c, d, e,  4);
    R2(block, e, a, b, c, d,  5);
    R2(block, d, e, a, b, c,  6);
    R2(block, c, d, e, a, b,  7);
    R2(block, b, c, d, e, a,  8);
    R2(block, a, b, c, d, e,  9);
    R2(block, e, a, b, c, d, 10);
    R2(block, d, e, a, b, c, 11);
    R2(block, c, d, e, a, b, 12);
    R2(block, b, c, d, e, a, 13);
    R2(block, a, b, c, d, e, 14);
    R2(block, e, a, b, c, d, 15);
    R2(block, d, e, a, b, c,  0);
    R2(block, c, d, e, a, b,  1);
    R2(block, b, c, d, e, a,  2);
    R2(block, a, b, c, d, e,  3);
    R2(block, e, a, b, c, d,  4);
    R2(block, d, e, a, b, c,  5);
    R2(block, c, d, e, a, b,  6);
    R2(block, b, c, d, e, a,  7);
    R3(block, a, b, c, d, e,  8);
    R3(block, e, a, b, c, d,  9);
    R3(block, d, e, a, b, c, 10);
    R3(block, c, d, e, a, b, 11);
    R3(block, b, c, d, e, a, 12);
    R3(block, a, b, c, d, e, 13);
    R3(block, e, a, b, c, d, 14);
    R3(block, d, e, a, b, c, 15);
    R3(block, c, d, e, a, b,  0);
    R3(block, b, c, d, e, a,  1);
    R3(block, a, b, c, d, e,  2);
    R3(block, e, a, b, c, d,  3);
    R3(block, d, e, a, b, c,  4);
    R3(block, c, d, e, a, b,  5);
    R3(block, b, c, d, e, a,  6);
    R3(block, a, b, c, d, e,  7);
    R3(block, e, a, b, c, d,  8);
    R3(block, d, e, a, b, c,  9);
    R3(block, c, d, e, a, b, 10);
    R3(block, b, c, d, e, a, 11);
    R4(block, a, b, c, d, e, 12);
    R4(block, e, a, b, c, d, 13);
    R4(block, d, e, a, b, c, 14);
    R4(block, c, d, e, a, b, 15);
    R4(block, b, c, d, e, a,  0);
    R4(block, a, b, c, d, e,  1);
    R4(block, e, a, b, c, d,  2);
    R4(block, d, e, a, b, c,  3);
    R4(block, c, d, e, a, b,  4);
    R4(block, b, c, d, e, a,  5);
    R4(block, a, b, c, d, e,  6);
    R4(block, e, a, b, c, d,  7);
    R4(block, d, e, a, b, c,  8);
    R4(block, c, d, e, a, b,  9);
    R4(block, b, c, d, e, a, 10);
    R4(block, a, b, c, d, e, 11);
    R4(block, e, a, b, c, d, 12);
    R4(block, d, e, a, b, c, 13);
    R4(block, c, d, e, a, b, 14);
    R4(block, b, c, d, e, a, 15);

    /* Add the working vars back into digest[] */
    digest[0] += a;
    digest[1] += b;
    digest[2] += c;
    digest[3] += d;
    digest[4] += e;

    /* Count the number of transformations */
    transforms++;
}


inline static void buffer_to_block(const std::string &buffer, uint32_t block[BLOCK_INTS])
{
    /* Convert the std::string (byte buffer) to a uint32_t array (MSB) */
    for (size_t i = 0; i < BLOCK_INTS; i++)
    {
        block[i] = (buffer[4*i+3] & 0xff)
                   | (buffer[4*i+2] & 0xff)<<8
                   | (buffer[4*i+1] & 0xff)<<16
                   | (buffer[4*i+0] & 0xff)<<24;
    }
}


inline SHA1::SHA1()
{
    reset(digest, buffer, transforms);
}


inline void SHA1::update(const std::string &s)
{
    std::istringstream is(s);
    update(is);
}


inline void SHA1::update(std::istream &is)
{
    while (true)
    {
        char sbuf[BLOCK_BYTES];
        is.read(sbuf, BLOCK_BYTES - buffer.size());
        buffer.append(sbuf, (std::size_t)is.gcount());
        if (buffer.size() != BLOCK_BYTES)
        {
            return;
        }
        uint32_t block[BLOCK_INTS];
        buffer_to_block(buffer, block);
        transform(digest, block, transforms);
        buffer.clear();
    }
}


/*
 * Add padding and return the message digest.
 */

inline std::string SHA1::final()
{
    /* Total number of hashed bits */
    uint64_t total_bits = (transforms*BLOCK_BYTES + buffer.size()) * 8;

    /* Padding */
    buffer += (char)0x80;
    size_t orig_size = buffer.size();
    while (buffer.size() < BLOCK_BYTES)
    {
        buffer += (char)0x00;
    }

    uint32_t block[BLOCK_INTS];
    buffer_to_block(buffer, block);

    if (orig_size > BLOCK_BYTES - 8)
    {
        transform(digest, block, transforms);
        for (size_t i = 0; i < BLOCK_INTS - 2; i++)
        {
            block[i] = 0;
        }
    }

    /* Append total_bits, split this uint64_t into two uint32_t */
    block[BLOCK_INTS - 1] = (uint32_t)total_bits;
    block[BLOCK_INTS - 2] = (uint32_t)(total_bits >> 32);
    transform(digest, block, transforms);

    /* Hex std::string */
    std::ostringstream result;
    for (size_t i = 0; i < sizeof(digest) / sizeof(digest[0]); i++)
    {
        result << std::hex << std::setfill('0') << std::setw(8);
        result << digest[i];
    }

    /* Reset for next run */
    //reset(digest, buffer, transforms);

    return result.str();
}


inline std::string SHA1::from_file(const std::string &filename)
{
    std::ifstream stream(filename.c_str(), std::ios::binary);
    SHA1 checksum;
    checksum.update(stream);
    return checksum.final();
}


#endif /* SHA1_HPP */

// SCACCHI ESAGONALI

#include <stdio.h>
#include <sys/socket.h>
#include <arpa/inet.h>
#include <errno.h>
#include <csignal>
#include <dirent.h>
#include <filesystem>
#include <stdint.h>
#include <string.h>

#define send_lit(s,str) send(s,str,sizeof(str)-1,0);

bool keepAlive = true;

struct Socket {
  int fd;

  Socket() : fd(-1) {}
  Socket(const int& a) : fd(a) {}
  int& operator =(const int& a) { return fd = a; }
  operator int&() { return fd; }
  int release(const int& b) { int a = fd; fd = b; return a; }

  ~Socket() {
    if (-1 != fd) {
      printf("[II] :: shutting down socket (%6d)\n", fd);
      shutdown(fd,SHUT_RDWR);
      fd = -1;
    }
  }

};

int strpos(char * a, char * b){
  int i = 0;
  while(a[i]){
    if (a[i] == b[0]) {
      for (int j = 0; a[i] && b[j]; j++){
        if (!(a[i+j]) || a[i+j] != b[j]) {
          goto SKIP;
        }
      }
      goto EXIT;
    }
SKIP:
    i++;
  }
  i = -1;
EXIT:
  return i;
}

char condizioni_iniziali[] = R"COND_INIZ(
PBBKBK PBCJCJ PBDIDI PBEHEH PBFGFG PBGGGG PBHGHG PBIGIG PBJGJG
PNBEBE PNCECE PNDEDE PNEEEE PNFEFE PNGDGD PNHCHC PNIBIB PNJAJA
ABFKFK ABFJFJ ABFIFI
ANFAFA ANFBFB ANFCFC
TBCKCK TBIHIH
TNCDCD TNIAIA
CBDKDK CBHIHI
CNDCDC CNHAHA
DBEKEK
DNEBEB
RBGJGJ
RNGAGA
)COND_INIZ";

char peji_ie_begin[] = R"PEJI_IE(HTTP/1.1 200 OK
Content-type: text/html


<!DOCTYPE html>
<html>
  <head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
      html, body, div { margin: 0px; padding: 0px; }
      div.wrap { border: 1px solid black; margin: 20px; padding: 20px; }
      div.giocaCome { display:flex; margin: 10px; }
      div.giocaCome a { display:block; width: 100px; margin: 10px; text-align: center; }
    </style>
  </head>
  <body>
    <h1> Scacchi Esagonali </h1>
    <a href='N'>Crea una nuova partita</a>
    <h2> Partite attive </h2>
)PEJI_IE";
char peji_ie_game[] = "<div class='wrap'><h3>_</h3><div class='giocaCome'><a href='P_B'>Gioca come bianco</a><div>&lt;--&gt;</div><a href='P_N'>Gioca come nero</a></div></div>\n";
#define peji_ie_name_0  22
#define peji_ie_name_1  61
#define peji_ie_name_2 117
char peji_ie_end[] = R"PEJI_IE(
  </body>
</html>
)PEJI_IE";

char peji_gemu[] = R"PEJI_GEMU(HTTP/1.1 200 OK
Content-type: text/html

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
  const gUrl = window.location.pathname;
  const gPartita = gUrl[2];
  var   gColoreGiocatore = gUrl[3];
  </script>
      <div class=tavola>
      <canvas class=tavola width=420 height=420 id=cScacchiera></canvas>
      <canvas class=tavola width=420 height=420 id=cPezzi     ></canvas>
      <canvas class=tavola width=420 height=420 id=c          ></canvas>
      </div>
      <form>
        <input type=text readonly name="mossa"   id=mossa pattern="M[A-Z][PTCADR][BN][A-Z][A-Z][A-Z][A-Z]" value="">
        <script>
          const mossaChrArr = ['M',gPartita,' ',' ',' ',' ',' ',' '];
          mossa.value = mossaChrArr.join('');
          function setL(i,o){ mossaChrArr[2+i] = o.innerHTML; mossa.value = mossaChrArr.join(''); }
          function setN(i,n){ mossaChrArr[2+i] = String.fromCharCode(65 + n); mossa.value = mossaChrArr.join(''); }
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
            const url = "/M"+gPartita+mossa;
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
              if (p1 in gPezzi && gPezzi[p1].at(1) == gPezzi[p0].at(1)) { break; }
              if (p0 in pezziADifesa && !pezziADifesa[p0].includes(p1)) { continue; }
              if (1 == gPezziScaccanti.length && !pezziScaccanti[0].includes(p1)) { continue; }
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

      httpGet("/L"+gPartita,function (txt) {
        cronologia.value = txt;
        eseguiMosse(txt);
        updateMovimenti();
        drawPezzi(gPezziCtx);
        coloraUltimaMossa();
      });

  var gRemoteAddr = window.location.origin;
  gRemoteAddr = gRemoteAddr.substr(gRemoteAddr.indexOf("://")+3);
  var gWs = new WebSocket("ws://"+gRemoteAddr);
  gWs.onopen  = console.log;
  gWs.onerror = console.log;
  gWs.onclose = console.log;
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
)PEJI_GEMU";

Socket websocks[64];
char websocks_idp[64];

void handleRequest(Socket& s, char * msg){
  int req = 1 + strpos(msg, (char *)"/");
  int size = strpos(&msg[req],(char *)" ");
  char req_name = msg[req];
  printf("[II] :: Req :: '%c'\n",req_name);
  if (0 == size) {
    send_lit(s,peji_ie_begin);
    {
      char name = 'A';
      DIR * dp = opendir("./");
      struct dirent* de = 0;
      while(0 != (de = readdir(dp))){
        if ('.' == de->d_name[0]) { continue; }
        peji_ie_game[peji_ie_name_0] = name;
        peji_ie_game[peji_ie_name_1] = name;
        peji_ie_game[peji_ie_name_2] = name;
        send_lit(s,peji_ie_game);
        name++;
      }
      closedir(dp);
    }
    send_lit(s,peji_ie_end);
  }else {
    switch (req_name) {
      case 'N':{
        char newgame[2] = "A";
        DIR * dp = opendir("./");
        struct dirent* de = 0;
        while(0 != (de = readdir(dp))){
          if ('.' == de->d_name[0]) { continue; }
          newgame[0]++;
        }
        closedir(dp);
        FILE * fp = fopen(newgame,"wb");
        fwrite(condizioni_iniziali+1,sizeof(condizioni_iniziali)-2,1,fp);
        fclose(fp);
        send_lit(s,"HTTP/1.1 301 OK\nLocation: /\n\n");
      } break;
      case 'P':{
        send_lit(s,peji_gemu);
      } break;
      case 'L':{
        char name[] = "_";
        name[0]=msg[req+1];
        FILE * p = fopen(name,"rb");
        int len = fread(msg,1,1<<16,p);
        fclose(p);
        send_lit(s,"HTTP/1.1 200 OK\nContent-Type: text/plain\n\n");
        send(s,msg,len,0);
      } break;
      case 'U':{
        char name[] = "_";
        name[0]=msg[req+1];
        FILE * p = fopen(name,"rb");
        fseek(p,-7,SEEK_END);
        int len = fread(msg,1,6,p);
        fclose(p);
        send_lit(s,"HTTP/1.1 200 OK\nContent-Type: text/plain\n\n");
        send(s,msg,len,0);
      } break;
      case 'M':{
        char name[] = "_";
        name[0]=msg[req+1];
        FILE * p = fopen(name,"ab");
        fwrite(&msg[req+2],6,1,p);
        fputc('\n',p);
        fclose(p);
        p = fopen(name,"rb");
        fseek(p,-7,SEEK_END);
        char move[9] = "\x81""\x06";
        int len = fread(move+2,1,6,p);
        fclose(p);
        send_lit(s,"HTTP/1.1 200 OK\nContent-Type: text/plain\n\n");
        send(s,move+2,len,0);
        for(int i = 0; i < 64; i++){
          if(-1 != (int)websocks[i]){
            errno = 0;
            printf("[II] :: echoing move to :: %6s :: %6d ",move+2,(int)websocks[i]);
            len = send(websocks[i],move,8,0);
            printf(":: ret = %6d :: errno = %6d\n",len, errno);
          }
        }
      } break;
      default:
        printf("%s\n",msg);
        send_lit(s,"HTTP/1.1 200 OK\nContent-Type: text/plain\n\n");
        send(s,&msg[req],size,0);
      break;
    }
  }
}

static char const secwebsocketkey[] = "Sec-WebSocket-Key: ";
static char const guid[] = "258EAFA5-E914-47DA-95CA-C5AB0DC85B11";
static char const base64table[65] = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";

void base64_encode(uint8_t * a, char * b, int n){
  uint32_t c;
  int i = 0;
  int j = 0;
  int k = 0;
  while (i < n) {
    c = 0;
    c |= (uint32_t)a[i++] << 16;
    if (!(i < n)) {
      b[j++] = base64table[(c >> 18) & 0x3f];
      b[j++] = base64table[(c >> 12) & 0x3f];
      b[j++] = '=';
      b[j++] = '=';
      break;
    }
    c |= (uint32_t)a[i++] << 8;
    if (!(i < n)) {
      b[j++] = base64table[(c >> 18) & 0x3f];
      b[j++] = base64table[(c >> 12) & 0x3f];
      b[j++] = base64table[(c >>  6) & 0x3f];
      b[j++] = '=';
      break;
    }
    c |= (uint32_t)a[i++];
    b[j++] = base64table[(c >> 18) & 0x3f];
    b[j++] = base64table[(c >> 12) & 0x3f];
    b[j++] = base64table[(c >>  6) & 0x3f];
    b[j++] = base64table[(c >>  0) & 0x3f];
  }
}

void handleWebSocket(Socket& s,char * msg, int p){
  printf("[II] :: WbS\n");
  SHA1 sha1;
  auto key = std::string(msg + p + sizeof(secwebsocketkey)-1,24);
  sha1.update(key);
  sha1.update(std::string(guid,sizeof(guid)-1));
  sha1.final();
  uint8_t sha1accept[20] = {0};
  for (int i = 0; i < 5; i++) {
    sha1accept[i * 4 + 0] = (sha1.digest[i] >> 24) & 0xff;
    sha1accept[i * 4 + 1] = (sha1.digest[i] >> 16) & 0xff;
    sha1accept[i * 4 + 2] = (sha1.digest[i] >>  8) & 0xff;
    sha1accept[i * 4 + 3] = (sha1.digest[i] >>  0) & 0xff;
  }
  char websockaccept[] = "HTTP/1.1 101 Switching Protocols\r\nUpgrade: websocket\r\nConnection: Upgrade\r\nSec-WebSocket-Accept: ____________________________\r\n\r\n";
  int resp_begin = strpos(websockaccept,(char*)"_");
  base64_encode(sha1accept,&websockaccept[resp_begin],20);
  send_lit(s,websockaccept);
  char id_partita = msg[2 + strpos(msg,(char*)"/")];
  for (int i = 0; i < 64; i++) {
    if (-1 == websocks[i]) {
      int flag = 1;
      setsockopt(websocks[i], IPPROTO_TCP, TCP_NODELAY, (char *) &flag, sizeof(flag));
      websocks[i] = s.release(-1);
      websocks_idp[i] = id_partita;
      break;
    }
  }
}

Socket RequestHandler;
void closeSocket(int sig){
  keepAlive = false;
  Socket s = RequestHandler.release(-1);
}
int main(int argc, char * argv[]){
  int port = atoi(argv[1]);
  std::filesystem::current_path("./.partite");
  std::signal(SIGINT, closeSocket);
  // std::signal(SIGTERM, closeSocket);
  Socket& s = RequestHandler;
  s = socket(AF_INET,SOCK_STREAM, 0);
  struct sockaddr_in addr = {
    .sin_family = AF_INET,
    .sin_port = htons(port),
    .sin_addr = {.s_addr = htonl(0x00000000)},
  };
  int yes = 1;
  setsockopt(s, SOL_SOCKET, SO_REUSEADDR, &yes, sizeof(yes));
  while (keepAlive && -1 == bind(s, (const sockaddr*)&addr, sizeof(addr))){
    printf("[EE] :: bind :: errno = %6d :: retry\n",errno);
    sleep(6);
  }
  printf("[II] :: binded successfully on port %6d\n",port);
  listen(s, 64);
  char msg[1 << 16] = {0};
  int msglen = 0;
  while (keepAlive) {
    Socket b = accept(s,0,0);
    msglen = recv(b,msg,sizeof(msg),0);
    int p = strpos(msg,(char*)secwebsocketkey);
    if (-1 < p && p < msglen) {
      handleWebSocket(b,msg,p);
    } else {
      handleRequest(b,msg);
    }
  }
  return 0;
}
