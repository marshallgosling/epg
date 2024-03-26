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
            $afd = $data->media->track[1]->ActiveFormatDescription;

            return compact('frames', 'duration', 'size', 'afd');
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
    public static function getRawInfo(Material $material)
    {
        $cmd = [];
        $cmd[] = config("MEDIAINFO_PATH", 'MediaInfo.exe');
        $cmd[] = $material->filepath;

        $process = new Process($cmd);
        $process->run();
        if ($process->isSuccessful()) {
            return $process->getOutput();
        }
        else {
            echo "process error:".$process->getErrorOutput().PHP_EOL;
        }
        return false;
        
    }

    public static function scanPath($filename)
    {
        $list = config('MEDIA_SOURCE_FOLDER');
        if($list) {
            $list = explode(PHP_EOL, $list);

            foreach($list as $item)
            {
                $path = $item.$filename;
                if(file_exists($path))
                {
                    return $path;
                }
            }
        }

        return false;
    }

}