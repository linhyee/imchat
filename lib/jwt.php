<?php
namespace lib;

/**
 * 
 * @package  lib.jwt
 * @author  mrlin <714480119@qq.com>
 */

class Jwt
{
    /**
     * 
     * encode jwt
     * 
     * @param  mixed $payload  object or array or others
     * @param  string $key     The secret key
     * 
     * @return string          A JWT
     * 
     */
    public static function encode($payload, $key)
    {
        $hd = array(
            'typ' => 'JWT',
            'alg' => 'HS256',
        );

        $sg = array(
            self::b64encode(json_encode($hd)),
            self::b64encode(json_encode($payload)),
        );

        $sign = self::hmacsha256(implode('.', $sg), $key);
        $sg[] = self::b64encode($sign);

        return implode('.', $sg);
    }

// ------------------------------------------------------------------------
    /**
     * decode jwt
     * 
     * @param  string $jwt A JWT string
     * @param  string $key The secret
     * 
     * @return mixed       payload
     */
    public static function decode($jwt, $key)
    {
        if (empty($key))
        {
            return false;
        }

        $tks = explode('.', $jwt);

        if (count($tks) != 3)
        {
            return false;
        }

        list($hd, $bd, $sg) = $tks;

        if ( ($nhd = json_decode(self::b64decode($hd))) === null)
        {
            return false;
        }

        if ( ($nbd = json_decode(self::b64decode($bd))) === null)
        {
            return false;
        }

        $nsg = self::b64decode($sg);

        if (self::hmacsha256("$hd.$bd", $key) != $nsg)
        {
            return false;
        }

        return $nbd;
    }

// ------------------------------------------------------------------------
    /**
     * 
     * safe base64 decode
     * 
     * @param  string $input input string
     * 
     * @return mixed         object or array or others
     * 
     */
    public static function b64decode($input)
    {
        $s = strlen($input) % 4;

        if ($s)
        {
            $len = 4 - $s;
            $input .= str_repeat('=', $len);
        }

        return base64_decode(strtr($input, '-_', '+/'));
    }

// ------------------------------------------------------------------------
    /**
     * 
     * safe base64 encode
     * 
     * @param  string $input input string
     * 
     * @return mixed         object or array or others
     * 
     */
    public static function b64encode($input)
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }

// ------------------------------------------------------------------------
    /**
     * HS512
     */
    public static function hmacsh1($data, $key)
    {
        if (function_exists('hash_hmac'))
        {
            return hash_hmac('sha1', $data, $key, true);
        }
        
        $bz = 64;

        if (strlen($key) > $bz)
        {
            $key = pack('H*', sha1($key));
        }

        $key = str_pad($key, $bz, chr(0x00));
        $ipd = str_repeat(chr(0x36), $bz);
        $opd = str_repeat(chr(0x5c), $bz);

        $hmac = pack('H*', sha1(($key ^ $opd) . pack('H*', sha1($key ^ $ipd) . $data)));

        return $hmac;
    }

// ------------------------------------------------------------------------
    /**
     * HS256
     */
    public static function hmacsha256($data, $key)
    {
        if (function_exists('hash_hmac'))
        {
            return hash_hmac('sha256', $data, $key);
        }
    }
}