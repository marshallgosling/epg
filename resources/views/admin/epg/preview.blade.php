<style type="text/css">

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
                <b>{{@__('Preview EPG Content')}} - </b>
                </div>
                <div class="btn-group">
                    <div class="dropdown">
                        <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            {{$model->air_date}} <small>{{ Channel::STATUS[$model->status] }} </small> <span class="label label-info">{{ Channel::AUDIT[$model->audit_status] }}</span>
                            <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu">
                            @foreach($channels as $item) 
                            <li><a href="./{{$item->air_date}}">{{$item->air_date}} <small>{{ Channel::STATUS[$item->status] }} </small> <span class="label label-info">{{ Channel::AUDIT[$item->audit_status] }}</span></a></li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
            <div class="box-body">
              @if(count($data) > 0)
              <div class="col-md-6"> 
                @foreach($order[0] as $pro_id) 
                <div class="bs-callout bs-callout-info">
                    <h4>{{$data[$pro_id]['start_at']}} - {{$data[$pro_id]['end_at']}} &nbsp;<small>{{$data[$pro_id]['duration']}} </small>&nbsp; &nbsp; | {{$data[$pro_id]['name']}}  </h4>
                    <ul class="list-group">
                      @foreach ($data[$pro_id]['items'] as $item)
                      <li class="list-group-item">{{$item}}</li>
                      @endforeach
                    </ul>   
                </div>
                @endforeach
              </div>
              <div class="col-md-6"> 
                @foreach($order[1] as $pro_id) 
                <div class="bs-callout bs-callout-info">
                    <h4>{{$data[$pro_id]['start_at']}} - {{$data[$pro_id]['end_at']}} &nbsp;<small>{{$data[$pro_id]['duration']}} </small>&nbsp; &nbsp; | {{$data[$pro_id]['name']}}  </h4>
                    <ul class="list-group">
                      @foreach ($data[$pro_id]['items'] as $item)
                      <li class="list-group-item">{{$item}}</li>
                      @endforeach
                    </ul>   
                </div>
                @endforeach
              </div>
              @else
              <h4>没有可以预览的串联单</h4>
              @endif
            </div>
        </div>
    </div>
</div>
