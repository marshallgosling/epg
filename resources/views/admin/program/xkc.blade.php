<div class="row">
    <div class="col-md-12">
        <div class="box">
            <div class="box-header">
                <div class="btn-group"><b>具体节目编排</b>&nbsp; &nbsp;</div>
                
                <div class="btn-group">
                    <div class="dropdown">
                        <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            {{@substr($model->start_at, 11)}} -- {{@substr($model->end_at, 11)}} {{$model->name}} 
                            <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu">
                            @foreach($list as $item) 
                            <li class="{{$item->id == $model->id ? 'bg-info':''}}"><a href="./{{$item->id}}" target="_top">{{@substr($item->start_at, 11)}} -- {{@substr($item->end_at, 11)}} {{$item->name}}</a></li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div class="btn-group">&nbsp; &nbsp;</div>
               
                <div class="btn-group pull-right">
                    <a class="btn btn-warning btn-sm" title="返回" href="../programs?channel_id={{$model->channel_id}}"><i class="fa fa-arrow-left"></i><span class="hidden-xs"> 返回</span></a>
                </div>
                
            </div>
            <!-- /.box-header -->
            <div class="box-body table-responsive no-padding">
                <div class="dd">
                    
                    <span id="treeinfo"></span>
                    <a id="btnSort" class="btn btn-info btn-sm"><i class="fa fa-sort-numeric-asc"></i> 开启排序</a>
                    <a id="btnDelete" class="btn btn-danger btn-sm"><i class="fa fa-trash"></i> 批量删除</a>
                    <a id="newBtn" title="新增" class="btn btn-success btn-sm" href="javascript:showSearchModal('new');"><i class="fa fa-plus"></i> 新增</a>
                    <a id="btnRollback" disabled="true" class="btn btn-warning btn-sm" title="回退"><i class="fa fa-rotate-left"></i> 回退</a>
            
                    <span id="total" class="pull-right"></span>
                </div>
                <div class="dd" id="tree-programs">
                    
                </div>
            </div>
            <!-- /.box-body -->
        </div>
    </div>
