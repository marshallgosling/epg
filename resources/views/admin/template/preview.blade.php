<style type="text/css">

/*
 * Callouts
 *
 * Not quite alerts, but custom and helpful notes for folks reading the docs.
 * Requires a base and modifier class.
 */

/* Common styles for all types */
.bs-callout {
  padding: 20px;
  margin-bottom: 20px;
  border: 1px solid #eee;
  border-left-width: 5px;
  border-radius: 3px;
}
.bs-callout h4 {
  margin-top: 0;
  margin-bottom: 5px;
  border-bottom: 1px solid #eee;
  padding-bottom: 10px;
}
.bs-callout p:last-child {
  margin-bottom: 0;
}
.bs-callout code {
  border-radius: 3px;
}
/* Variations */
.bs-callout-danger {
  border-left-color: #ce4844;
}
.bs-callout-danger h4 {
  color: #ce4844;
}
.bs-callout-warning {
  border-left-color: #aa6708;
}
.bs-callout-warning h4 {
  color: #aa6708;
}
.bs-callout-info {
  border-left-color: #337ab7;
}
.bs-callout-info h4 {
  color: #337ab7;
}
.bs-callout-success {
  border-left-color: #3c763d;
}
.bs-callout-success h4 {
  color: #3c763d;
}
.bs-callout-primary {
    border-left-color: #563d7c;
}
.bs-callout-primary h4 {
  color: #563d7c;
}
.bs-callout table {
  margin-bottom: 5px;
}
</style>
<div class="row">
    <div class="col-md-12"> 
        <div class="box">
            <div class="box-header">
                <div class="btn-group">
                @if($error)
                  错误信息：<b class="text-danger">{{$error}}</b>
                @else
                  <b>{{@__('Preview Template Content')}}</b>
                @endif
                
                </div>
                <div class="btn-group">
                <a class="btn btn-success btn-sm" title="模拟编单测试" href="../simulator"><i class="fa fa-android"></i><span class="hidden-xs"> 模拟编单测试</span></a>
                
                </div>
                <div class="btn-group">
                 <a class="btn btn-danger btn-sm" title="一键清空状态" href="javascript:clearState();"><i class="fa fa-chain-broken"></i><span class="hidden-xs"> 一键清空状态</span></a>
                
                </div>
                <div class="btn-group pull-right">
                    @if($back)<a class="btn btn-primary btn-sm" title="返回编辑模式" href="../{{$group}}"><i class="fa fa-arrow-left"></i><span class="hidden-xs"> 返回编辑模式</span></a>@endif
                </div>
            </div>
            <div class="box-body table-responsive">
                @foreach($data as $temp) 
                <div class="bs-callout bs-callout-{{$temp['color']}}">
                    <h4>{{$temp['start_at']}} - {{$temp['end_at']}} &nbsp;<small>{{$temp['duration']}} </small>&nbsp; &nbsp; | {{$temp['name']}} (ID:{{$temp['id']}}) </h4>
                    {!!$temp['table']!!}    
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
  function clearState() {
      swal({
          title: "确认要清空状态吗?",
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
                      url: '/admin/template/{{$group}}/reset/state',
                      data: {
                          data: 'reset',
                          action: "state",
                          _token: LA.token,
                      },
                      success: function (data) {
                          $.pjax.reload('#pjax-container');
                          toastr.success('重置成功 !');
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
</script>

