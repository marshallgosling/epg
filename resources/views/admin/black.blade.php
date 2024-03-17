{{ $content }}
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
                        <span class="input-group-addon">关键字</span>
                        <input type="text" class="form-control" style="width:240px" id="keyword" placeholder="请输入关键字, 输入%作为通配符">
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
<script>
    var selectedIndex = -1;
    var selectedItems = [];
    var loadingMore = false;
    var curPage = 1;
    var uniqueAjax = null;
    var cachedPrograms = null;
    var keyword = '';
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
            dataList[selectedIndex] = selectedItem;
            //modifiedItem.push(selectedItem.unique_no.toString());

            //$('#btnSort').html("保存");
            //$('#treeinfo').html('<strong class="text-danger">请别忘记保存修改！</'+'strong>');
            selectedItem = false;
            selectedItems = [];
            $('.search-item').removeClass('info');
            $('#selectedSpan').html('');
        });
    });

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

</script>