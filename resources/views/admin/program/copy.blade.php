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
                    <span class="text-danger"><b>该栏目节目编单为副本，原始编单链接<a href="./{{$replicate}}">跳转至原始编单</a>, 如需更改该副本，点击</b></span>
                    <a id="btnOpen" class="btn btn-danger btn-sm">开启编辑</a>
                    
                    <span id="total" class="pull-right"></span>
                </div>
                <div class="dd" id="tree-programs">
                    
                </div>
            </div>
            <!-- /.box-body -->
        </div>
    </div>
</div>

<div id="template" style="display: none">{!!$template!!}</div>
<script type="text/javascript">

    var dataList = JSON.parse('{!!$json!!}');
    
    $(function () {   
        reloadTree();
        $("#btnOpen").on('click', function(e) {
            swal({
                title: "确认要开启编辑模式吗?",
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
                            url: '/admin/channel/open/data/{!! $model->id !!}',
                            data: {
                                data: JSON.stringify(dataList),
                                _token:LA.token,
                            },
                            success: function (data) {
                                $.pjax.reload('#pjax-container');
                                toastr.success('开启编辑功能成功 !');
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
 
    function reloadTree()
    {
        var html = '<ol class="dd-list">';
        var total = 0;
        for(i=0;i<dataList.length;i++)
        {
            var style = '';
            
            html += createItem(i, dataList[i], style);
            total += parseDuration(dataList[i].duration);
        }
        html += '</'+'ol>';

        $('#tree-programs').html(html);
        var d = Date.parse('2000/1/1 00:00:00');
        $('#total').html('<small>总时长 '+ formatTime(d+total*1000) +', 共 '+dataList.length+' 条记录</'+'small>');

        //Set the lastChecked box to null
        var lastChecked = null;
        var $chkboxes = $('.grid-row-checkbox');
        //When any check box is clicked
        $chkboxes.click(function(e) {

            //Set the last checkbox checked if it didnt exist before
            if (!lastChecked) {
                lastChecked = this;
            }

            //If the shift button is held while clicking a box
            if (e.shiftKey) {
                var start = $chkboxes.index(this);
                var end = $chkboxes.index(lastChecked);

                var selectedChecks = $chkboxes.slice(Math.min(start,end), Math.max(start,end)+ 1)
                selectedChecks.prop('checked', lastChecked.checked);
            }

            //Set the last checked button to 
            lastChecked = this;
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