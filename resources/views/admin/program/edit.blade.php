<div class="row">
<form id="widget-form-655477f1c8f59" method="POST" action="/admin/channel/channelv/programs/{{$model->id}}" class="form-horizontal" accept-charset="UTF-8" pjax-container="1">
    <div class="box-body fields-group">
     
                    <input type="hidden" name="name" value="{{$model->name}}" id="name">
                    <input type="hidden" name="schedule_start_at" value="{{$model->schedule_start_at}}" id="schedule_start_at">
                    <input type="hidden" name="schedule_end_at" value="{{$model->schedule_end_at}}" id="schedule_end_at">
                    <input type="hidden" name="start_at" value="{{$model->start_at}}" id="start_at">
                    <input type="hidden" name="end_at" value="{{$model->end_at}}" id="end_at">
                    <input type="hidden" name="duration" value="{{$model->duration}}" id="duration">
                    <input type="hidden" name="data" value='{{$model->data}}' id="data">
    </div>
    <input type="hidden" name="_method" value="PUT" class="_method">
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
                    <a class="btn btn-warning btn-sm tree-654ce72915b12-refresh" title="刷新"><i class="fa fa-refresh"></i><span class="hidden-xs"> 刷新</span></a>
                </div>
                
            </div>
            <!-- /.box-header -->
            <div class="box-body table-responsive no-padding">
                <div class="dd" id="tree-programs">
                    <ol class="dd-list">
                        @foreach($data as $idx=>$item)
                        <li class="dd-item" data-id="{{$idx}}">
                            <div class="dd-handle">
                                <i class="fa fa-bars"></i> {{$item['duration']}} <strong>{{$item['name']}}</strong> 【{{@implode(' ', $item['category'])}}】  <a href="#" class="dd-nodrag">{{$item['unique_no']}}</a>
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
            <!-- /.box-body -->
            <div class="box-footer">
            <div class="col-md-4"></div>

            <div class="col-md-8">
                
                            <div class="btn-group pull-right">
                    <button id="replaceBtn" type="button" class="btn btn-info pull-right">替换</button>
                </div>
                        </div>
            </div>


        


        </div><!-- /.box-body -->
    </div>

<script>
    var selectedItem = null;
    var selectedIndex = -1;
    var replaceItem = null;
    var dataList = JSON.parse('{!! $model->data !!}');
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
            escapeMarkup: function (markup) {
                return markup;
            }
        });

        $('.program').on('select2:select', function (e) {
            replaceItem = e.params.data;
            
            formatProgram(replaceItem);
        });
        function formatProgram (repo) {
            $("#pName").html(repo.text);
            $("#pDuration").html(repo.duration);
            $("#pNo").html(repo.unique_no);
            $('#pCategory').html(repo.category);
        }

        $('#replaceBtn').on('click', function(e) {
            if(selectedIndex > -1) {
                replaceItem.name = replaceItem.text;
                dataList[selectedIndex] = replaceItem;
                console.log(JSON.stringify(dataList));
                $('#data').val(JSON.stringify(dataList));
                $('#widget-form-655477f1c8f59').submit();
            }
            
        });

        $('#tree-save').on('click', function(e) {
            var list = $('#tree-programs').nestable('serialize');
            var newList = [];
            for(var i=0;i<list.length;i++)
            {
                newList[i] = dataList[list[i].id];
            }
            console.log(newList);
            $('#data').val(JSON.stringify(newList));
            $('#widget-form-655477f1c8f59').submit();
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
        console.log("select Program "+idx);
    }
</script></div></div>