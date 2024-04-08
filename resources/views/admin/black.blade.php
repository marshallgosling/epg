<div class="row">
    <div class="col-md-12">
        <div class="box">
            <div class="box-header">
                <div class="btn-group"><b>{{__('具体节目编排')}}</b></div>
                <span id="total" class="pull-right"></span>
            </div>
            <!-- /.box-header -->
            <div class="box-body table-responsive no-padding">
                
                <div class="dd" id="tree-programs">
                    
                </div>
            </div>
            <!-- /.box-body -->
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="box">
            <div class="box-header">
                <div class="btn-group"><b>{{__('替换规则')}}</b></div>
                <a id="btnSave" class="btn btn-info btn-sm"><i class="fa fa-info-circle"></i> 保存规则</a>
                <span id="treeinfo"></span>
            </div>
            <!-- /.box-header -->
            <div id="replace-programs" class="box-body table-responsive no-padding">
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="searchModal" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">搜索</h4>
      </div>
      <div class="modal-body">
        
        <div class="row">
        <div class="col-md-12">
            <form id="modal-form" class="form-inline" onkeydown="if(event.keyCode==13){return false;}">
                <div class="form-group">
                    <div class="input-group">
                        <span class="input-group-addon">栏目</span>
                        <select class="form-control category" id="category" style="width:140px" >
                        <option value=""></option>
                        @foreach($categories as $key=>$value)
                        <option value="{{$key}}">{{$value}}</option>
                        @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <div class="input-group">
                        <span class="input-group-addon">关键字</span>
                        <input type="text" class="form-control" style="width:200px" id="keyword" placeholder="输入%作为通配符">
                    </div>
                </div>
                <div class="form-group">
                    <div class="input-group">
                        <span class="input-group-addon">时长</span>
                        <input type="text" class="form-control" style="width:100px" id="duration" placeholder="秒为单位">
                    </div>
                </div>
                <div class="form-group">
                    <div class="input-group">
                        <span class="input-group-addon">
                            节目库
                        </span>
                        <select class="form-control library" id="library" data-value="program">
                            <option value="records">星空中国</option><option value="record2">星空国际</option><option value="program" selected>V China</option>
                        </select>
                        <span class="input-group-btn">
                            <button id="btnSearch" class="btn btn-info" type="button">搜索</button>
                        </span>
                    </div>
                </div>
            </form>
        </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="table-responsive" style="margin-top:10px;height:500px; overflow-y:scroll">
                    <table class="table table-search table-hover table-striped">
                                
                    </table>
                    <div id="noitem" style="margin:30px;display:block"><strong>请输入关键字查询</strong></div>
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
        <small id="selectedSpan" class="text-danger"></small>
        <button id="confirmBtn" type="button" class="btn btn-info" disabled="true">确认</button>      
        <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
        
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<div id="template" style="display: none">{!!$template!!}</div>
<script type="text/javascript">
    var selectedIndex = -1;
    var selectedItems = [];
    var loadingMore = false;
    var curPage = 1;
    var uniqueAjax = null;
    var cachedPrograms = null;
    var keyword = '';
    var dataList = JSON.parse('{!!$json!!}');
    var replaceList = JSON.parse('{!!$replace!!}');
    function reloadTree()
    {
        var html = '<ol class="dd-list">';
        var total = 0;
        for(i=0;i<dataList.length;i++)
        {
            var style = '';
            //if(in_array(dataList[i].unique_no, replaceItem)) style = 'bg-danger';
            html += createItem(i, dataList[i], style);
        }
        html += '</'+'ol>';

        $('#tree-programs').html(html);
        $('#total').html('<small>共 '+dataList.length+' 条记录</'+'small>');

        $chkboxes = $('.grid-row-checkbox');
        //setupMouseEvents();
    }

    function reloadReplace()
    {
        var html = '<table class="table table-hover grid-table">';
        html += '<thead><tr><th>节目名</th><th>播出编号</th><th>时长</th><th>替换节目名</th><th>替换编号</th><th>替换时长</th><th>建议</th></tr></thead>';
        var total = 0;
        for(i=0;i<replaceList.length;i++)
        {
            html += createReplace(i, replaceList[i]);
        }
        html += '</'+'table>';

        $('#replace-programs').html(html);
       
    }

    function showSearchModal(idx) {
        
        selectedIndex = idx;

        $('#searchModal').modal('show');
        $('#confirmBtn').removeAttr('disabled');
    }
    $(function () {
        $('#moreBtn').on('click', function(e) {
            if(loadingMore) return;
            loadingMore = true;
            curPage ++;
            $.ajax({
                url: "/admin/api/tree/programs",
                dataType: 'json',
                data: {
                    q: keyword,
                    c: $('#category').val(),
                    t: $('#library').val(),
                    s: $('#duration').val(),
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
                        if(item.black && item.black>0) tr = ' danger';
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

        $("#keyword").on('keyup', function(e) {
            if(e.keyCode==13) {
                searchKeywords($('#keyword').val());
            }
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

            if(selectedItem.black) {
                toastr.error("该艺人以上黑名单，不能使用");
                return;
            }
            item = dataList[selectedIndex];
            setReplaceProgram(item, selectedItem);

            selectedItem = false;
            selectedItems = [];
            $('.search-item').removeClass('info');
            $('#selectedSpan').html('');
            reloadReplace();
        });
        $('#btnSave').on('click', function(e) {
            $.ajax({
                method: 'post',
                url: '/admin/media/blacklist/result/{!! $model->id !!}/save',
                data: {
                    data: JSON.stringify(replaceList),
                    _token:LA.token,
                },
                success: function (data) {
                    //$.pjax.reload('#pjax-container');
                    toastr.success('保存成功 !');
                }
            });
        });

        reloadTree();
        reloadReplace();
    });

    function setReplaceProgram(item, replace) {
        for(var i=0;i<replaceList.length;i++)
        {
            if(replaceList[i].unique_no == item.unique_no) {
                replaceList[i].replace = replace;
                return true;
            }
        }
        item.replace = replace;
        replaceList.push(item);
        return;

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
        
        
            selectedItem = repo;
            $('.search-item').removeClass('info');
            $('.search-item').eq(idx).addClass('info');
        
        
    }
    function searchKeywords(keyword)
    {
        if(uniqueAjax) uniqueAjax.abort();
        keyword = keyword;
        $('.table-search').html('');
        $('#noitem').html('搜索数据中...');
        cachedPrograms = [];
        curPage = 1
        uniqueAjax = $.ajax({
            url: "/admin/api/tree/programs",
            dataType: 'json',
            data: {
                    q: keyword,
                    c: $('#category').val(),
                    t: $('#library').val(),
                    s: $('#duration').val(),
                    p: curPage
            },
                success: function (data) {
                    uniqueAjax = null;
                    var items = data.result;
                    cachedPrograms = cachedPrograms.concat(items);
                    selectedItem = null;
                    selectedItems = [];

                    if(data.total == 0) {
                        $('#noitem').show();
                        $('#noitem').html('<strong>没有找到任何记录</strong>');
                        $('#totalSpan').html('');
                        return;
                    }
                    $('#noitem').hide();
                    $('#totalSpan').html("共找到 " + data.total + " 条节目（每次载入 20 条）");
                    var head = ['序号','播出编号','名称','艺人','时长','标签'];
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

    function formatTime($time) {
        var d = new Date($time);
        var a = [];
        a[0] = d.getHours() > 9 ? d.getHours().toString() : '0'+d.getHours().toString();
        a[1] = d.getMinutes() > 9 ? d.getMinutes().toString() : '0'+d.getMinutes().toString();
        a[2] = d.getSeconds() > 9 ? d.getSeconds().toString() : '0'+d.getSeconds().toString();
        return a.join(':');
    }

    function formatDuration(seconds)
    {
        var h = Math.floor(seconds / 3600);
        var m = Math.floor((seconds % 3600) / 60);
        var s = seconds % 60;
        var a = [];
        a[0] = h > 9 ? h : '0'+h;
        a[1] = m > 9 ? m : '0'+m;
        a[2] = s > 9 ? s : '0'+s;
        return a.join(':');
    }

    function createItem(idx, item, style) {
        var html = $('#template').html();
        var textstyle = "";
        if(style == '') style = parseBg(item.category, item.unique_no);
        if(style == 'bg-danger') textstyle = 'text-danger';
        return html.replace(/idx/g, idx).replace(/textstyle/g, textstyle).replace('start_at', item.start_at).replace('end_at', item.end_at)
                    .replace('name', item.name).replace('duration', item.duration).replace('artist', item.artist).replace('program', item.program)
                    .replace('category', item.category).replace('unique_no', item.unique_no).replace('bgstyle', style).replace('air_date', item.air_date);
    }

    function createReplace(idx, item) {
        var td = [];
        td.push(item.name);td.push(item.unique_no);td.push(item.duration);
        td.push(item.replace.name);td.push(item.replace.unique_no);td.push(item.replace.duration);

        var d1 = parseDuration(item.duration);
        var d2 = parseDuration(item.replace.duration);
        if(Math.abs(d1-d2) > 180) td.push('时长不匹配');
        else td.push('可替换');

        return "<tr><td>"+td.join('</'+'td><td>')+"</"+"td></"+"tr>";
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