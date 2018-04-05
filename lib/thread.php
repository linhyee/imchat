<?php 
namespace lib;

/**
 * 
 * @package  lib.thread
 * @author  mrlin <714480119@qq.com>
 */

class Thread
{

    /**
     * 
     * mutil-thread
     * 
     * @param  string  $url
     * 
     * @param  string  $hostname
     * 
     * @param  integer $port
     * 
     * @noreturn
     * 
     */
    public static function runThread($url, $hostname = '', $port = 80)
    {
        if(!$hostname) {
            $hostname = $_SERVER['HTTP_HOST'];
        }

        $fp = fsockopen($hostname, $port, $errno, $errstr, 600);

        if (!$fp) {
            echo "$errstr ($errno)<br />\n";
            return;
        }

        fputs($fp,"GET " . $url . "\r\n");
        fclose($fp);
    }

// ------------------------------------------------------------------------
    /**
     * 
     * mutil-thread
     * 
     * @param  string  $url
     * 
     * @param  string  $hostname
     * 
     * @param  integer $port
     * 
     * @noreturn
     * 
     */
    public static function runThreads($url, $hostname = '', $port = 80 )
    {
        if(!$hostname) {
            $hostname=$_SERVER['HTTP_HOST'];
        }

        $fp = fsockopen($hostname, $port, $errno, $errstr, 600);

        if (!$fp) {
            echo "$errstr ($errno)<br />\n";
            return;
        }

        fputs($fp, "GET " . $url . "\r\n");

        while (!feof($fp)) {
            echo fgets($fp,2048);
        }
        
        fclose($fp);
    }	

// ------------------------------------------------------------------------
    /**
     * 
     * mutil-thread noblock
     * 
     * @param  array|string  $urls
     * 
     * @param  string  $hostname
     *
     * @param  integer $port
     * 
     * @noreturn
     * 
     */
	public static function runThreadSOCKET($urls, $hostname = '', $port = 80)
	{
		if(!$hostname) {
			$hostname = $_SERVER['HTTP_HOST'];
		}

		if(!is_array($urls)) {
			$urls = (array)$urls;
		}

		foreach ($urls as $url) {
			$fp = fsockopen($hostname, $port,  $errno, $errstr, 18000);

			stream_set_blocking($fp, true);
			stream_set_timeout($fp, 18000);
			fputs($fp,"GET ".$url."\r\n");

			fclose($fp);
		}
	}
}