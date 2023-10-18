<?php

namespace App\Admin\Controllers;

use App\Models\Records;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class RecordsController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Records';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Records());

        $grid->column('id', __('Id'));
        $grid->column('name', __('Name'));
        $grid->column('tape_no', __('Tape no'));
        $grid->column('type', __('Type'));
        $grid->column('album', __('Album'));
        $grid->column('artist', __('Artist'));
        $grid->column('co_artist', __('Co artist'));
        $grid->column('gender', __('Gender'));
        $grid->column('mood', __('Mood'));
        $grid->column('energy', __('Energy'));
        $grid->column('tempo', __('Tempo'));
        $grid->column('duration', __('Duration'));
        $grid->column('genre', __('Genre'));
        $grid->column('author', __('Author'));
        $grid->column('word', __('Word'));
        $grid->column('company', __('Company'));
        $grid->column('sp', __('Sp'));
        $grid->column('lang', __('Lang'));
        $grid->column('comment', __('Comment'));
        $grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'));

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(Records::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('tape_no', __('Tape no'));
        $show->field('type', __('Type'));
        $show->field('album', __('Album'));
        $show->field('artist', __('Artist'));
        $show->field('co_artist', __('Co artist'));
        $show->field('gender', __('Gender'));
        $show->field('mood', __('Mood'));
        $show->field('energy', __('Energy'));
        $show->field('tempo', __('Tempo'));
        $show->field('duration', __('Duration'));
        $show->field('genre', __('Genre'));
        $show->field('author', __('Author'));
        $show->field('word', __('Word'));
        $show->field('company', __('Company'));
        $show->field('sp', __('Sp'));
        $show->field('lang', __('Lang'));
        $show->field('comment', __('Comment'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Records());

        $form->text('name', __('Name'));
        $form->text('tape_no', __('Tape no'));
        $form->text('type', __('Type'));
        $form->text('album', __('Album'));
        $form->text('artist', __('Artist'));
        $form->text('co_artist', __('Co artist'));
        $form->text('gender', __('Gender'));
        $form->text('mood', __('Mood'));
        $form->text('energy', __('Energy'));
        $form->text('tempo', __('Tempo'));
        $form->number('duration', __('Duration'));
        $form->text('genre', __('Genre'));
        $form->text('author', __('Author'));
        $form->text('word', __('Word'));
        $form->text('company', __('Company'));
        $form->text('sp', __('Sp'));
        $form->text('lang', __('Lang'));
        $form->text('comment', __('Comment'));

        return $form;
    }
}
