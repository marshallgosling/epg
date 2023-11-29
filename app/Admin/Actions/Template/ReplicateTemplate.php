<?php

namespace App\Admin\Actions\Template;

use App\Models\Template;
use App\Models\TemplatePrograms;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ReplicateTemplate extends RowAction
{
    public $name = '复制';

    public function handle(Template $model)
    {
        $template_id = $model->id;
        $new = $model->replicate();
        $new->status = Template::STATUS_NOUSE;
        $new->save();
        
        $newid = $new->id;
        $programs = TemplatePrograms::where('template_id', $template_id)
                    ->select("name", "category", "data" ,"type", "template_id", "sort")
                    ->get()->toArray();

        foreach($programs as &$pro)
        {
            $pro['template_id'] = $newid;
        }

        TemplatePrograms::insert($programs);

        return $this->response()->success(__('Replicate Success message'))->refresh();
    }

}