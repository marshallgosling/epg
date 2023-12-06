<?php

namespace App\Admin\Actions\Material;

use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;

class BatchImportor extends BatchAction
{
    public $name = '批量导入节目库';

    public function handle(Collection $models)
    {
        foreach($models as $model)
        {
            $class = '\App\Models\Program';
            $program = new $class();
            
            if(in_array($model->category, ['tvshow', 'tvseries', 'movie','starmade','cartoon']))
            {
                $class = '\App\Models\Record';
                $program = new $class();
                $program->episodes = $model->group;
                if(preg_match('/(\d+)$/', $model->name, $matches))
                {
                    $program->ep = (int) $matches[1];
                }
            }
            
            $program->name = $model->name;
            $program->unique_no = $model->unique_no;
            $program->duration = $model->duration;
            $program->category = [$model->category];
            
            if($class::where('unique_no', $model->unique_no)->exists())
            {
                continue;
            }
            else {
                $program->save();
            }
        }
        
        return $this->response()->success(__('Import Success message'));
    }

}