<div class="row">
<form id="widget-form-655477f1c8f59" method="POST" action="/admin/channel/xkv/data/{{$model->id}}/save" class="form-horizontal" accept-charset="UTF-8" pjax-container="1">
    <div class="box-body fields-group">
    
                    <input type="hidden" name="data" value='' id="data">
    </div>
    
            <input type="hidden" name="_token" value="{{@csrf_token()}}">
</form>
</div>
<div class="row">
    <div class="col-md-12">
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
                <div class="btn-group">&nbsp; &nbsp;</div>
               
                <div class="btn-group pull-right">
                    <a class="btn btn-warning btn-sm" title="返回" href="/admin/channel/xkv/programs?channel_id={{$model->channel_id}}"><i class="fa fa-arrow-left"></i><span class="hidden-xs"> 返回</span></a>
                </div>
                
            </div>
            <!-- /.box-header -->
            <div class="box-body table-responsive no-padding">
                <div class="dd">
                    
                    <span id="treeinfo"></span>
                    <a id="btnSort" class="btn btn-info btn-sm">开启排序</a>
                    <a id="btnDelete" class="btn btn-danger btn-sm">批量删除</a>
                    <span class="pull-right"><small>共 {{ @count($data) }} 条记录</small></span>
                </div>
                <div class="dd" id="tree-programs">
                    <ol class="dd-list">
                        @foreach($data as $idx=>$item)
                        <li class="dd-item" data-id="{{$idx}}">
                            <div class="dd-handle {{ \App\Models\Category::parseBg($item['category'], $item['unique_no']) }}">
                                <input type="checkbox" class="grid-row-checkbox" data-id="{{$idx}}" autocomplete="off">                    
                                <span style="display:inline-block;width:120px;margin-left:10px;">{{$item['start_at']}} -- {{$item['end_at']}} </span>
                                <span style="display:inline-block;width:120px;"><a class="dd-nodrag" href="#" data-toggle="modal" data-target="#searchModal" data-id="{{$idx}}">{{$item['unique_no']}}</a></span>
                                <span style="display:inline-block;width:300px;text-overflow:ellipsis"><strong>{{$item['name']}}</strong></span>
                                <span style="display:inline-block;width:80px;"><small>{{$item['duration']}}</small></span>
                                <span style="display:inline-block;width:60px;">【{{$item['category']}}】</span>
                                <span style="display:inline-block;width:300px;text-overflow:ellipsis">{{ $item['artist'] }}</span>
                                
                                <span class="pull-right dd-nodrag">
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
</div></div>
<!-- Modal -->
<div class="modal fade bs-example-modal-lg" id="searchModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">搜索</h4>
      </div>
      <div class="modal-body">
        <div class="fields-group">
                    <div class="form-group">                     
                        <input type="text" class="form-control" name="keyword" id="keyword" placeholder="请输入关键字">
                    </div>
                </div>
                <div class="fields-group">
                    <div class="table-responsive table-search" style="height: 500px; overflow-y:scroll">
                        
                    </div>
                </div>
      </div>
      <div class="modal-footer">
        <button id="replaceBtn" type="button" class="btn btn-info">替换</button>
        <button id="newBtn" title="新增" type="button" class="btn btn-success">新增</button>
        <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<script type="text/javascript">
    var selectedItem = null;
    var selectedIndex = -1;
    var replaceItem = null;
    var sortChanged = false;
    var dataList = JSON.parse('{!!$json!!}');
    var sortEnabled = false;
    var cachedPrograms = null;
    var uniqueAjax = null;
    $(function () {
        $('#widget-form-655477f1c8f59').submit(function (e) {
            e.preventDefault();
            $(this).find('div.cascade-group.hide :input').attr('disabled', true);
        });
        $('#searchModal').on('show.bs.modal', function (event) {
            selectedIndex = $(event.relatedTarget).data('id');
        }).on('hidden.bs.modal', function (e) {
            
        });
        $('.grid-row-checkbox').iCheck({
            checkboxClass:'icheckbox_minimal-blue'
        });
        $('#btnDelete').on('click', function(e) {
            var selected = [];

            $('.grid-row-checkbox:checked').each(function () {
                selected.push($(this).data('id'));
            });

            if (selected.length == 0) {
                return;
            }

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
                            url: '/admin/channel/xkv/data/{!! $model->id !!}/remove/' + selected.join('_'),
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
        $('#btnSort').on('click', function(e) {
            if(!sortEnabled) {
                $('#tree-programs').nestable({maxDepth: 1});
                $('#tree-programs').on('change', function() {
                    sortChanged = true;
                    $('#treeinfo').html('<strong class="text-danger">请别忘记保存排序！</strong>');
                });
                sortEnabled = true;
                $('#btnSort').html("保存");
                $('#treeinfo').html('<small>可拖动排序</small>');
            }
            else {
                var list = $('#tree-programs').nestable('serialize');
                var newList = [];
                for(var i=0;i<list.length;i++)
                {
                    newList[i] = dataList[list[i].id];
                }
                $.ajax({
                    method: 'post',
                    url: '/admin/channel/xkv/data/{!! $model->id !!}/save',
                    data: {
                        data: JSON.stringify(newList),
                        _token:LA.token,
                    },
                    success: function (data) {
                        $.pjax.reload('#pjax-container');
                        toastr.success('保存成功 !');
                    }
                });

                sortEnabled = false;
            }
        });
        
        $("#keyword").on('change', function(e) {
            if(uniqueAjax) uniqueAjax.abort();
            uniqueAjax = $.ajax({
                url: "/admin/api/programs",
                dataType: 'json',
                data: {
                    q: e.currentTarget.value,
                },
                success: function (data) {
                    uniqueAjax = null;
                    var head = ['播出编号','名称','艺人','时长','栏目'];
                    var html = '<table class="table table-hover table-striped"><tr><th>'+head.join('</th><th>')+'</th></tr>';
                    var items = data.result;
                    for(i=0;i<items.length;i++)
                    {
                        item = items[i];
                        tr = '';
                        if(item.black) tr = ' danger';
                        html += '<tr class="search-item'+tr+'" onclick="selectProgram('+i+')"><td>'+item.unique_no+'</td><td>'+item.name+'</td><td>'+item.artist+'</td><td>'+item.duration+'</td><td>'+item.category+'</td></tr>';
                    }
                    html += '</table>';
                    $('.table-search').html(html);
                    cachedPrograms = items;
                },
                error: function() {
                    uniqueAjax = null;
                }
            })
        });

        $('#newBtn').on('click', function(e) {
            if(replaceItem == null) {         
                toastr.error('请先搜索节目！');
                return;
            }
            dataList.push(replaceItem);

            $.ajax({
                    method: 'post',
                    url: '/admin/channel/xkv/data/{!! $model->id !!}/save',
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

                // if(replaceItem.black) {
                //     toastr.error("该艺人以上黑名单，不能使用");
                //     return;
                // }
                dataList[selectedIndex] = replaceItem;
                console.log(JSON.stringify(dataList));

                var spans = $('.dd-handle').eq(selectedIndex).children('span');
                spans.eq(1).children('a').html(selectedItem.unique_no);
                spans.eq(2).html('<strong class="danger">'+selectedItem.name+'</strong>');
                spans.eq(3).html('<small>'+selectedItem.duration+'</small>');
                //spans.eq(2).html('<strong>'.selectedItem.name.'</strong>');
                spans.eq(5).html(selectedIem.artist);
                
            }
            else {
                toastr.error('请先选择节目！');
            }
            
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
                            url: '/admin/channel/xkv/data/{!! $model->id !!}/remove/' + id,
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

    function editProgram(idx) {

    }
    function selectProgram (idx) {
        var repo = cachedPrograms[idx];
        if(repo.black) {
            toastr.error("该节目以上黑名单");
        }
        selectedItem = repo;
        selectedIndex = idx;
        $('.search-item').removeClass('info');
        $('.search-item').eq(idx).addClass('info');
    }
</script>