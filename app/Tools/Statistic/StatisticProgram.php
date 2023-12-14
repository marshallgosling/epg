<?php

namespace App\Tools\Statistic;

use App\Models\Channel;
use App\Models\ChannelPrograms;
use App\Models\Statistic;

class StatisticProgram implements IStatistic
{
    public $model = 'Program:unique_no';

    public $channels;

    private $channel;

    private $data;

    public $sample = [
        "model" => '',
        "column" => '',
        "group" => '',
        "value" => 1,
        "type" => Statistic::TYPE_DAILY
    ];

    public function load($channel) {
        //$this->channels = Channel::where('audit_status', Channel::AUDIT_PASS)->with('programs')->get();
        $this->channel = $channel;
    }

    public function loadSample($sample=false)
    {
        if($sample) $this->sample = $sample;
    }

    public function scan() {
        
        $sample = $this->sample;
        $sample['model'] = $this->model;

        $statistic = [];

        $channel = $this->channel;

        if(!$channel) {
            return ["result"=>false, "msg"=>"Please run load function before run scan."];
        }

        $programs = $channel->programs()->get();

        if(count($programs) == 0) {
            return ["result"=>false, "msg"=>"Channel is empty. Nothing to scan."];
        }

        
            foreach($programs as $pro) {
                $data = json_decode($pro->data, true);

                if(array_key_exists('replicate', $data)) {
                    $replicate = $data['replicate'];
                    $data = ChannelPrograms::where('id', $replicate)->value('data');
                    $data = json_decode($data, true);
                }
                
                foreach($data as $item)
                {
                    if(array_key_exists($item['unique_no'], $statistic))
                    {
                        $statistic[$item['unique_no']]['value'] += 1;
                    }
                    else {
                        $obj = $sample;
                        $obj['category'] = $item['category'];
                        $obj['group'] = $channel->name;
                        $obj['date'] = $channel->air_date;
                        $obj['column'] = $item['unique_no'];
                        $obj['comment'] = $item['name'];
                        $statistic[$item['unique_no']] = $obj;
                    }
                }
            }
        

        $this->data = [];
        
        foreach($statistic as $s) {
            $this->data[] = $s;
        }

        return ["result"=>true];
    }

    public function store($force=false)
    {
        if($force) {
            Statistic::where('date', $this->channel->air_date)->delete();
        }
        Statistic::upsert($this->data, ['date', 'column', 'group', 'model', 'type'], ['value', 'comment', 'category']);
    }

    public function print()
    {
        print_r($this->data);
    }
}