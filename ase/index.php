<?php
/***
 * 把字符串转为比特序列
 * 字符转为32位有符号整数 toint32
 * */
class WordArray
{
    public $words;
    public $sigBytes;

    public function __construct($words1 = array(), $sigBytes1)
    {
        $this->words = $words1;
      
        $this ->sigBytes = $sigBytes1;
    }

    // function clamp() {
  //   $words[ $sigBytes >> 2] &= 0xffffffff << (32 - ($sigBytes % 4) * 8);
  //  // this.words.length = Math.ceil($sigBytes/ 4);
  // }
}

/***
 *把字符串转为比特序列
 * 字符转为32位有符号整数 toint32
 */

function convert($str)
{
    $words;
    $length = strlen($str);
    $arrayStr = str_split($str);
    $unicodestr = '';
    for ($j = 0; $j < $length; ++$j) {
        $unicodestr = base_convert(bin2hex(iconv('utf-8', 'UCS-4', $arrayStr[$j])), 16, 10);
        $words[$j >> 2] |= rr(($unicodestr & 0xff) , (24 - ($j % 4) * 8));

    }
    return  new WordArray($words, $length);
}

function stringify($wordArray)
{
    // Shortcuts
    $words = $wordArray->words;
    $sigBytes = $wordArray->sigBytes;
    $str;
    // Convert
    $latin1Chars = array();
    for ($i = 0; $i < $sigBytes; ++$i) {
        $bite = ($words[$i >> 2] >> (24 - ($i % 4) * 8)) & 0xff;
        //$str = iconv('UCS-4', 'utf-8', $bite);
       // $bite = "\u65b0\u6d6a\u5fae\u535a";
       $bite = base_convert($bite,10,16);
       if(strlen($bite)<4)
       {
        $bite=str_pad($bite,4,"0",STR_PAD_LEFT);
       }
        $bite = '\u'.$bite;
        $json = '{"str":"'.$bite.'"}';
        $array = json_decode($json,true);
        $str .= $array['str'];
    }
   return $str;
}

$SBOX ;
$INV_SBOX = array();
$SUB_MIX_0 = array();
$SUB_MIX_1 = array();
$SUB_MIX_2 = array();
$SUB_MIX_3 = array();
$INV_SUB_MIX_0 = array();
$INV_SUB_MIX_1 = array();
$INV_SUB_MIX_2 = array();
$INV_SUB_MIX_3 = array();
$M = array(); // 明文
$keySchedule = array();
$invKeySchedule = array();
$nRounds;

function uright($v, $n)
{
  
  return ($v & 0xFFFFFFFF) >> ($n & 0x1F);
}
//<<
function rr($v, $n)
{
  // $v = dechex($v);
  // $n = dechex($n);
  $t = ($v & 0xFFFFFFFF) << ($n & 0x1F);
 $a = $t & 0x80000000 ? $t | 0xFFFFFFFF00000000 : $t & 0xFFFFFFFF;
  return $t & 0x80000000 ? $t | 0xFFFFFFFF00000000 : $t & 0xFFFFFFFF;
}
// /**
//  * 初始化S盒
//  */
function ArrayTest() {
  $d = array();
  for ($i = 0; $i < 256; $i++) {
    if ($i < 128) {
      $d[$i] = rr($i,1);
    } else {
      $d[$i] = rr($i,1) ^ 0x11b;
    }
  }

  $x = 0;
  $xi = 0;
  for ($i1 = 0; $i1 < 256; $i1++) {
    // Compute sbox
    $sx = ($xi) ^ (rr($xi,1))^ (rr($xi,2)) ^ (rr($xi,3)) ^ (rr($xi,4));
    $sx = (uright($sx,8)) ^ ($sx & 0xff) ^ 0x63;
    $GLOBALS['SBOX'][$x] = $sx;
    $GLOBALS['INV_SBOX'][$sx] = $x;
    // Compute multiplication
    $x2 = $d[$x];
    $x4 = $d[$x2];
    $x8 = $d[$x4];

    // Compute sub bytes, mix columns tables
    $t = ($d[$sx] * 0x101) ^ ($sx * 0x1010100);
    $GLOBALS['SUB_MIX_0'][$x] = rr($t,24) | uright($t,8);
    $GLOBALS['SUB_MIX_1'][$x] =rr($t,16) | uright($t,16);
    $GLOBALS['SUB_MIX_2'][$x] = rr($t,8) | uright($t,24);
    $GLOBALS['SUB_MIX_3'][$x] = $t;

    // Compu$te inv sub by$tes, inv mix columns $tables
    $t1 = ($x8 * 0x1010101) ^ ($x4 * 0x10001) ^ ($x2 * 0x101) ^ ($x * 0x1010100);
    $GLOBALS['INV_SUB_MIX_0'][$sx] = rr($t1,24) | uright($t1,8);
    $GLOBALS['INV_SUB_MIX_1'][$sx] = rr($t1,16) | uright($t1,16);
    $GLOBALS['INV_SUB_MIX_2'][$sx] = rr($t1,8) | uright($t1,24);
    $GLOBALS['INV_SUB_MIX_3'][$sx] = $t1;

    // Compute next counter
    if (!$x) {
      $x = $xi = 1;
    } else {
      $x = $x2 ^ $d[$d[$d[$x8 ^ $x2]]];
      $xi ^= $d[$d[$xi]];
    }
  }
  echo " SUB_MIX_0";
  print_r(  $GLOBALS['SUB_MIX_0']);
}


