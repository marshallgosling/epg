<?php
namespace App\Tools\Material;

use App\Models\Material;
use Symfony\Component\Process\Process;

class MediaInfo
{
    /**
     * Parse MXF file using MediaInfo.exe
     * 
     * @param Material $material 素材对象
     * @param int $mode 返回模式，0:只返回必要数据 (frames,duration,size). 1:返回所有json数据
     * 
     * @return array|object|bool
     */
    public static function getInfo(Material $material, $mode=0)
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

            if(!$data->media) return false;

            if($mode == 1) return $data;

            $frames = $data->media->track[0]->FrameCount;
            $duration = $data->media->track[0]->Duration;
            $size = $data->media->track[0]->FileSize;

            return compact('frames', 'duration', 'size');
        }
        
        return false;
        
    }

    /**
     * Parse MXF file using MediaInfo.exe, return Raw Data
     * 
     * @param Material $material 素材对象
     * 
     * @return string
     */
    public static function geRawInfo(Material $material)
    {
        $cmd = [];
        $cmd[] = config("MEDIAINFO_PATH", 'MediaInfo.exe');
        $cmd[] = $material->filepath;

        $process = Process::fromShellCommandline(implode(' ', $cmd));
        $process->run();
        if ($process->isSuccessful()) {
            return implode(' ', $cmd).$process->getOutput();
        }
        else {
            return $process;
        }
        
        return false;
        
    }

}