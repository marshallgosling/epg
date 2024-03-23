<?php

namespace App\Tools\Exporter;

use Illuminate\Support\Facades\Storage;

class ZipperFiles
{

    public static function createZip($filepath, $files)
    {
        $zip = new \ZipArchive;
        $zipFileName = Storage::disk('xml')->path($filepath);
        if ($zip->open($zipFileName, \ZipArchive::CREATE) === TRUE) {
            
            foreach ($files as $file) {
                $zip->addFile(Storage::disk('xml')->path($file), $file);
            }

            $zip->close();

            return true;
        } else {
            return false;
        }
    }
}