function keyHandle($key) {
  $keyWords = $key->words;
  $keySize = $key->sigBytes / 4;
  $t;
  $RCON = [0x00, 0x01, 0x02, 0x04, 0x08, 0x10, 0x20, 0x40, 0x80, 0x1b, 0x36] ;// 轮系数
  // Compute number of rounds
  $GLOBALS['nRounds'] = $keySize + 6;

  // Compute number of key schedule rows
  $ksRows = ( $GLOBALS['nRounds'] + 1) * 4;
  // Compute key schedule
  for ($ksRow = 0; $ksRow < $ksRows; $ksRow++) {
    if ($ksRow < $keySize) {
      $GLOBALS['keySchedule'][$ksRow] = $keyWords[$ksRow];
    } else {
      $t = $GLOBALS['keySchedule'][$ksRow - 1];
      // ksrow能被4整除  ksrow的结果是 上一项和上一项-3 做G函数运算，完后做异或
      /** **G算法
       *函数G()首先将4个输入字节进行翻转，并执行一个按字节的S盒代换，
      *  最后用第一个字节与���系数Rcon进行异或运算。轮系数是一个有10个元素的一维数组，
      *  一个元素1个字节。G()函数存在的目的有两个，一是增加密钥编排中的非线性；
      *  二是消除AES中的对称性。这两种属性都是抵抗某些分组密码攻击必要的。
       */
      if (!($ksRow % $keySize)) {
        // Rot word 上一项为t
        // 循环左移8位
        $t = rr($t,8) | uright($t,24);
        $t =
         rr($GLOBALS['SBOX'][uright($t,24)],24)|
         rr($GLOBALS['SBOX'][uright($t,16)& 0xff],16) |
         rr($GLOBALS['SBOX'][uright($t,8)& 0xff],8) |
          $GLOBALS['SBOX'][$t  & 0xff];

        // Mix Rcon
      
        $t ^= rr($RCON[($ksRow / $keySize) | 0],24);
      } else if ($keySize > 6 && $ksRow % $keySize === 4) {
        // Sub word
        $t =
          rr($GLOBALS['SBOX'][uright($t,24)] ,24) |
          rr($GLOBALS['SBOX'][uright($t,16) & 0xff] , 16) |
          rr($GLOBALS['SBOX'][uright($t,8) & 0xff] , 8) |
          $GLOBALS['SBOX'][$t & 0xff];
      }

      $GLOBALS['keySchedule'][$ksRow] = $GLOBALS['keySchedule'][$ksRow - $keySize] ^ $t;
    }
  }
//   // Compute inv key schedule
  for ($invKsRow = 0; $invKsRow < $ksRows; $invKsRow++) {
    $ksRow1 = $ksRows - $invKsRow;
    $keyt1;
    if ($invKsRow % 4) {
      $keyt1 =  $GLOBALS['keySchedule'][$ksRow1];
    } else {
      $keyt1 =  $GLOBALS['keySchedule'][$ksRow1 - 4];
    }

    if ($invKsRow < 4 || $ksRow1 <= 4) {
      $GLOBALS['invKeySchedule'][$invKsRow] = $keyt1;
    } else {
      $GLOBALS['invKeySchedule'][$invKsRow] =
      ($GLOBALS['INV_SUB_MIX_0'][$GLOBALS['SBOX'][uright($keyt1,24)]]) ^
      ($GLOBALS['INV_SUB_MIX_1'][$GLOBALS['SBOX'][uright($keyt1,16) & 0xff]]) ^
      ($GLOBALS['INV_SUB_MIX_2'][$GLOBALS['SBOX'][uright($keyt1,8) & 0xff]]) ^
      ($GLOBALS['INV_SUB_MIX_3'][$GLOBALS['SBOX'][$keyt1 & 0xff]]);
    }
  }
}

