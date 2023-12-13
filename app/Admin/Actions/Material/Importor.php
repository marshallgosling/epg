<?php

namespace App\Admin\Actions\Material;

use App\Models\Program;
use App\Models\Record;
use App\Models\Material;
use Encore\Admin\Actions\RowAction;

class Importor extends RowAction
{
    public $name = '导入节目库';

    public function handle(Material $model)
    {
        $class = '\App\Models\Program';
        $program = new $class();
        if(in_array($model->category, ['CanXin', 'drama', 'movie','Entertainm','cartoon']))
        {
            $class = '\App\Models\Record';
            $program = new $class();
            
            $program->episodes = $model->group;
            if(preg_match('/(\d+)$/', $model->name, $matches))
            {
                $program->ep = (int) $matches[1];
                
            }
        }
        
        $program->unique_no = $model->unique_no;
        $program->name = $model->name;
        $program->duration = $model->duration;
        $program->category = [$model->category];
        
        if($class::where('unique_no', $model->unique_no)->exists())
        {
            return $this->response()->error(__('Import failed message'));
        }
        else {
            $program->save();
        }
        return $this->response()->success(__('Import Success message'));
    }

}