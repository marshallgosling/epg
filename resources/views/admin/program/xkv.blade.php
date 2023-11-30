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
                            <li><a href="./{{$item->id}}" target="_top">{{$item->start_at}} {{$item->name}}</a></li>
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
                    <a id="newBtn" title="新增" class="btn btn-success btn-sm" href="javascript:showSearchModal('new');">新增</a>
                    <span id="total" class="pull-right"></span>
                </div>
                <div class="dd" id="tree-programs">
                    
                </div>
            </div>
            <!-- /.box-body -->
        </div>
    </div>
</div></div>
<!-- Modal -->
<div class="modal fade" id="searchModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
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
                    <div class="table-responsive" style="height: 450px; overflow-y:scroll">
                        <table class="table table-search table-hover table-striped">
                            
                        </table>
                        <div id="noitem" style="display:block"><strong>没有找到任何记录</strong></div>
                    </div>
                    <ul class="pager">
                         <li><a id="moreBtn" style="margin:0;display:none;" href="#">载入更多</a> <small id="totalSpan" class="pull-right"></small></li>
                    </ul>

                </div>
      </div>
      <div class="modal-footer">
        <button id="confirmBtn" type="button" class="btn btn-info" disabled="true">确认</button>      
        <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<div id="template" style="display: none">{!!$template!!}</div>
