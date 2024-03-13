<?php
namespace App\Tools\Material;

use App\Models\Material;

class RecognizeFileInfo
{

    /**
     * recognize filename 
     * format:[name.unique_no.mxf]
     * 
     * @param string $filename
     * 
     * @return Array|false
     */
    public static function recognize($filename)
    {
        $names = explode('.', $filename);

        $unique_no = '';
        $name = '';
        $m = false;

        if(count($names)<=1) return compact('filename', 'unique_no', 'name', 'm');
        if(array_pop($names) != 'mxf') return compact('filename', 'unique_no', 'name', 'm');

        if(count($names) == 2)
        {
            $unique_no = array_pop($names);
            $name = array_pop($names);
            
            $m = Material::where('unique_no', $unique_no)->first();

            if(!$m) {
                $m = Material::where('name', $name)->first();
            }

        }

        if(count($names) == 1)
        {
            $unknow = array_pop($names);
            $m = Material::where('unique_no', $unknow)->first();

            if(!$m) {
                $m = Material::where('name', $unknow)->first();
                if($m) $name = $unknow;
            }
            else {
                $unique_no = $unknow;
            }

            if(preg_match('/^[VC|XK|X]/', $unknow, $matches))
            {
                $unique_no = $unknow;
            }
            else if(preg_match('/^[VC|XK|X]/', $unknow, $matches))
            {
            }

        }

        return compact('filename', 'unique_no', 'name', 'm');
    }
}