</div>
<!-- Modal -->
<div class="modal fade" id="searchModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">搜索</h4>
      </div>
      <div class="modal-body">
        <div class="row">
            <div class="col-md-12">
            <form class="form-inline">
                <div class="form-group">
                    <div class="input-group">
                        <span class="input-group-addon">栏目</span>
                        <select class="form-control category" id="category" style="width:200px" >
                        <option value=""></option>
                        @foreach($categories as $key=>$value)
                        <option value="{{$key}}">{{$value}}</option>
                        @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <div class="input-group">
                    <div class="input-group-btn">
                        <button id="modalBtn" type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span id="modalText">关键字</span> <span class="caret"></span></button>
                        <ul class="dropdown-menu">
                            <li><a href="javascript:changeModel('关键字');">关键字</a></li>
                            <li><a href="javascript:changeModel('剧集名');">剧集名</a></li>
                        </ul>
                    </div><!-- /btn-group -->
                    <input type="text" class="form-control" style="width:240px" name="keyword" id="keyword" placeholder="请输入关键字, 输入%作为通配符">
                    </div>
                </div> 
                <div class="form-group">
                    <div class="input-group">
                    <span class="input-group-addon">
                        节目库
                    </span>
                    <select class="form-control library" id="library" data-value="records">
                        <option value="records" selected>星空中国</option><option value="record2">星空国际</option><option value="program">V China</option>
                    </select>
                    <span class="input-group-btn">
                        <button id="btnSearch" class="btn btn-info" type="button">搜索</button>
                    </span>
                    </div>
                </div></form>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="table-responsive" style="height:500px; overflow-y:scroll">
                    <table class="table table-search table-hover table-striped">
                                
                    </table>
                    <div id="noitem" style="display:block"><strong>请输入关键字查询</strong></div>
                </div>
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <div class="pull-left">
            <ul class="pager" style="margin:0;">
                <li><a id="moreBtn" style="margin:0;display:none;" href="#">载入更多 <i class="fa fa-angle-double-right"></i></a>
                <small id="totalSpan"></small>
                </li>
            </ul>
        </div>
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
    var backupList = [];
    var curPage = 1;
    var keyword = '';
    var loadingMore = false;
    var modal = '关键字';
    $(function () {
        $('#widget-form-655477f1c8f59').submit(function (e) {
            e.preventDefault();
            $(this).find('div.cascade-group.hide :input').attr('disabled', true);
        });
        $(".category").select2({
            placeholder: {"id":"","text":"请选择栏目"},
            "allowClear":true
        });
        $(".library").select2();
        $('body').on('mouseup', function(e) {
            startmove = false;
            templist = [];
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
            $('#btnSort').html("保存");
            $('#treeinfo').html('<strong class="text-danger">请别忘记保存修改！</'+'strong>');
        });

        $('#btnRollback').on('click', function(e) {
            if(backupList.length == 0) return;
            if(sortEnabled) {
                toastr.error("请先保存排序结果。");
                return;
            }
            //console.log("rollback data");
            dataList = backupList.pop();
            console.table(dataList);
            reloadTree();
            toastr.success("回退成功");
            if(backupList.length==0)
                $('#btnRollback').attr('disabled', true);
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
                    url: '/admin/channel/xkc/data/{!! $model->id !!}/save',
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
                url: "/admin/api/tree/records",
                dataType: 'json',
                data: {
                    q: keyword,
                    c: $('#category').val(),
                    m: modal,
                    t: $('#library').val(),
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
            //searchKeywords(e.currentTarget.value);
        });

        $('#btnSearch').on('click', function(e) {
            searchKeywords($('#keyword').val());
        });

        $('#confirmBtn').on('click', function(e) {
            if(!selectedItem) {
                toastr.error('请先选择节目！');
            }

            $(this).attr('disabled', 'true');

            $('#searchModal').modal('hide');

            backupData();

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

    function changeModel(v)
    {
        $('#modalText').html(v);
        modal = v;
    }

    function searchKeywords(k)
    {
        if(uniqueAjax) uniqueAjax.abort();
            keyword = k;
            $('#noitem').html('搜索数据中...');
            $('.table-search').html('');
            cachedPrograms = [];
            curPage = 1
            uniqueAjax = $.ajax({
                url: "/admin/api/tree/records",
                dataType: 'json',
                data: {
                    q: keyword,
                    c: $('#category').val(),
                    m: modal,
                    t: $('#library').val(),
                    p: curPage
                },
                success: function (data) {
                    uniqueAjax = null;
                    var items = data.result;
                    cachedPrograms = cachedPrograms.concat(items);
                    selectedItem = null;

                    if(data.total == 0) {
                        $('#noitem').show();
                        $('#noitem').html('<strong>没有找到任何记录</strong>');
                        $('#totalSpan').html('');
                        return;
                    }
                    $('#noitem').hide();
                    $('#totalSpan').html("共找到 " + data.total + " 条节目（每次载入 20 条）");
                    var head = ['序号','播出编号','名称','其他','时长','栏目'];
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
                    $('#noitem').show();
                    $('#noitem').html('<strong>没有找到任何记录</strong>');
                }
            });
    }

    function backupData() {
        backupList.push(JSON.parse(JSON.stringify(dataList)));
        console.log("backupData()");
        $('#btnRollback').removeAttr('disabled');
    }


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
        $('#btnSort').html("保存");
        $('#treeinfo').html('<strong class="text-danger">请别忘记保存修改！</'+'strong>');
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
        var $chkboxes = $('.grid-row-checkbox');
        setupMouseEvents();
    }

    var $chkboxes;
    var startmove = false;
    var templist = [];
    function setupMouseEvents()
    {
        templist = [];
        $('.dd-item').on('mousedown', function(e) {
            e.preventDefault();
            let idx = parseInt($(this).data('id'));
            startmove = true;
            let ch = $chkboxes.eq(idx);
            ch.prop('checked', !ch.prop('checked'));
            
        });
        $('.dd-item').on('mouseenter', function(e) {
            if(startmove) {
                var idx = parseInt($(this).data('id'));
                
                if(templist.indexOf(idx) == -1) {
                    templist.push(idx);
                    var ch = $chkboxes.eq(idx);
                    ch.prop('checked', !ch.prop('checked'));
                }
                else {
                    let t = templist.splice(templist.indexOf(idx));

                    for(i=0;i<t.length;i++) {
                        var ch = $chkboxes.eq(t[i]);
                        ch.prop('checked', !ch.prop('checked'));
                    }
                }
            }
        });

    }

    function reCalculate(idx) {

        var start = idx == 0 ? Date.parse('{{$model->start_at}}') : Date.parse('2020-1-1 ' + dataList[idx-1].end_at);
        
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