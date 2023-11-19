<div class="row">
<form id="widget-form-655477f1c8f59" method="POST" action="/admin/channel/channelv/data/{{$model->id}}/save" class="form-horizontal" accept-charset="UTF-8" pjax-container="1">
    <div class="box-body fields-group">
    
                    <input type="hidden" name="data" value='' id="data">
    </div>
    
            <input type="hidden" name="_token" value="{{@csrf_token()}}">
</form>
</div>
<div class="row">
    <div class="col-md-8">
        <div class="box">
            <div class="box-header">
                <div class="btn-group"><b>具体节目编排</b>&nbsp; &nbsp;</div>
                <div class="btn-group">
                    {{$model->start_at}} {{$model->name}}
                </div>
                <div class="btn-group pull-right">
                    
                </div>
                <div class="btn-group">&nbsp; &nbsp;</div>
                <div class="btn-group">&nbsp; &nbsp;</div>
                <div class="btn-group pull-right">
                    <a class="btn btn-info btn-sm" id="tree-save" title="保存"><i class="fa fa-save"></i><span class="hidden-xs"> 保存</span></a>
                    <a class="btn btn-warning btn-sm" title="返回" href="/admin/template/channelv"><i class="fa fa-refresh"></i><span class="hidden-xs"> 返回</span></a>
                </div>
                
            </div>
            <!-- /.box-header -->
            <div class="box-body table-responsive no-padding">
                <div id="treeinfo" class="dd">
                    <small>可拖动排序</small>
                </div>
                <div class="dd" id="tree-programs">
                    <ol class="dd-list">
                        @foreach($data as $idx=>$item)
                        <li class="dd-item" data-id="{{$idx}}">
                            <div class="dd-handle">
                                <small>类型：</small> <span class="label label-{{ \App\Models\TemplatePrograms::LABELS[$item['type']] }}">{{ \App\Models\TemplatePrograms::TYPES[$item['type']] }}</span> <small>&nbsp;栏目：</small> <a href="#" class="dd-nodrag">{{$item['category']}}</a> <small> &nbsp;别名：</small> {{$item['name']}}
                                <span class="pull-right dd-nodrag">
                                    <a href="javascript:selectProgram({{$idx}});" title="选择"><i class="fa fa-edit"></i></a>&nbsp;
                                    <a href="javascript:copyProgram({{$idx}});" title="复制"><i class="fa fa-copy"></i></a>&nbsp;
                                    
                                    <a href="javascript:void(0);" data-id="{{$idx}}" class="tree_branch_delete" title="删除"><i class="fa fa-trash"></i></a>
                                </span>
                            </div>
                        </li>
                        @endforeach
                    </ol>
                </div>
            </div>
            <!-- /.box-body -->
        </div>
    </div>
    <div class="col-md-4">
        <div  class="box box-info">
                <div class="box-header with-border">
                <h3 class="box-title">已选中栏目</h3>
                <div class="box-tools pull-right">
                                </div><!-- /.box-tools -->
            </div><!-- /.box-header -->
            <div class="box-body" style="display: block;">
                <div class="box-body fields-group">
                
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <tr><td width="120px">名称</td><td id="sName"></td></tr>
                            <tr><td>栏目</td><td id="sCategory"></td></tr>
                            <tr><td>类型</td><td id="sType"></td></tr>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title">搜索</h3>
                <div class="box-tools pull-right"></div><!-- /.box-tools -->
            </div><!-- /.box-header -->
            <div class="box-body" style="display: block;">
                <div class="box-body fields-group">
                    <div class="form-group col-sm-12 ">                     
                        <input type="hidden" name="program"><select class="form-control program" style="width: 100%;" name="program" data-value=""><option value=""></option></select>
                    </div>
                </div>
                <div class="box-body fields-group">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <tr><td>名称</td><td><input type="text" id="pName"></td></tr>
                            <tr><td>栏目</td><td id="pCategory"></td></tr>
                            <tr><td>类型</td><td id="pType">
                            
                            <span class="icheck">

                                <label class="radio-inline">
                                    <input type="radio" name="type" value="0" class="minimal type"> 节目  
                                </label>

                            </span>


                            <span class="icheck">

                                <label class="radio-inline">
                                    <input type="radio" name="type" value="1" class="minimal type"> 垫片 
                                </label>

                            </span>

                            <span class="icheck">

                                <label class="radio-inline">
                                    <input type="radio" name="type" value="2" class="minimal type"> 广告 
                                </label>

                            </span>


                            </td></tr>
                        </table>
                    </div>
                </div>
            </div>
            <!-- /.box-body -->
            <div class="box-footer">
                <div class="col-md-4"></div>

                <div class="col-md-8">
                    
                    <div class="btn-group pull-right">                       
                        <button id="replaceBtn" type="button" class="btn btn-info pull-right">替换</button>
                        &nbsp;
                        &nbsp;
                    </div>
                    
                    <div class="btn-group pull-right">
                        <button id="newBtn" title="新增" type="button" class="btn btn-success pull-right">新增</button>
                        &nbsp;&nbsp;
                    </div>
                </div>
            </div>

        </div><!-- /.box-body -->
    </div>

