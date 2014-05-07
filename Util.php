<?php

class Util {
    /**
     * Swap endian-ness of binary data.
     * @param string $buf
     * @return string
     */
    public static function swapEndian($buf) {
        $r = str_pad($buf, strlen($buf));

        for($i = 0; $i < floor(strlen($buf) / 4); $i++) {
            $r[$i * 4] = $buf[$i * 4 + 3];
            $r[$i * 4 + 1] = $buf[$i * 4 + 2];
            $r[$i * 4 + 2] = $buf[$i * 4 + 1];
            $r[$i * 4 + 3] = $buf[$i * 4];
        }

        return $r;
    }

    /**
     * Swap endian-ness of a hex string.
     * @param string $hex
     * @return string
     */
    public static function swapEndianHex($hex) {
        return implode('', array_reverse(str_split($hex, 2)));
    }

    public static function hex2bin($data) {
        if(is_array($data)) {
            foreach($data as $index => $datum) {
                $data[$index] = self::hex2bin($datum);
            }
            return $data;
        }
        
        static $old;
        if ($old === null) {
            $old = version_compare(PHP_VERSION, '5.2', '<');
        }
        $isobj = false;
        if (is_scalar($data) || (($isobj = is_object($data)) && method_exists($data, '__toString'))) {
            if ($isobj && $old) {
                ob_start();
                echo $data;
                $data = ob_get_clean();
            }
            else {
                $data = (string) $data;
            }
        }
        else {
            trigger_error(__FUNCTION__.'() expects parameter 1 to be string, ' . gettype($data) . ' given', E_USER_WARNING);
            return;//null in this case
        }
        $len = strlen($data);
        if ($len % 2) {
            trigger_error(__FUNCTION__.'(): Hexadecimal input string must have an even length', E_USER_WARNING);
            return false;
        }
        if (strspn($data, '0123456789abcdefABCDEF') != $len) {
            trigger_error(__FUNCTION__.'(): Input string must be hexadecimal string', E_USER_WARNING);
            return false;
        }
        return pack('H*', $data);
    }
}
