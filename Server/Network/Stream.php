<?php
namespace Server\Network;

/**
* @category RSPS
* @package EtherRS
* @author David Harris <lolidunno@live.co.uk>, Mitchell Murphy <mitchell@fl3x.co>
* @copyright 2013 EtherRS
* @version GIT: $Id:$
* @link https://github.com/mitchellm/EtherRS/
*/

Class Stream {
    public $currentOffset = 1, $bitPosition = 1;
    public $array = array();
    public $packetType;
    public $packetStart;

    public $SERVER_PACKET_SIZES = array(
        0, 0, 0, 0, 6, 0, 0, 0, 4, 0, 
        0, 4, 4, 0, 0, 0, 0, 0, 0, 0, 
        0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 
        0, 0, 0, 0, -2, 4, 3, 0, 0, 0, 
        0, 0, 0, 0, 5, 0, 0, 6, 0, 0, 
        10, 0, 0, -2, 0, 0, 0, 0, 0, 0, 
        -2, 1, 0, 0, 2, -2, 0, 0, 0, 0, 
        6, 3, 2, 4, 2, 4, 0, 0, 0, 4, 
        0, -2, 0, 0, 7, 2, 0, 6, 0, 0, 
        0, 0, 0, 0, 0, 0, 0, 2, 0, 1, 
        0, 2, 0, 0, -1, 4, 1, 0, 0, 0, 
        1, -1, 0, 0, 2, 0, 0, 15, 0, 0, 
        0, 4, 4, 0, 0, 0, -2, -2, 0, 0, 
        0, 0, 0, 0, 6, 0, 0, 0, 0, 0, 
        0, 0, 2, 0, 0, 0, 0, 14, 0, 0, 
        0, 4, 0, 0, 0, 0, 3, 0, 0, 0, 
        4, 0, 0, 0, 2, 0, 6, 0, 0, 0, 
        0, 3, 0, 0, 5, 0, 10, 6, 0, 0, 
        0, 0, 0, 0, 0, 2, 0, 0, -2, 3, 
        -2, 0, 0, 0, 0, 0, -1, 0, 0, 0, 
        4, 0, 0, 0, 0, 0, 3, 0, 2, 0, 
        0, 0, 0, 0, -2, 7, 0, 0, 2, 0, 
        0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 
        8, 0, 0, 0, 0, 0, 0, 0, 0, 0, 
        2, -2, 0, 0, 0, 0, 6, 0, 4, 3, 
        0, 0, 0, -1, 6, 0
    );

    public function __construct() {
        $this->array = array_fill(0, 5000, 0);
    }
    
    public function putBits($bits, $val) {
        if($bits <= 0 || $bits > 32)
            return;
            
        $bitPos = $this->bitPosition;
        $pos = $bitPos >> 3;
        $bitPos &= 0x7;
        if($bitPos != 0) {
            $n = 8 - $bitPos;
            if($n > $bits) 
                $n = $bits;
                
            $mask = (1 << $n) - 1;
            $b = $this->array[$pos] & 0xff;
            $this->array[$pos] = (($b & ($mask << $bitPos)) | ($val & $mask));
            $this->bitPosition += $n;
            
            if($n == $bits)
                return;
                
            ++$pos;
            $val >>= $n;
            $bits -= $n;
        }
        while($bits > 0) {
            $m = $bits;
            if ($m > 8)
                $m = 8;
            $this->array[$pos++] = $val;
            $val >>= $m;
            $bits -= $m;
            $this->bitPosition += $m;
        }
        return $this;
    }
    
    public function iniBitAccess() {
        $this->bitPosition = $this->currentOffset * 8;
    }
    
    public function finishBitAccess() {
        $this->currentOffset = intval(($this->bitPosition + 7) / 8);
    }
    
    public function setBitPosition($x) {
        $this->bitPosition = $x;
    }
    
    public function getBitPosition() {
        return $this->bitPosition;
    }

    public function beginPacket($isaac, $val) {
        $type = $this->SERVER_PACKET_SIZES[$val];
        $this->putHeader($isaac, $val);
        if ($type < 0)
            $type = -$type;
        else
            $type = 0;

         $this->packetType = $type;
         $this->putPacketSize($type, 0);
         $this->packetStart = $this->currentOffset;
    }

    public function putPacketSize($type, $size)
    {
        $size = intval($size);
        echo "\n Type: " . $type . " - Size: " . $size . " \n";
        if ($type == 0) //Do nothing
            ;
        else if ($size < 0 || $size >= 1 << ($type << 3))
            die("invalid $size");
        else if ($type == 1)
            $this->putByte($size);
        else if ($type == 2)
            $this->putShort($size);
        else
            die("invalid $type");

    }

    public function finishPacket()
    {
        $tmp = $this->currentOffset;
        $this->currentOffset = $this->packetStart - $this->packetType;
        $this->putPacketSize($this->packetType, $tmp - $this->packetStart);
        $this->currentOffset = $tmp;
    }

    public function packData($resource) {
        $string = "";
        foreach ($resource as $chr) {
            $string .= chr($chr);
        }
        return $string;
    }

    public function getUnsignedShort() {
        $this->currentOffset += 2;
        return (($this->array[$this->currentOffset - 2] & 0xff) << 8) + ($this->array[$this->currentOffset - 1] & 0xff);
    }

    public function printArray() {
        var_dump($this->array);
    }

    public function clear() {
        $this->setStream(array());
        $this->currentOffset = 1;
    }

    public function getStream() {
        return $this->packData($this->array);
    }
    
    public function getCurrentOffset() {
        return $this->currentOffset;
    }

    public function setStream($arr) {
        $this->currentOffset = 1;
        $this->array = $arr;
    }

    public function putShort($i) {
        $this->array[$this->currentOffset++] = $i >> 8;
        $this->array[$this->currentOffset++] = $i;
        return $this;
    }

    public function toByte($val) {
        return ( ( ($val + 128) % 256) - 128 );
    }

    public function setCurrentOffset($offset) {
        $this->currentOffset = $offset;
    }

    public function putByte($i) {
        $this->array[$this->currentOffset++] = $this->toByte($i);
        return $this;
    }

    public function putBytesReverse($i) {
        $arrayLen = count($this->array);
        for($i = $arrayLen - 1; $i >= 0; $i--) {
            putByte($this->array[$i]);
        }
    }

    public function putInt($i) {
        $this->array[$this->currentOffset++] = $this->toByte($i >> 24);
        $this->array[$this->currentOffset++] = $this->toByte($i >> 16);
        $this->array[$this->currentOffset++] = $this->toByte($i >> 8);
        $this->array[$this->currentOffset++] = $this->toByte($i);
        return $this;
    }
 
    public function putLong($l) {
        $this->array[$this->currentOffset++] = $this->toByte($l >> 56);
        $this->array[$this->currentOffset++] = $this->toByte($l >> 48);
        $this->array[$this->currentOffset++] = $this->toByte($l >> 40);
        $this->array[$this->currentOffset++] = $this->toByte($l >> 32);
        $this->array[$this->currentOffset++] = $this->toByte($l >> 24);
        $this->array[$this->currentOffset++] = $this->toByte($l >> 16);
        $this->array[$this->currentOffset++] = $this->toByte($l >> 8);
        $this->array[$this->currentOffset++] = $this->toByte($l);
        return $this;
    }

    public function putShortA($val) {
        $this->array[$this->currentOffset++] = $this->toByte($val >> 8);
        $this->array[$this->currentOffset++] = $this->toByte($val + 128);
        return $this;
    }

    public function putByteA($val) {
        $this->array[$this->currentOffset++] = $this->toByte($val + 128);
        return $this;
    }

    public function putLEShortA($val) {
        $this->array[$this->currentOffset++] = $this->toByte($val + 128);
        $this->array[$this->currentOffset++] = $this->toByte($val >> 8);
        return $this;
    }

    public function putLEShort($val) {
        $this->array[$this->currentOffset++] = $this->toByte($val);
        $this->array[$this->currentOffset++] = $this->toByte($val >> 8);
        return $this;
    } 

    public function putBit($bool) {
        $this->putBits(1, $bool ? 1 : 0);
        return $this;
    }

    public function putHeader(\Server\Cryption\ISAAC $isaac, $packet) {
        $this->putByte($packet + $isaac->rand());
        return $this;
    }

    public function putByteC($val) {
        $this->array[$this->currentOffset++] = $this->toByte(-$val);
        return $this;
    }

    public function putInt1($val) {
        $this->array[$this->currentOffset++] = $this->toByte($val >> 8);
        $this->array[$this->currentOffset++] = $this->toByte($val);
        $this->array[$this->currentOffset++] = $this->toByte($val >> 24);
        $this->array[$this->currentOffset++] = $this->toByte($val >> 16);
        return $this;
    }

    public function putInt2($val) {
        $this->array[$this->currentOffset++] = $this->toByte($val >> 16);
        $this->array[$this->currentOffset++] = $this->toByte($val >> 24);
        $this->array[$this->currentOffset++] = $this->toByte($val);
        $this->array[$this->currentOffset++] = $this->toByte($val >> 8);
        return $this;
    }

    public function putLEInt($val) {
        $this->array[$this->currentOffset++] = $this->toByte($val);
        $this->array[$this->currentOffset++] = $this->toByte($val >> 8);
        $this->array[$this->currentOffset++] = $this->toByte($val >> 16);
        $this->array[$this->currentOffset++] = $this->toByte($val >> 24);
        return $this;
    }

    public function putByteS($val) {
        $this->array[$this->currentOffset++] = $this->toByte(128 - $val);
        return $this;
    }    

    public function putTriByte($val) {
        $this->array[$this->currentOffset++] = $this->toByte($val >> 16);
        $this->array[$this->currentOffset++] = $this->toByte($val >> 8);
        $this->array[$this->currentOffset++] = $this->toByte($val);
        return $this;
    }

    public function putSmart($val) {
        if($val >= 128) {
            putShort($val + 32768);
        } else {
            putByte(toByte($val));
        }
        return $this;
    }

    public function putSignedSmart($val) {
        if($val >= 128) {
            putShort($val + 49152);
        } else {
            putByte(toByte($val + 64));
        }
        return $this;
    }

    public function putString($s) {
        $max = $this->currentOffset + strlen($s);
        for($i = $this->currentOffset; $i < $max; $i++) {
            $this->array[$this->currentOffset + $i] = $s[$i];
        }
        $this->currentOffset += strlen($s);
        $this->array[$this->currentOffset++] = 10;
        return $this;
    }

    public function getUnsignedByte() {
        return $this->array[$this->currentOffset++] & 0xff;
    }

    public function get() {
        return $this->array[$this->currentOffset++];
    }

    public function getSignedInt() {
        $this->currentOffset += 2;
        $i = (($this->array[$this->currentOffset - 2] & 0xff) << 8) + ($this->array[$this->currentOffset - 1] & 0xff);
        if ($i > 32767) {
            $i -= 0x10000;
        }
        return $i;
    }
 
    public function getInt() {
        $this->currentOffset += 4;

        return (($this->array[$this->currentOffset - 4] & 0xff) << 24) + 
        (($this->array[$this->currentOffset - 3] & 0xff) << 16) + (($this->array[$this->currentOffset - 2] & 0xff) << 8) + 
        ($this->array[$this->currentOffset - 1] & 0xff);
    }
 
    public function getLong() {
        $l = $this->toLong($this->getInt() & 0xffffffff);
        $l1 = $this->toLong($this->getInt() & 0xfffffff);
        return ($l << 32) + $l1;
    }

    public function toLong($val) {
        return $val;
    }

    public function getString() {
        $i = $this->currentOffset;
        $string = '';
        while($this->array[$this->currentOffset++] != 10) {
            $string .= chr($this->array[$this->currentOffset - 1]);
        }
        return $string;
    }

    public function getBytes($abyte0, $i, $j) {
        for ($k = $j; $k < $j + $i; $k++) {
            $abyte0[$k] = $this->array[$this->currentOffset++];
        }
    }
}
?>