<script>
    var selectedItem = null;
    var selectedIndex = -1;
    var replaceItem = null;
    var dataList = JSON.parse('{!!$data!!}');
    var sortChanged = false;
    $(function () {
        $('#widget-form-655477f1c8f59').submit(function (e) {
            e.preventDefault();
            $(this).find('div.cascade-group.hide :input').attr('disabled', true);
        });
        $('.type').iCheck({radioClass:'iradio_minimal-blue'});          
        $('.after-submit').iCheck({checkboxClass:'icheckbox_minimal-blue'}).on('ifChecked', function () {
            $('.after-submit').not(this).iCheck('uncheck');
        });
        $('#tree-programs').nestable({maxDepth: 1});
        $('#tree-programs').on('change', function() {
            sortChanged = true;
            $('#treeinfo').html('<strong class="text-danger">请别忘记保存排序！</strong>');
        });
        $(".program").select2({
            ajax: {
                url: "/admin/api/category",
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term,
                        page: params.page
                    };
                },
                processResults: function (data, params) {
                    params.page = params.page || 1;

                    return {
                        results: data.data,
                        pagination: {
                        more: data.next_page_url
                        }
                    };
                },
                cache: true
            },
            allowClear:true,placeholder:"标题或编号",minimumInputLength:1,
            //templateResult: formatProgram,
            language: "zh-CN",
            escapeMarkup: function (markup) {
                return markup;
            }
        });

        $('.program').on('select2:select', function (e) {
            replaceItem = e.params.data;
            
            formatProgram(replaceItem);
        });
        function formatProgram (repo) {
            $("#pName").val(repo.name);
            $(".type[value="+repo.type+"]").attr('checked', 'true');
            $('#pCategory').html(repo.category);
        }

        $('#newBtn').on('click', function(e) {
            if(replaceItem == null) {         
                toastr.error('请先搜索节目分类！');
                return;
            }
            replaceItem.type = $(".type:checked").val();
            replaceItem.sort = dataList.length + 1;

            $.ajax({
                    method: 'post',
                    url: '/admin/template/tree/{!! $model->id !!}/append',
                    data: {
                        data: JSON.stringify(replaceItem),
                        action: "append",
                        _token:LA.token,
                    },
                    success: function (data) {
                        $.pjax.reload('#pjax-container');
                        toastr.success('新增成功 !');
                    }
                });
        });

        $('#replaceBtn').on('click', function(e) {
            if(selectedIndex > -1) {
                
                dataList[selectedIndex].name = replaceItem.name;
                dataList[selectedIndex].category = replaceItem.category;
                dataList[selectedIndex].type = replaceItem.type;
                dataList[selectedIndex].haschanged = 1;
                //console.table(dataList); return;

                if(sortChanged) {
                    var list = $('#tree-programs').nestable('serialize');
            
                    for(var i=0;i<list.length;i++)
                    {
                        if(dataList[i].sort != list[i].id) {
                            dataList[i].sort = list[i].id;
                            dataList[i].haschanged = 1;
                        }
                    }
                }

                swal({
                    title: "确认要替换?",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "确认",
                    showLoaderOnConfirm: true,
                    cancelButtonText: "取消",
                    preConfirm: function() {
                        return new Promise(function(resolve) {
                            $.ajax({
                                method: 'post',
                                url: '/admin/template/tree/{!! $model->id !!}/save',
                                data: {
                                    data: JSON.stringify(dataList),
                                    action: "replace",
                                    _token:LA.token,
                                },
                                success: function (data) {
                                    $.pjax.reload('#pjax-container');
                                    toastr.success('替换成功 !');
                                    resolve(data);
                                }
                            });
                        });
                    }
                }).then(function(result) {
                    var data = result.value;
                    if (typeof data === 'object') {
                        if (data.status) {
                            swal(data.message, '', 'success');
                        } else {
                            swal(data.message, '', 'error');
                        }
                    }
                });

                
            }
            else {
                toastr.error('请先选择节目！');
            }
            
        });

        $('#tree-save').on('click', function(e) {
            var list = $('#tree-programs').nestable('serialize');
            
            for(var i=0;i<list.length;i++)
            {
                if(dataList[i].sort != list[i].id) {
                    dataList[i].sort = list[i].id;
                    dataList[i].haschanged = 1;
                }
            }

            //console.table(dataList); return;
            $.ajax({
                    method: 'post',
                    url: '/admin/template/tree/{!! $model->id !!}/save',
                    data: {
                        data: JSON.stringify(dataList),
                        _token:LA.token,
                    },
                    success: function (data) {
                        $.pjax.reload('#pjax-container');
                        toastr.success('保存成功 !');
                    }
                });
        });

        $('.tree_branch_delete').click(function() {
            var id = $(this).data('id');
            swal({
                title: "确认删除?",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "确认",
                showLoaderOnConfirm: true,
                cancelButtonText: "取消",
                preConfirm: function() {
                    return new Promise(function(resolve) {
                        $.ajax({
                            method: 'post',
                            url: '/admin/template/tree/{!! $model->id !!}/remove/'+id,
                            data: {
                                _method:'delete',
                                _token:LA.token,
                            },
                            success: function (data) {
                                $.pjax.reload('#pjax-container');
                                toastr.success('删除成功 !');
                                resolve(data);
                            }
                        });
                    });
                }
            }).then(function(result) {
                var data = result.value;
                if (typeof data === 'object') {
                    if (data.status) {
                        swal(data.message, '', 'success');
                    } else {
                        swal(data.message, '', 'error');
                    }
                }
            });
        });
        
    });
    function selectProgram (idx) {
        var repo = dataList[idx];
        $("#sName").html(repo.name);
        $("#sType").html(repo.type);
        $('#sCategory').html(repo.category[0]);
        selectedItem = repo;
        selectedIndex = idx;
    }

    function copyProgram (idx) {
        var repo = dataList[idx];
        repo.sort = dataList.length + 1;
        $.ajax({
            method: 'post',
            url: '/admin/template/tree/{!! $model->id !!}/append',
            data: {
                data: JSON.stringify(repo),
                action: "copy",
                _token:LA.token,
            },
            success: function (data) {
                $.pjax.reload('#pjax-container');
                toastr.success('复制成功 !');
            }
        });
    }
</script></div></div>