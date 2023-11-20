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
                    <div class="dropdown">
                        <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            {{$model->start_at}} {{$model->name}}
                            <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu">
                            @foreach($list as $item) 
                            <li><a href="./{{$item->id}}">{{$item->start_at}} {{$item->name}}</a></li>
                            @endforeach
                        </ul>
                    </div>
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
                <div class="dd">
                    <span id="treeinfo"><small>可拖动排序</small></span>
                    <span class="pull-right"><small>共 {{ @count($data) }} 条记录</small></span>
                </div>
                <div class="dd" id="tree-programs">
                    <ol class="dd-list">
                        @foreach($data as $idx=>$item)
                        <li class="dd-item" data-id="{{$idx}}">
                            <div class="dd-handle">
                                <small>类型：</small> <span class="label label-{{ \App\Models\TemplatePrograms::LABELS[$item['type']] }}">{{ \App\Models\TemplatePrograms::TYPES[$item['type']] }}</span> <small>&nbsp;栏目：</small> <a href="#" class="dd-nodrag">{{$item['category']}}</a> &nbsp;<small> 别名：</small> {{$item['name']}} &nbsp;<small class="text-warning">{{$item['data']}}</small>
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
    <div class="col-md-4" >
        <div data-spy="affix" data-offset-top="10"> 
        <div class="box box-info" >
                <div class="box-header with-border">
                <h3 class="box-title">已选中栏目</h3>
                <div class="box-tools pull-right"></div><!-- /.box-tools -->
            </div><!-- /.box-header -->
            <div class="box-body" style="display: block;">
                <div class="box-body fields-group">
                
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <tr><td width="120px">别名</td><td><input type="text" id="sName" class="form-control" placeholder="名称及描述"></td></tr>
                            <tr><td>栏目</td><td><input type="text" id="sCategory" class="form-control" placeholder="栏目编号"></td></tr>
                            <tr><td>类型</td><td>
                                <span class="icheck">
                                    <label class="radio-inline">
                                        <input type="radio" name="type" value="0" class="minimal type"> 节目  
                                    </label>
                                </span>

                                <span class="icheck">
                                    <label class="radio-inline">
                                        <input type="radio" name="type" value="1" class="minimal type"> 广告 
                                    </label>
                                </span>

                                <span class="icheck">
                                    <label class="radio-inline">
                                        <input type="radio" name="type" value="2" class="minimal type"> 垫片 
                                    </label>
                                </span></td></tr>
                            <tr><td>编号</td><td><input type="text" id="code" class="form-control" placeholder="播出编号"></td></tr>
                        </table>
                    </div>
                </div>

            </div>
            <div class="box-footer">
                <div class="col-md-4"></div>

                <div class="col-md-8">
                    
                    <div class="btn-group pull-right">                       
                        <button id="editBtn" type="button" class="btn btn-info pull-right">保存</button>
                        &nbsp;
                        &nbsp;
                    </div>
                    
                    <div class="btn-group pull-right">
                        <button id="newBtn" title="新增" type="button" class="btn btn-success pull-right">新增</button>
                        &nbsp;&nbsp;
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
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <tr><td>栏目</td><td><select class="form-control category" style="width: 100%;" name="category" data-value=""><option value=""></option></select></td></tr>
                            <tr><td>节目</td><td><select class="form-control program" style="width: 100%;" name="program" data-value=""><option value=""></option></select>
                    </td></tr>

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
                    
                    
                </div>
            </div>

        </div><!-- /.box-body -->
        </div>
    </div>

<script>
    var selectedItem = null;
    var selectedIndex = -1;
    var replaceItem = null;
    var replaceCategory = null;
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
        $(".category").select2({
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

        $('.category').on('select2:select', function (e) {
            replaceCategory = e.params.data;
            
        });
        $(".program").select2({
            ajax: {
                url: "/admin/api/programs",
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
        });

        $('#newBtn').on('click', function(e) {
            /*if(replaceItem == null) {         
                toastr.error('请先搜索节目分类！');
                return;
            }*/
            var newItem = {
                name: $('#sName').val(),
                type: $(".type:checked").val(),
                sort: dataList.length + 1,
                category: $('#sCategory').val(),
                data: $('#code').val()
            }

            $.ajax({
                    method: 'post',
                    url: '/admin/template/channelv/tree/{!! $model->id !!}/save',
                    data: {
                        data: JSON.stringify(newItem),
                        action: "append",
                        _token:LA.token,
                    },
                    success: function (data) {
                        $.pjax.reload('#pjax-container');
                        toastr.success('新增成功 !');
                    }
                });
        });

        $('#editBtn').on('click', function(e) {
            if(selectedIndex > -1) {
                
                var newItem = {
                    id: selectedIndex,
                    name: $('#sName').val(),
                    type: $(".type:checked").val(),
                    category: $('#sCategory').val(),
                    data: $('#code').val()
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
                                url: '/admin/template/channelv/tree/{!! $model->id !!}/save',
                                data: {
                                    data: JSON.stringify(newItem),
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
                    url: '/admin/template/channelv/tree/{!! $model->id !!}/save',
                    data: {
                        data: JSON.stringify(dataList),
                        action: "sort",
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
                            url: '/admin/template/channelv/tree/{!! $model->id !!}/remove/'+id,
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

        $('#replaceBtn').on('click', function(e) {
            if(replaceItem) {
                $('#sName').val(replaceItem.name);
                $('#code').val(replaceItem.unique_no);
            }
            if(replaceCategory) ('#sCategory').val(replaceCategory.category);
        });
        
    });
    function selectProgram (idx) {
        var repo = dataList[idx];
        $("#sName").val(repo.name);
        $(".type[value="+repo.type+"]").iCheck("check");
        $("#code").val(repo.data);
        $('#sCategory').val(repo.category);
        selectedItem = repo;
        selectedIndex = repo.id;
    }

    function copyProgram (idx) {
        var repo = dataList[idx];
        repo.sort = dataList.length + 1;
        $.ajax({
            method: 'post',
            url: '/admin/template/tree/{!! $model->id !!}/save',
            data: {
                data: JSON.stringify(repo),
                action: "append",
                _token:LA.token,
            },
            success: function (data) {
                $.pjax.reload('#pjax-container');
                toastr.success('复制成功 !');
            }
        });
    }
</script></div></div>