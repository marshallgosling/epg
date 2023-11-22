<?php

namespace App\Admin\Actions\Material;

use App\Models\Program;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;

class Importor extends RowAction
{
    public $name = '导入节目库';

    public function handle(\App\Models\Material $model)
    {
        // $model ...
        $program = new Program();
        $program->unique_no = $model->unique_no;
        $program->name = $model->name;
        $program->duration = $model->duration;
        $program->category = $model->category;
        
        if(Program::where('unique_no', $model->unique_no)->exists())
        {
            return $this->response()->error(__('Import failed message'));
        }
        else {
            $program->save();
        }
        return $this->response()->success(__('Import Success message'));
    }

}