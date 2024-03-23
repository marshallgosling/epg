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
                    
                    <span class="text-danger"><b>该频道节目编单功能已锁定。如需更改，需要取消 <i class="fa fa-lock text-danger"></i> 状态。</b></span>
                    
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