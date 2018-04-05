<?php 

namespace lib;

/**
 * 
 * @package lib.http
 * @author mrlin <71448011@qq.com>
 */
class Http
{
    /**
     * 
     * post data
     * 
     * @var array
     */
    protected $post = array();

    /**
     * 
     * retry times
     * 
     * @var integer
     */
    protected $retry = 3;

    /**
     * 
     * curl options
     * 
     * @var array
     */
    protected $option = array();

    /**
     * 
     * defaults
     * 
     * @var array
     */
    protected $default = array();

    /**
     * 
     * whether download ?
     * 
     * @var boolean
     */
    protected $download = false;

// ------------------------------------------------------------------------
    /**
     * 
     * @__construct
     */
    public function __construct()
    {
        $this->retry = 0;

        //set curl default options
        $this->default = array(
            'CURLOPT_TIMEOUT'        => 30,
            'CURLOPT_ENCODING'       => '',
            'CURLOPT_IPRESOLVE'      => 1,
            'CURLOPT_RETURNTRANSFER' => true,
            'CURLOPT_SSL_VERIFYPEER' => false,
            'CURLOPT_CONNECTTIMEOUT' => 10,         
        );
    }
// ------------------------------------------------------------------------
    /**
     * 
     * create http instance
     * 
     * @return Object
     * 
     */
    public static function getHttp()
    {
        static $http = null;

        if (!$http instanceof Http)
        {
            $http = new self();
        }

        return $http;
    }

// ------------------------------------------------------------------------
    /**
     * 
     * get request
     * 
     * @param  string $url
     * 
     * @return
     */
    public function get($url)
    {
        return $this->set('CURLOPT_URL', $url)->exec();
    }

// ------------------------------------------------------------------------
    /**
     * 
     * post request
     * 
     * @param  array|string $data
     * 
     * @param  string $value
     * 
     * @return $this
     */
    public function post($data, $value = '')
    {
        if (is_array($data))
        {
            foreach ($data as $key => $value)
            {
                $this->post[$key] = $value;
            }
        }
        else
        {
            $this->post[$data] = $value;
        }

        return $this;
    }

// ------------------------------------------------------------------------
    /**
     *
     * submit post request
     *
     * @param  string $url
     * 
     * @return array
     *
     */
    public function submit($url)
    {
        if (!$this->post)
        {
            return array(
                'error'   => -1,
                'message' => 'no post data',
            );
        }

        return $this->set('CURLOPT_URL', $url)->exec();
    }

// ------------------------------------------------------------------------
    /**
     * 
     * set download url
     * 
     * @param  string $url
     * 
     * @return $this
     * 
     */
    public function download($url)
    {
        $this->download = true;

        return $this->set('CURLOPT_CUSTOMREQUEST', 'GET')->set('CURLOPT_URL', $url);
    }

// ------------------------------------------------------------------------
    /**
     *
     * save download file
     * 
     * @param  string $path
     * 
     * @return int
     * 
     */
    public function save($path)
    {
        if (!$this->download)
        {
            return array(
                'error'   => -1,
                'message' => 'no download url',
            );
        }

        $result = $this->exec();

        if ($result['error'] === 0)
        {
            $pathinfo = pathinfo($path);
            $dir      = realpath($pathinfo['dirname']);

            if (!is_writable($dir))
            {
                return array(
                    'error'   => -1,
                    'message' => 'no such directory or directory is not writable',
                );
            }

            $fp = fopen($path, 'w');
            fwrite($fp, $result['body']);
            fclose($fp);
        }

        return $result;
    }

// ------------------------------------------------------------------------
    /**
     *
     * 
     * set curl option
     * 
     * @param array|string $item
     * 
     * @param string $value
     * 
     */
    public function set($item, $value = '')
    {
        if (is_array($item))
        {
            foreach ($item as $key => &$value)
            {
                $this->option [$key] = $value;
            }
        }
        else
        {
            $this->option[$item] = $value;
        }

        return $this;
    }

// ------------------------------------------------------------------------
    /**
     * 
     * retry when occur error
     * 
     * @param  integer $times
     * 
     * @return $this
     * 
     */
    public function retry($times = 0)
    {
        $this->retry = $times;
        return $this;
    }

// ------------------------------------------------------------------------
    /**
     * 
     * clear properties
     * 
     * @return $this
     */
    public function clear()
    {
        $this->post     = null;
        $this->retry    = null;
        $this->option   = null;
        $this->download = null;

        return $this;
    }

// ------------------------------------------------------------------------
    /**
     * 
     * exec curl action
     *
     * @param  integer $retry
     *
     * @return array
     * 
     */
    protected function exec($retry = 0)
    {
        $ch = curl_init();

        $options = array_merge($this->default, $this->option);

        foreach ($options as $key => $val)
        {
            if (is_string($key))
            {
                $key = constant(strtoupper($key));
            }

            curl_setopt($ch, $key, $val);
        }

        if ($this->post)
        {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->buildPost($this->post));
        }

        $body  = curl_exec($ch);
        $info  = curl_getinfo($ch);
        $errno = curl_errno($ch);

        if ($errno === 0 && $info['http_code'] >= 400)
        {
            $errno = $info['http_code'];
        }

        curl_close($ch);

        //auto retry
        if ($errno && $retry < $this->retry)
        {
            $this->exec($retry + 1);
        }

        $this->clear();

        return array(
            'body'  => $body,
            'info'  => $info,
            'error' => $errno
        );
    }   

// ------------------------------------------------------------------------
    /**
     * 
     * build post data
     * 
     * @param  array|string $input
     *
     * @param  string $pre
     * 
     * @return mixed
     */
    protected function buildPost($input, $pre = null)
    {
        if (is_array($input))
        {
            $output = array();

            foreach ($input as $key => $value)
            {
                $index = is_null($pre) ? $key : "{$pre}[{$key}]";

                if (is_array($value))
                {
                    $output = array_merge($output, $this->buildPost($value, $index));
                }
                else
                {
                    $output[$index] = $value;
                }
            }

            return $output;
        }

        return $input;
    }
}
