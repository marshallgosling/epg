<?php
namespace App\Tools\Material;

use App\Models\Material;
use Symfony\Component\Process\Process;

class MediaInfo
{
    public static function getInfo(Material $material)
    {
        $cmd = [];
        $cmd[] = config("MEDIAINFO_PATH", 'MediaInfo.exe');
        $cmd[] = "--Output=JSON";
        $cmd[] = $material->filepath;

        $process = new Process($cmd);
        $process->run();
        if ($process->isSuccessful()) {
            $json = $process->getOutput();

            $data = json_decode($json);

            $frames = $data->media->track[0]->FrameCount;
            $durtion = $data->media->track[0]->Duration;
            $size = $data->media->track[0]->FileSize;

            return compact('frames', 'duration', 'size');
        }
        return false;
        
    }
}