<script type="text/javascript">
    var selectedItem = null;
    var selectedIndex = -1;
    var replaceItem = [];
    var sortChanged = false;
    var dataList = JSON.parse('{!!$json!!}');
    var sortEnabled = false;
    var cachedPrograms = null;
    var uniqueAjax = null;
    var backupList = JSON.parse(JSON.stringify(dataList));
    var curPage = 1;
    var keyword = '';
    var loadingMore = false;
    $(function () {
        $('#widget-form-655477f1c8f59').submit(function (e) {
            e.preventDefault();
            $(this).find('div.cascade-group.hide :input').attr('disabled', true);
        });

        $('#btnDelete').on('click', function(e) {

            if(sortEnabled) {
                toastr.error("请先保存排序结果。");
                return;
            }
            var selected = [];

            $('.grid-row-checkbox:checked').each(function () {
                selected.push($(this).data('id'));
            });

            if (selected.length == 0) {
                toastr.error("请先勾选需要删除的节目。");
                return;
            }
            for(i=selected.length-1;i>=0;i--)
            {
                dataList.splice(selected[i], 1);
            }
            reCalculate(0);
            reloadTree();
        });

        $('#btnSort').on('click', function(e) {
            if($('#btnSort').html() != '保存') {
                $('#tree-programs').nestable({maxDepth: 1});
                $('#tree-programs').on('change', function() {
                    sortChanged = true;
                    $('#treeinfo').html('<strong class="text-danger">请别忘记保存排序！</'+'strong>');
                });
                sortEnabled = true;
                $('#btnSort').html("保存");
                $('#treeinfo').html('<small>可拖动排序</'+'small>');
            } 
            else {
                var newList = [];
                if(sortChanged) {
                    var list = $('#tree-programs').nestable('serialize');
                    
                    for(var i=0;i<list.length;i++)
                    {
                        newList[i] = dataList[list[i].id];
                    }
                }
                else {
                    newList = dataList;     
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

        $('#moreBtn').on('click', function(e) {
            if(loadingMore) return;
            loadingMore = true;
            curPage ++;
            $.ajax({
                url: "/admin/api/tree/programs",
                dataType: 'json',
                data: {
                    q: keyword,
                    p: curPage
                },
                success: function (data) {
                    loadingMore = false;
                    var items = data.result;
                    
                    var idx = cachedPrograms.length;
                    cachedPrograms = cachedPrograms.concat(items);
                    if(data.total > cachedPrograms.length) $('#moreBtn').show();
                    else $('#moreBtn').hide();
                    var html = '';
                    for(i=0;i<items.length;i++)
                    {
                        item = items[i];
                        tr = '';
                        if(item.black) tr = ' danger';
                        if(item.artist==null) item.artist='';
                        html += '<tr class="search-item'+tr+'" onclick="selectProgram('+idx+')"><td>'+(idx+1)+'</td><td>'+item.unique_no+'</td><td>'+item.name+'</td><td>'+item.artist+'</td><td>'+item.duration+'</td><td>'+item.category+'</td></tr>';
                        idx ++;
                    }
                    
                    $('.table-search').append(html);
                },
                error: function() {
                    loadingMore = false;
                }
            })
        });
        
        $("#keyword").on('change', function(e) {
            if(uniqueAjax) uniqueAjax.abort();
            keyword = e.currentTarget.value;
            cachedPrograms = [];
            curPage = 1
            uniqueAjax = $.ajax({
                url: "/admin/api/tree/programs",
                dataType: 'json',
                data: {
                    q: keyword,
                    p: curPage
                },
                success: function (data) {
                    uniqueAjax = null;
                    var items = data.result;
                    cachedPrograms = cachedPrograms.concat(items);
                    selectedItem = null;

                    if(data.total == 0) {
                        $('#noitem').show();
                        $('#totalSpan').html('');
                        return;
                    }
                    $('#noitem').hide();
                    $('#totalSpan').html("共找到 " + data.total + " 条节目");
                    var head = ['序号','播出编号','名称','艺人','时长','栏目'];
                    var html = '<tr><th>'+head.join('</th><th>')+'</th></tr>';
                    if(data.total > cachedPrograms.length) $('#moreBtn').show();
                    else $('#moreBtn').hide();
                    for(i=0;i<items.length;i++)
                    {
                        item = items[i];
                        tr = '';
                        if(item.black) tr = ' danger';
                        if(item.artist==null) item.artist='';
                        html += '<tr class="search-item'+tr+'" onclick="selectProgram('+i+')"><td>'+(i+1)+'</td><td>'+item.unique_no+'</td><td>'+item.name+'</td><td>'+item.artist+'</td><td>'+item.duration+'</td><td>'+item.category+'</td></tr>';
                    }
                    
                    $('.table-search').html(html);
                },
                error: function() {
                    uniqueAjax = null;
                }
            })
        });

        $('#confirmBtn').on('click', function(e) {
            if(!selectedItem) {
                toastr.error('请先选择节目！');
            }

            $(this).attr('disabled', 'true');

            $('#searchModal').modal('hide');


            if(selectedIndex == 'new') {
                
                selectedIndex = dataList.length;
                
                dataList.push(selectedItem);
                replaceItem.push(selectedItem.unique_no.toString());
                reCalculate(selectedIndex);
            }
            else {
                if(selectedItem.black) {
                    toastr.error("该艺人以上黑名单，不能使用");
                    return;
                }
                dataList[selectedIndex] = selectedItem;
                replaceItem.push(selectedItem.unique_no.toString());
                reCalculate(selectedIndex);
            }

            
            reloadTree();
            
            $('#btnSort').html("保存");
            $('#treeinfo').html('<strong class="text-danger">请别忘记保存修改！</'+'strong>');
            selectedItem = false;
            $('.search-item').removeClass('info');
            
        });
        
        reloadTree();
    });

    function showSearchModal(idx) {
        if(sortEnabled) {
            toastr.error("请先保存排序结果。");
            return;
        }
        selectedIndex = idx;
        $('#searchModal').modal('show');
        $('#confirmBtn').removeAttr('disabled');
    }

    function selectProgram (idx) {
        var repo = cachedPrograms[idx];
        if(repo.black) {
            toastr.error("该节目以上黑名单");
        }
        if(repo.category) {
            repo.category = repo.category.toString().split(',')[0];
        }
        if(repo.artist==null) repo.artist='';
        repo.isnew = true;
        selectedItem = repo;

        $('.search-item').removeClass('info');
        $('.search-item').eq(idx).addClass('info');
    }

    function deleteProgram (idx) {
        if(sortEnabled) {
            toastr.error("请先保存排序结果。");
            return;
        }
        dataList.splice(idx, 1);
        reCalculate(idx);
        reloadTree();
    }

    function reloadTree()
    {
        var html = '<ol class="dd-list">';
        var total = 0;
        for(i=0;i<dataList.length;i++)
        {
            var style = '';
            if(in_array(dataList[i].unique_no, replaceItem)) style = 'bg-danger';
            html += createItem(i, dataList[i], style);
            total += parseDuration(dataList[i].duration);
        }
        html += '</'+'ol>';

        $('#tree-programs').html(html);
        var d = Date.parse('2000/1/1 00:00:00');
        $('#total').html('<small>总时长 '+ formatTime(d+total*1000) +', 共 '+dataList.length+' 条记录</'+'small>');
    }

    function reCalculate(idx) {

        var start = idx == 0 ? Date.parse('{{$model->start_at}}') : Date.parse('2020-1-1 ' + dataList[idx-1].end_at);
        console.log("start:"+dataList[idx-1].end_at);
        for(i=idx;i<dataList.length;i++)
        {
            dataList[i].start_at = formatTime(start);
            start += parseDuration(dataList[i].duration) * 1000;
            dataList[i].end_at = formatTime(start);
        }
    }

    function formatTime($time) {
        var d = new Date($time);
        var a = [];
        a[0] = d.getHours() > 9 ? d.getHours().toString() : '0'+d.getHours().toString();
        a[1] = d.getMinutes() > 9 ? d.getMinutes().toString() : '0'+d.getMinutes().toString();
        a[2] = d.getSeconds() > 9 ? d.getSeconds().toString() : '0'+d.getSeconds().toString();
        return a.join(':');
    }

    function createItem(idx, item, style) {
        var html = $('#template').html();
        var textstyle = "";
        if(style == '') style = parseBg(item.category, item.unique_no);
        if(style == 'bg-danger') textstyle = 'text-danger';
        return html.replace(/idx/g, idx).replace(/textstyle/g, textstyle).replace('start_at', item.start_at).replace('end_at', item.end_at)
                    .replace('name', item.name).replace('duration', item.duration).replace('artist', item.artist)
                    .replace('category', item.category).replace('unique_no', item.unique_no).replace('bgstyle', style);
    }

    function parseBg($no, $code)
    {
        if($no == 'm1') return 'bg-warning';
        if($no == 'v1') return 'bg-default';
        if($code.match(/VCNM(\w+)/)) return 'bg-info';
        return '';
    }
    function parseDuration($dur)
    {
        $d = $dur.toString().split(':');
        return parseInt($d[0])*3600+parseInt($d[1])*60+parseInt($d[2]);
    }

    function in_array(search,array){
        for(var i in array){
            if(array[i]==search){
                return true;
            }
        }
        return false;
    }
</script>