// /**
//  * 加解密步骤
//  */
function doAES(
  $M,
  $keySchedule,
  $SUB_MIX_0,
  $SUB_MIX_1,
  $SUB_MIX_2,
  $SUB_MIX_3,
  $SBOX
) {
  /*
   *密钥加法
   *1.明文和密钥（子密钥K[0,3]）进行按字节异或
   *s0为对明文加密后的密文
   */
 

  $s0 = $M[0] ^ $keySchedule[0];
  $s1 = $M[1] ^ $keySchedule[1];
  $s2 = $M[2] ^ $keySchedule[2];
  $s3 = $M[3] ^ $keySchedule[3];

  
  /***
   * 2.字节代换层
   * 让输入的数据通过S_box表（s盒SBOX）完成从一个字节到另一个字节的映射
   */
  // Key schedule row counter
  $ksRow = 4;
  // Rounds 128位 nrounds是10圈 已经在keyHandle函数计算出
  echo($GLOBALS['nRounds']);

  for ($round = 1; $round < $GLOBALS['nRounds']; $round++) {
    // Shift rows, sub bytes, mix columns, add round key
    $test1 = uright($s0,24);
    $test1 = uright($s1,16) ;
    $test1 = uright($s2,8);

    $test20 =  $SUB_MIX_0[uright($s0,24)];
    $test21 =   $SUB_MIX_1[uright($s1,16) & 0xff];
    $test22 =  $SUB_MIX_2[uright($s2,8) & 0xff];
    $test23 = $SUB_MIX_3[$s3 & 0xff];
    $test24 = $keySchedule[$ksRow];
  $test3 = $test20 ^ $test21 ^ $test22 ^ $test23^$test24;
  $test4 = 2041688520 xor -56077228 xor -315833875 xor 3873845719 xor -1888639920;
    //$test3=  $keySchedule[$ksRow++];
    echo $s0;
    $t0 =
    $SUB_MIX_0[uright($s0,24)] ^
    $SUB_MIX_1[uright($s1,16) & 0xff] ^
    $SUB_MIX_2[uright($s2,8) & 0xff] ^
    $SUB_MIX_3[$s3 & 0xff] ^
    $keySchedule[$ksRow++];
    echo $s0;
    $t1 =
    $SUB_MIX_0[uright($s1,24)] ^
    $SUB_MIX_1[uright($s2,16) & 0xff] ^
    $SUB_MIX_2[uright($s3,8) & 0xff] ^
    $SUB_MIX_3[$s0 & 0xff] ^
    $keySchedule[$ksRow++];

    $t2 =
    $SUB_MIX_0[uright($s2,24)] ^
    $SUB_MIX_1[uright($s3,16) & 0xff] ^
    $SUB_MIX_2[uright($s0,8) & 0xff] ^
    $SUB_MIX_3[$s1 & 0xff] ^
    $keySchedule[$ksRow++];
    $t3 =
    $SUB_MIX_0[uright($s3,24)] ^
    $SUB_MIX_1[uright($s0,16) & 0xff] ^
    $SUB_MIX_2[uright($s1,8) & 0xff] ^
    $SUB_MIX_3[$s2 & 0xff] ^
    $keySchedule[$ksRow++];

    // Update state
    $s0 = $t0;
    $s1 = $t1;
    $s2 = $t2;
    $s3 = $t3;

   
  }
  echo("S0");
  echo($s0.','.$s1.','.$s2.','.$s3);

//   /**
//    * 3.行位移
//    * 行位移操作最为简单，它是用来将输入数据作为一个4·4的字节矩阵进行处理的
//    * 在加密时，保持矩阵的第一行不变，第二行向左移动8Bit(一个字节)、第三行向左移动2个字节、
//    * 第四行向左移动3个字节。而在解密时恰恰相反，依然保持第一行不变，
//    * 将第二行向右移动一个字节、第三行右移2个字节、第四行右移3个字节。
//    */
  $ts0 =
    (rr($SBOX[uright($s0,24)] ,24) |
      rr($SBOX[uright($s1,16) & 0xff] , 16) |
      rr($SBOX[uright($s2,8) & 0xff] , 8) |
      $SBOX[$s3 & 0xff]) ^
      $keySchedule[$ksRow++];
  $ts1 =
    (rr($SBOX[uright($s1,24)] , 24) |
      rr($SBOX[uright($s2,16) & 0xff] , 16) |
      rr($SBOX[uright($s3,8) & 0xff] , 8) |
      $SBOX[$s0 & 0xff]) ^
      $keySchedule[$ksRow++];
  $ts2 =
    (rr($SBOX[uright($s2,24)] , 24) |
      rr($SBOX[uright($s3,16) & 0xff] , 16) |
      rr($SBOX[uright($s0,8) & 0xff] , 8) |
      $SBOX[$s1 & 0xff]) ^
      $keySchedule[$ksRow++];
  $ts3 =
    (rr($SBOX[uright($s3,24)] , 24) |
      rr($SBOX[uright($s0,16) & 0xff] , 16) |
      rr($SBOX[uright($s1,8) & 0xff] , 8) |
      $SBOX[$s2 & 0xff]) ^
      $keySchedule[$ksRow++];
      $M[0] = $ts0;
      $M[1] = $ts1;
      $M[2] = $ts2;
      $M[3] = $ts3;

     return $M;
}

