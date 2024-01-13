<?php
namespace lib;

use XmlWriter;

/**
 * 
 * Based on: http://stackoverflow.com/questions/99350/passing-php-associative-arrays-to-and-from-xml
 * 
 * @package lib.array2xml
 * @author mrlin <714480119@qq.com>
 * 
 */
class Array2XML
{
    /**
     * xml version
     * 
     * @var string
     */
    private $version;

    /**
     * xml encode
     * 
     * @var string
     */
    private $encoding;

    /**
     * with XML declaration ?
     * 
     * @var boolean
     */
    private $declaration = true;

    /**
    * Construct Array2XML object with selected version and encoding 
    *
    * for available values check XmlWriter docs http://www.php.net/manual/en/function.xmlwriter-start-document.php
    * 
    * @param string $xml_version XML Version, default 1.0
    * @param string $xml_encoding XML Encoding, default UTF-8
    */
    public function __construct($xmlVersion = '1.0', $xmlEncoding = 'UTF-8')
    {
        $this->version = $xmlVersion;
        $this->encoding = $xmlEncoding;
    }

    /**
     * 
     * with XML declaration ?
     * 
     * @param  boolean $bool 
     * 
     * @return $this
     */
    public function withDeclaration($bool = true)
    {
        $this->declaration = $bool;

        return $this;
    }

    /**
     * siglon method
     * 
     * @param  array $data    Associative Array to be parsed into an XML Data
     * @param  string $startEl root node of XML
     * @return string
     */
    public static function createXML($data, $startEl)
    {
        static $arr2xml = null;

        if (!$arr2xml instanceof Array2XML)
        {
            $arr2xml = new self();
        }

        return $arr2xml->buildXML($data, $startEl);
    }

    /**
     * Build an XML Data Set
     *
     * @param array $data Associative Array containing values to be parsed into an XML Data Set(s)
     * @param string $startElement Root Opening Tag, default data
     * @return string XML String containig values
     * @return mixed Boolean false on failure, string XML result on success
     */
    public function buildXML($data, $startElement = 'data')
    {
        if(!is_array($data))
        {
            $err = 'Invalid variable type supplied, expected array not found on line '.__LINE__." in Class: ".__CLASS__." Method: ".__METHOD__;
            trigger_error($err);

            return false; //return false error occurred
        }

        $xml = new XmlWriter();
        $xml->openMemory();

        if ($this->declaration)
        {
            $xml->startDocument($this->version, $this->encoding);
        }

        $xml->startElement($startElement);

        $this->writeEl($xml, $data);

        $xml->endElement();//write end element
        //returns the XML results
        return $xml->outputMemory(true);
    }

    /**
     * Map tree to Associative Array
     * 
     * @param  array $array Associative Array that keys is somelike 'A_B_C_D'
     * @return array
     */
    public static function mapArrayData($array)
    {
        $map = array();

        foreach ($array as $key => $value)
        {
            //Reference the Array $ant
            $ref = & $map;

            foreach (explode('_', $key) as $v)
            {
                if (!isset($ref[$v]))
                {
                    $ref[$v] = '';
                }
                // Save the current reference
                $ref = & $ref[$v];
            }

            $ref = $value;
        }

        return $map;
    }

    /**
     * Write keys in $data prefixed with @ as XML attributes, if $data is an array. 
     * When an @ prefixed key is found, a '%' key is expected to indicate the element itself, 
     * and '#' prefixed key indicates CDATA content
     *
     * @param object $xml XMLWriter Object
     * @param array $data with attributes filtered out
     * @return array
     */
    protected function writeAttr(XMLWriter $xml, $data)
    {
        if(is_array($data))
        {
            $nonAttributes = array();

            if (array_key_exists('%', $data))
            {
                // bug, make sure the attributes first
                krsort($data);
            }

            foreach ($data as $key => $val) 
            {
                //handle an attribute with elements
                if($key[0] == '@')
                {
                    $xml->writeAttribute(substr($key, 1), $val);
                }
                else if($key[0] == '%')
                {
                    if(is_array($val))
                        $nonAttributes = $val;
                    else
                        $xml->text($val);
                }
                //ignore normal elements
                else $nonAttributes[$key] = $val;
            }

            return $nonAttributes;
        }

        return $data;
    }

    /**
     * Write XML as per Associative Array
     *
     * @param object $xml XMLWriter Object
     * @param array $data Associative Data Array
     * @noreturn
     */
    protected function writeEl(XMLWriter $xml, $data)
    {
        foreach($data as $key => $value)
        {
            if(is_array($value) && !$this->isAssoc($value))
            { 
                //numeric array
                foreach($value as $itemValue)
                {
                    if(is_array($itemValue))
                    {
                        $xml->startElement($key);

                        $itemValue = $this->writeAttr($xml, $itemValue);
                        $this->writeEl($xml, $itemValue);

                        $xml->endElement();
                    }
                    else
                    {
                        $itemValue = $this->writeAttr($xml, $itemValue);
                        $xml->writeElement($key, "$itemValue");
                    }
                }
            }
            else if(is_array($value))
            {
                //associative array
                $xml->startElement($key);

                $value = $this->writeAttr($xml, $value);
                $this->writeEl($xml, $value);

                $xml->endElement();
            }
            else //scalar
            {
                if ($key[0] == '#')
                {
                    $xml->startElement(substr($key, 1));
                    $xml->writeCData($value);
                    $xml->endElement();
                }
                else
                {
                    $xml->writeElement($key, "$value");
                }
            }
        }
    }

    /**
     * Check if array is associative with string based keys
     * 
     * FROM: http://stackoverflow.com/questions/173400/php-arrays-a-good-way-to-check-if-an-array-is-associative-or-sequential/4254008#4254008
     *
     * @param array $array Array to check
     * @return boolean
     */
    protected function isAssoc($array)
    {
        return (bool)count(array_filter(array_keys($array), 'is_string'));
    }
}