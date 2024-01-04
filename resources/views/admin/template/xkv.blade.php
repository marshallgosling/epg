<div class="row">
    <div class="col-md-12">
        <div class="box">
            <div class="box-header">
                <div class="btn-group"><b>{{__('具体节目编排')}}</b>&nbsp; &nbsp;</div>
                
                <div class="btn-group">
                    <div class="dropdown">
                        <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <b>{{@substr($model->start_at, 0)}}</b> (<small>{{@substr($model->duration, 0)}}</small>) {{$model->name}} <small class="text-{{$model->status==\App\Models\Template::STATUS_SYNCING?'danger':'info'}}">{{@\App\Models\Template::STATUSES[$model->status]}}</small>
                            <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu">
                            @foreach($list as $item) 
                            <li class="{{$item->id == $model->id ? 'bg-info':''}}"><a href="./{{$item->id}}"><b>{{@substr($item->start_at, 0)}}</b> (<small>{{@substr($item->duration, 0)}}</small>) {{$item->name}} <small class="text-{{$item->status==\App\Models\Template::STATUS_SYNCING?'danger':'info'}}">{{@\App\Models\Template::STATUSES[$item->status]}}</small></a></li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div class="btn-group">&nbsp; &nbsp;</div>
               
                <div class="btn-group pull-right">
                    <a class="btn btn-warning btn-sm" title="{{__('返回普通模式')}}" href="../programs?template_id={{$model->id}}"><i class="fa fa-arrow-left"></i><span class="hidden-xs"> {{__('返回普通模式')}}</span></a>
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
<div class="modal fade" id="editorModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">修改</h4>
      </div>
      <div class="modal-body">
        <div class="box-body">
            <div class="form-group">
                <label>类型</label>
                <div>
                    <span class="icheck">
                            <label class="radio-inline">
                                <input type="radio" name="type" id="inputType0" value="0" class="minimal type action"> 节目  
                            </label>
                    </span>
                    <span class="icheck">
                            <label class="radio-inline">
                                <input type="radio" name="type" id="inputType1" value="1" class="minimal type action"> 广告  
                            </label>
                    </span>
                    <span class="icheck">
                            <label class="radio-inline">
                                <input type="radio" name="type" id="inputType2" value="2" class="minimal type action"> 垫片  
                            </label>
                    </span>
                </div>
            </div>
            <div class="form-group">
                        <label>分类</label>
                        
                            <input type="text" class="form-control" id="inputCategory" placeholder="填写分类">
                       
                    </div>
            <div class="form-group">
                    <label>别名</label>
                   
                        <input type="text" class="form-control" id="inputName" placeholder="填写别名">
                    
                </div>
            <div class="form-group">
                <label>数据</label>
                
                    <input type="text" class="form-control" id="inputData" placeholder="填写播出编号">
              
            </div>
        </div>
    </div>
      <div class="modal-footer">
        
        <button id="editBtn" type="button" class="btn btn-info">确认</button>      
        <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
        
      </div>
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
            <div class="col-lg-8">
                <div class="input-group">    
                    <span class="input-group-addon">
                        <label><input type="checkbox" id="onlycategory" autocomplete="off"> 只搜索栏目字段</label>
                    </span>
                    <input type="text" class="form-control" name="keyword" id="keyword" placeholder="请输入关键字">
                    <span class="input-group-btn">
                        <button id="btnSearch" class="btn btn-info" type="button">搜索</button>
                    </span>
                </div>
            </div>
            
        </div>
        <div class="row">
            <div class="col-lg-12">
                <div class="table-responsive" style="margin-top:10px;height:500px; overflow-y:scroll">
                    <table class="table table-search table-hover table-striped">
                                
                    </table>
                    <div id="noitem" style="margin:30px;display:block"><strong>没有找到任何记录</strong></div>
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
        <span>模版类型</span>
        <label><input type="radio" name="datatype" value="0" checked> 节目</label>
        <label><input type="radio" name="datatype" value="2"> 固定</label>
        <label><input type="radio" name="datatype" value="1"> 广告</label>
        &nbsp;
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
    var editorIndex = -1;
    var deletedItem = [];
    var sortChanged = false;
    var dataList = JSON.parse('{!!$json!!}');
    var category = JSON.parse('{!!@json_encode($category)!!}');
    var sortEnabled = false;
    var cachedPrograms = null;
    var uniqueAjax = null;
    var backupList = [];
    var curPage = 1;
    var keyword = '';
    var loadingMore = false;
    $(function () {
        $('#widget-form-655477f1c8f59').submit(function (e) {
            e.preventDefault();
            $(this).find('div.cascade-group.hide :input').attr('disabled', true);
        });
        $('body').on('mouseup', function(e) {
            //console.log('stop ');
            startmove = false;
            templist = [];
        });
        $('.minimal').iCheck({radioClass:'iradio_minimal-blue'});
        $('#btnDelete').on('click', function(e) {

            if(sortEnabled) {
                toastr.error("{{__('Please save new ordered list.')}}");
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
                deletedItem.push(dataList.splice(selected[i], 1)[0]);
            }
            $('#btnSort').html("{{ __('Save') }}");
            $('#treeinfo').html('<strong class="text-danger">{{ __("PleaseDonotForgotSave") }}</'+'strong>');

            reloadTree();
        });

        $('#btnRollback').on('click', function(e) {
            if(backupList.length == 0) return;
            if(sortEnabled) {
                toastr.error("{{__('Please save new ordered list.')}}");
                return;
            }

            deletedItem.pop();
            dataList = backupList.pop();
            
            reloadTree();
            toastr.success("{{__('Rollback success')}}");

            if(backupList.length==0)
                $('#btnRollback').attr('disabled', true);
        });

        $('#editBtn').on('click', function(e) {
            $('#editorModal').modal('hide');
            backupData();
            var tmp = dataList[editorIndex];
            tmp.type = $('input[name=type]:checked').val();
            tmp.name = $('#inputName').val();
            tmp.category = $('#inputCategory').val();
            tmp.data = $('#inputData').val();
            dataList[editorIndex] = tmp;

            $('#btnSort').html("{{ __('Save') }}");
            $('#treeinfo').html('<strong class="text-danger">{{ __("PleaseDonotForgotSave") }}</'+'strong>');

            reloadTree();
        });

        $('#btnSort').on('click', function(e) {
            if($('#btnSort').html() != "{{ __('Save') }}") {
                $('#tree-programs').nestable({maxDepth: 1});
                $('#tree-programs').on('change', function() {
                    sortChanged = true;
                    $('#treeinfo').html('<strong class="text-danger">{{ __("PleaseDonotForgotSave") }}</'+'strong>');
                });
                sortEnabled = true;
                $('#btnSort').html("{{ __('Save') }}");
                $('#treeinfo').html('<small>{{ __("Drag and Sort") }}</'+'small>');
            } 
            else {
                var action = "";
                var newList = [];
                if(sortChanged) {
                    var list = $('#tree-programs').nestable('serialize');
                    
                    for(var i=0;i<list.length;i++)
                    {
                        newList[i] = dataList[list[i].id];
                    }
                    action = "sort";
                }
                else {
                    action = "modify";
                    newList = dataList;     
                }
                
                $.ajax({
                    method: 'post',
                    url: '/admin/template/xkv/data/{!! $model->id !!}/save',
                    data: {
                        data: JSON.stringify(newList),
                        action: action,
                        deleted: JSON.stringify(deletedItem),
                        _token: LA.token
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
                        html += '<tr class="search-item'+tr+'" onclick="selectProgram('+idx+')"><td>'+(idx+1)+'</'+'td><td>'+item.unique_no+'</'+'td><td>'+item.name+'</'+'td><td>'+item.artist+'</'+'td><td>'+item.duration+'</'+'td><td>'+item.category+'</'+'td></tr>';
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
            searchKeywords(e.currentTarget.value);
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
            deletedItem.push({name: "empty"});

            var item = {
                id: selectedIndex == 'new' ? 0 : dataList[selectedIndex].id,
                name: selectedItem.name,
                category: selectedItem.category,
                type: $('input[name=datatype]:checked').val(),
                data: selectedItem.unique_no,
                sort: selectedIndex,
                ischange: 1
            };
            if(item.type == 0) {
                item.data = '';
                item.name = '';
            }

            if(selectedIndex == 'new') selectedIndex = dataList.length;
            dataList[selectedIndex] = item;

            reloadTree();
            
            $('#btnSort').html("{{ __('Save') }}");
            $('#treeinfo').html('<strong class="text-danger">{{ __("PleaseDonotForgotSave") }}</'+'strong>');
            selectedItem = false;
            $('.search-item').removeClass('info');
            
        });
        
        reloadTree();
    });

    function searchKeywords(keyword)
    {
        if(uniqueAjax) uniqueAjax.abort();
            //keyword = e.currentTarget.value;
            cachedPrograms = [];
            curPage = 1
            uniqueAjax = $.ajax({
                url: "/admin/api/tree/programs",
                dataType: 'json',
                data: {
                    q: keyword,
                    p: curPage,
                    o: $('#onlycategory').prop('checked') ? 1 : 0
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
                    $('#totalSpan').html("共找到 " + data.total + " 条节目（每次载入 20 条）");
                    var head = ['序号','播出编号','名称','艺人','时长','栏目'];
                    var html = '<tr><th>'+head.join('</t'+'h><th>')+'</'+'th></'+'tr>';
                    if(data.total > cachedPrograms.length) $('#moreBtn').show();
                    else $('#moreBtn').hide();
                    for(i=0;i<items.length;i++)
                    {
                        item = items[i];
                        tr = '';
                        if(item.black) tr = ' danger';
                        if(item.artist==null) item.artist='';
                        html += '<tr class="search-item'+tr+'" onclick="selectProgram('+i+')"><td>'+(i+1)+'</'+'td><td>'+item.unique_no+'</'+'td><td>'+item.name+'</'+'td><td>'+item.artist+'</'+'td><td>'+item.duration+'</'+''+'td><td>'+item.category+'</'+'td></'+'tr>';
                    }
                    
                    $('.table-search').html(html);
                },
                error: function() {
                    uniqueAjax = null;
                }
            });
    }

    function backupData() {
        backupList.push(JSON.parse(JSON.stringify(dataList)));
        $('#btnRollback').removeAttr('disabled');
    }

    function showEditorModal(idx) {
        if(sortEnabled) {
            toastr.error("{{__('Please save new ordered list.')}}");
            return;
        }
        editorIndex = idx;
        $('#editorModal').modal('show');
        var tmp = dataList[editorIndex];
        $('.minimal').iCheck('uncheck');
        $('#inputType'+tmp.type).iCheck('check');
        $('#inputName').val(tmp.name);
        $('#inputCategory').val(tmp.category);
        $('#inputData').val(tmp.data);
    }

    function showSearchModal(idx) {
        if(sortEnabled) {
            toastr.error("{{__('Please save new ordered list.')}}");
            return;
        }
        selectedIndex = idx;
        $('#searchModal').modal('show');
        $('#confirmBtn').removeAttr('disabled');
    }

    function selectProgram (idx) {
        var repo = cachedPrograms[idx];
        if(repo.black) {
            toastr.error("该节目已上黑名单");
        }
        if(repo.category) {
            repo.category = repo.category.toString().split(',')[0];
        }
        
        selectedItem = repo;

        $('.search-item').removeClass('info');
        $('.search-item').eq(idx).addClass('info');
    }

    function deleteProgram (idx) {
        if(sortEnabled) {
            toastr.error("{{__('Please save new ordered list.')}}");
            return;
        }
        backupData();
        deletedItem.push(dataList.splice(idx, 1)[0]);
        $('#btnSort').html("{{ __('Save') }}");
        $('#treeinfo').html('<strong class="text-danger">请别忘记保存修改！</'+'strong>');
        reloadTree();
    }

    function copyProgram (idx) {
        if(sortEnabled) {
            toastr.error("请先保存排序结果。");
            return;
        }
        backupData();
        var repo = dataList[idx];
        repo.id = 0;
        dataList.splice(idx+1, 0, repo);
        reloadTree();
    }

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
        setupMouseEvents();
    }

    var $chkboxes;
    var startmove = false;
    var templist = [];
    function setupMouseEvents()
    {
        templist = [];
        $('.dd-item').on('mousedown', function(e) {
            if(sortEnabled) return;
            e.preventDefault();
            let idx = parseInt($(this).data('id'));
            startmove = true;
            let ch = $chkboxes.eq(idx);
            ch.prop('checked', !ch.prop('checked'));
            
        });
        $('.dd-item').on('mouseenter', function(e) {
            if(sortEnabled) return;
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
                //console.log(templist);
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
        if(style == '') style = parseBg(item.category, item.data);
        labelstyle = category.labels[item.type];

        return html.replace(/idx/g, idx).replace('name', item.name).replace('categorytype', category.types[item.type])
                    .replace('labelstyle', labelstyle).replace('category', item.category).replace('unique_no', item.data).replace('bgstyle', style);
    }

    function parseBg($no, $code)
    {
        if($no == 'm1') return 'bg-warning';
        if($no == 'v1') return 'bg-default';
        if($code && $code.match(/VCNM(\w+)/)) return 'bg-info';
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

    //Reset the checkValArray to empty
    let checkValArray = []

    
</script>