// /**
//  *
//  * @param {文本} p
//  *如果文本不够16位进行补全
//  */
// function messageNotEnough(p) {
//   $length = p.length
//   if (p.length < 16) {
//     p = p + Math.pow(10, 16 - p.length)
//   }
//   return {
//     text: p,
//     lengthInit: length
//   }
// }
// /**
//  *
//  * @param {文本：明文或密文} p
//  * @param {密钥} key
//  */
function enAes($p, $key) {
  // if ($p < 16) {
  //   $PObject = messageNotEnough(p)
  // }
  // 准备array 必须为第一步
  ArrayTest();
  // 准备key 得到keySchedule或invschedule
  $keyConvert = convert($key);
  keyHandle($keyConvert);
  // 执行加密步骤
  $Message = convert($p);

  $MArray = doAES(
    $Message->words,
    $GLOBALS['keySchedule'],
    $GLOBALS['SUB_MIX_0'],
    $GLOBALS['SUB_MIX_1'],
    $GLOBALS['SUB_MIX_2'],
    $GLOBALS['SUB_MIX_3'],
    $GLOBALS['SBOX']
  );
  $Message->words = $MArray;


/////////////////////////
 
  //deAes(stringify($Message), $key);

///////////////////////////

  return stringify($Message);


  
}

enAes('1234567812345678','1234567812345678');


function deAes($p, $key) {
echo("解密开始：");


  // 准备array 必须为第一步
  ArrayTest();
  // 准备key 得到keySchedule或invschedule
  keyHandle(convert($key));
  // 执行解密步骤
  $Message = convert($p);

  // Message.clamp()
  $M = $Message->words;
  // Swap 2nd and 4th rows
  $offset = 0;
  $t = $M[$offset + 1];
  $M[$offset + 1] = $M[$offset + 3];
  $M[$offset + 3] = $t;
  $M = doAES(
    $M,
    $GLOBALS['invKeySchedule'],
    $GLOBALS['INV_SUB_MIX_0'],
    $GLOBALS['INV_SUB_MIX_1'],
    $GLOBALS['INV_SUB_MIX_2'],
    $GLOBALS['INV_SUB_MIX_3'],
    $GLOBALS['INV_SBOX']
  );
  // console.log(stringify(Message))
  $t1 = $M[$offset + 1];
  $M[$offset + 1] = $M[$offset + 3];
  $M[$offset + 3] = $t1;
  $Message->words = $M;

  echo("解密：");
  print_r( stringify($Message));
}
