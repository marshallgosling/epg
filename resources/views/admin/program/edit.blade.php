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
                <div class="btn-group">&nbsp; &nbsp;</div>
                <div class="btn-group">
                    <a class="btn btn-info btn-sm" id="tree-save" title="保存"><i class="fa fa-save"></i><span class="hidden-xs"> 保存</span></a>
                </div>
                
                <div class="btn-group">
                    <a class="btn btn-warning btn-sm" title="返回" href="/admin/channel/channelv/programs?channel_id={{$model->channel_id}}"><i class="fa fa-refresh"></i><span class="hidden-xs"> 返回</span></a>
                </div>
                <div class="btn-group">
                    <small>可拖动排序</small>
                </div>
            </div>
            <!-- /.box-header -->
            <div class="box-body table-responsive no-padding">
                <div class="dd" id="tree-programs">
                    <ol class="dd-list">
                        @foreach($data as $idx=>$item)
                        <li class="dd-item" data-id="{{$idx}}">
                            <div class="dd-handle">
                                 {{$item['start_at']}} -- {{$item['end_at']}} <strong>{{$item['name']}}</strong> <small>{{$item['duration']}}</small>【{{@implode(' ', $item['category'])}}】  <a href="#" class="dd-nodrag">{{$item['unique_no']}}</a>
                                <span class="pull-right dd-nodrag">
                                    <a href="javascript:selectProgram({{$idx}});"><i class="fa fa-edit"></i></a>
                                    <a href="javascript:void(0);" data-id="{{$idx}}" class="tree_branch_delete"><i class="fa fa-trash"></i></a>
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
                <h3 class="box-title">已选中节目</h3>
                <div class="box-tools pull-right">
                                </div><!-- /.box-tools -->
            </div><!-- /.box-header -->
            <div class="box-body" style="display: block;">
                <div class="box-body fields-group">
                
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <tr><td width="120px">名称</td><td id="sName"></td></tr>
                            <tr><td>播出编号</td><td id="sNo"></td></tr>
                            <tr><td>栏目</td><td id="sCategory"></td></tr>
                            <tr><td>时长</td><td id="sDuration"></td></tr>
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
                
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <tr><td width="120px">名称</td><td id="pName"></td></tr>
                            <tr><td>播出编号</td><td id="pNo"></td></tr>
                            <tr><td>栏目</td><td id="pCategory"></td></tr>
                            <tr><td>时长</td><td id="pDuration"></td></tr>
                        </table>
                    </div>
                </div>
            </div>
            <!-- /.box-body -->
            <div class="box-footer">
                <div class="col-md-4"></div>

                <div class="col-md-8">
                    
                    <div class="btn-group pull-left">                       
                        <button id="replaceBtn" type="button" class="btn btn-info pull-right">替换</button>
                    </div>
                    
                    <div class="btn-group pull-right">
                        <button id="newBtn" title="新增" type="button" class="btn btn-success pull-right">新增</button>
                    </div>
                </div>
            </div>

        </div><!-- /.box-body -->
    </div>

<script>
    var selectedItem = null;
    var selectedIndex = -1;
    var replaceItem = null;
    var dataList = JSON.parse('{!!$model->data!!}');
    $(function () {
        $('#widget-form-655477f1c8f59').submit(function (e) {
            e.preventDefault();
            $(this).find('div.cascade-group.hide :input').attr('disabled', true);
        });
        $('#tree-programs').nestable({maxDepth: 1});
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
            allowClear:true,placeholder:"标题或播出编号",minimumInputLength:1,
            //templateResult: formatProgram,
            language: "zh_CN",
            escapeMarkup: function (markup) {
                return markup;
            }
        });

        $('.program').on('select2:select', function (e) {
            replaceItem = e.params.data;
            
            formatProgram(replaceItem);
        });
        function formatProgram (repo) {
            $("#pName").html(repo.name);
            $("#pDuration").html(repo.duration);
            $("#pNo").html(repo.unique_no);
            $('#pCategory').html(repo.category);
        }

        $('#newBtn').on('click', function(e) {
            if(replaceItem == null) {         
                toastr.error('请先搜索节目！');
                return;
            }
            replaceItem.name = replaceItem.text;
            dataList.push(replaceItem);

            $.ajax({
                    method: 'post',
                    url: '/admin/channel/channelv/data/{!! $model->id !!}/save',
                    data: {
                        data: JSON.stringify(dataList),
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
                replaceItem.name = replaceItem.text;
                dataList[selectedIndex] = replaceItem;
                console.log(JSON.stringify(dataList));
                
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
                                url: '/admin/channel/channelv/data/{!! $model->id !!}/save',
                                data: {
                                    data: JSON.stringify(dataList),
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
            var newList = [];
            for(var i=0;i<list.length;i++)
            {
                newList[i] = dataList[list[i].id];
            }
            $.ajax({
                    method: 'post',
                    url: '/admin/channel/channelv/data/{!! $model->id !!}/save',
                    data: {
                        data: JSON.stringify(newList),
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
                            url: '/admin/channel/channelv/data/{!! $model->id !!}/remove/' + id,
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
        $("#sDuration").html(repo.duration);
        $("#sNo").html(repo.unique_no);
        $('#sCategory').html(repo.category.join(' '));
        selectedItem = repo;
        selectedIndex = idx;
    }
</script></div></div>