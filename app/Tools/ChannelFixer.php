<?php

namespace App\Tools;

class ChannelFixer 
{
    public static $channel;

    public static function fixChannelStartTime($channel)
    {
        if(!$channel) {
            echo "channel is null.".PHP_EOL;
            return;
        }

        $programs = $channel->programs()->get();
        $last = strtotime($channel->air_date.' 00:00:00');
        echo "channel air date is {$channel->air_date}".PHP_EOL;

        foreach($programs as $p)
        {
            echo "Program {$p->name} air date is {$p->start_at}".PHP_EOL;
            $air = strtotime($p->start_at);

            if($air < $last) {
                $air += 24 * 3600;
                
                echo "fix program: {$p->start_at} to ".date('Y-m-d H:i:s', $air).PHP_EOL;

                $p->start_at = date('Y-m-d H:i:s', $air);
                $p->save();
            }

            $last = $air;
        }
    }
}