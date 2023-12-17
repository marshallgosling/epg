<?php

/**
 * Laravel-admin - admin builder based on Laravel.
 * @author z-song <https://github.com/z-song>
 *
 * Bootstraper for Admin.
 *
 * Here you can remove builtin form field:
 * Encore\Admin\Form::forget(['map', 'editor']);
 *
 * Or extend custom form field:
 * Encore\Admin\Form::extend('php', PHPEditor::class);
 *
 * Or require js and css assets:
 * Admin::css('/packages/prettydocs/css/styles.css');
 * Admin::js('/packages/prettydocs/js/main.js');
 *
 */

Encore\Admin\Facades\Admin::navbar(function (\Encore\Admin\Widgets\Navbar $navbar) {

    //$navbar->left(view('admin.search'));

    $navbar->right(view('admin.notification'));

});

Encore\Admin\Grid::init(function (Encore\Admin\Grid $grid) {
    $grid->disableExport();
    $grid->paginate(30);
    $grid->filter(function (Encore\Admin\Grid\Filter $filter) {
        $filter->disableIdFilter();
        if(array_key_exists('expand', $_REQUEST))$filter->expand();
        $filter->setAction('?expand=1');
    });
    $grid->header(function ($query) {
        return '<small>按住 <kbd>shift</kbd> 键可快速批量多选操作</small>';
    });
    $js = <<<JS
    var startmove = false;
    var templist = [];
    var shift = false;
    $('body').on('keydown', function(e) {
        if(e.shiftKey) shift = true;
    });
    $('body').on('keyup', function(e) {
        shift = false;
    });
    $('tbody > tr').on('mousedown', function(e) {
        if(!shift) return;
        e.preventDefault();
        let idx = $(this).data('key');
        
        startmove = true;
        $('input[data-id='+idx+']').iCheck('toggle');
    });
    $('tbody > tr').on('mouseenter', function(e) {
        if(startmove) {
            let idx = $(this).data('key');
            
            if(templist.indexOf(idx) == -1) {
                templist.push(idx);
                $('input[data-id='+idx+']').iCheck('toggle');
            }
            else {
                let t = templist.splice(templist.indexOf(idx));

                for(i=0;i<t.length;i++) {
                    $('input[data-id='+t[i]+']').iCheck('toggle');
                }
            }
        }
    });
    $('body').on('mouseup', function(e) {
        startmove = false;
        templist = [];
        
        var selected = $.admin.grid.selected().length;

        if (selected > 0) {
            $('.grid-select-all-btn').show();
        } else {
            $('.grid-select-all-btn').hide();
        }

        $('.grid-select-all-btn .selected').html("已选择 {n} 项".replace('{n}', selected));
    });
JS;
    \Encore\Admin\Facades\Admin::script($js);
});

Encore\Admin\Form::init(function (Encore\Admin\Form $form) {

    $form->disableEditingCheck();

    $form->disableCreatingCheck();

    $form->disableViewCheck();

    $form->tools(function (Encore\Admin\Form\Tools $tools) {
        $tools->disableDelete();
        $tools->disableView();
        $tools->disableList();
    });
    
});

\Encore\Admin\Show::init(function (\Encore\Admin\Show $show) {
    $show->panel()->tools(function (\Encore\Admin\Show\Tools $tools) {
        $tools->disableDelete();
        $tools->disableEdit();
        $tools->disableList();
    });
});

Encore\Admin\Form::forget(['map', 'editor']);
Encore\Admin\Grid\Filter::extend('mlike', \App\Admin\Extensions\MyLike::class);

