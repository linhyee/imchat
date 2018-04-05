<?php 
namespace lib;

/**
 * 
 * @package  lib.config
 * @author  mrlin <714480119@qq.com>
 */
class Config
{
    /**
     * developer env
     */
    const DEV = 'dev';

    /**
     * production env
     */

    const PRO = 'pro';

    /**
     * user acceptance test env
     */
    const UAT = 'uat';


// ------------------------------------------------------------------------
    /**
     * 
     * get one configure
     * 
     * @param  string $env
     * 
     * @return array
     * 
     */
    public static function getConfig($env = Config::DEV)
    {
        switch ($env) {
            case 'dev':
                $config = self::dev();
                break;
            
            case 'pro':
                $config = self::pro();
                break;

            case 'uat':
                $config = self::uat();
                break;

            default:
                throw new \Exception("Invalid config type", 1);
                break;
        }

        return $config;
    }

// ------------------------------------------------------------------------
    /**
     * 
     * @doc
     * 
     * @return array
     * 
     */
    protected static function dev()
    {
        return array('db_type' => 'sqlite','sqlite_path' => dirname(getcwd()).'/data/ichat.db' );
    }

// ------------------------------------------------------------------------
    /**
     * @doc
     * 
     * @return array
     * 
     */
    protected static function pro()
    {
        return array();
    }

// ------------------------------------------------------------------------
    /**
     * @doc
     * 
     * @return array
     *
     */
    protected static function uat()
    {
        return array(); 
    }
}
