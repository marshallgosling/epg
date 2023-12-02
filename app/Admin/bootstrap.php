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

Encore\Admin\Grid::init(function (Encore\Admin\Grid $grid) {
    $grid->disableExport();
    $grid->paginate(30);
    $grid->filter(function (Encore\Admin\Grid\Filter $filter) {
        $filter->disableIdFilter();
        if(array_key_exists('expand', $_REQUEST))$filter->expand();
        $filter->setAction('?expand=1');
    });
});

Encore\Admin\Form::forget(['map', 'editor']);

Encore\Admin\Form::init(function (Encore\Admin\Form $form) {

    $form->disableEditingCheck();

    $form->disableCreatingCheck();

    $form->disableViewCheck();

    
});
