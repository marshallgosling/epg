<?php

namespace App\Tools;

class XmlExporter
{
    private $indentation = '    ';
    private $xml;
    // TODO: private $this->addtypes = false; // type="string|int|float|array|null|bool"
    public function export($data, $root='root')
    {
        $data = array($root => $data);
        $this->xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>';
        $this->recurse($data, 0);
        $this->xml .= PHP_EOL;
        return $this->xml;
    }
    private function recurse($data, $level)
    {
        $indent = str_repeat($this->indentation, $level);
        foreach ($data as $key => $value) {
            $this->xml .= PHP_EOL . $indent . '<' . $key;
            if ($value === null) {
                $this->xml .= ' />';
            } else {
                $this->xml .= '>';
                if (is_array($value)) {
                    if ($value) {
                        $temporary = $this->getArrayName($key);
                        foreach ($value as $idx=>$entry) {
                            if($idx>0) $this->xml .= PHP_EOL . $indent . '<' . $key .'>';
                            $this->recurse($entry, $level + 1);
                            if($idx<count($value)-1) $this->xml .= PHP_EOL . $indent . '</' . $key . '>';
                        }
                        $this->xml .= PHP_EOL . $indent;
                    }
                } else if (is_object($value)) {
                    if ($value) {
                        $this->recurse($value, $level + 1);
                        $this->xml .= PHP_EOL . $indent;
                    }
                } else {
                    if (is_bool($value)) {
                        $value = $value ? 'true' : 'false';
                    }
                    $this->xml .= $this->escape($value);
                }
                $this->xml .= '</' . $key . '>';
            }
        }
    }
    private function escape($value)
    {
        // TODO:
        return $value;
    }
    private function getArrayName($parentName)
    {
        // TODO: special namding for tag names within arrays
        return $parentName;
    }
}
