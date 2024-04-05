<?php

namespace App\Tools\Exporter;

class XmlReader
{
    
    public static function parseXml($str)
    {
        $ret = preg_match_all('/<Id>(.*)<\/Id>/', $str, $matches);
        if(!$ret) return false;
        
        return $matches[1];
    }

    public static function parseSystemTime($str)
    {
        $ret = preg_match_all('/<SystemTime>(.*)<\/SystemTime>/', $str, $matches);
        if(!$ret) return false;
        
        return implode(',', $matches);
    }
}