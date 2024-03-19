<style type="text/css">

/* Common styles for all types */
.epg-callout {
  padding: 20px;
  margin-bottom: 20px;
  border: 1px solid #eee;
  border-left-width: 5px;
  border-radius: 3px;
}
.epg-callout h4 {
  margin-top: 0;
  margin-bottom: 5px;
  border-bottom: 1px solid #eee;
  padding-bottom: 10px;
}
.epg-callout p:last-child {
  margin-bottom: 0;
}
.epg-callout code {
  border-radius: 3px;
}
/* Variations */
.epg-callout-danger {
  border-left-color: #ce4844;
}
.epg-callout-danger h4 {
  color: #ce4844;
}
.epg-callout-warning {
  border-left-color: #aa6708;
}
.epg-callout-warning h4 {
  color: #aa6708;
}
.epg-callout-info {
  border-left-color: #337ab7;
}
.epg-callout-info h4 {
  color: #337ab7;
}
.epg-callout-success {
  border-left-color: #3c763d;
}
.epg-callout-success h4 {
  color: #3c763d;
}
.epg-callout-primary {
    border-left-color: #563d7c;
}
.epg-callout-primary h4 {
  color: #563d7c;
}
.epg-callout table {
  margin-bottom: 5px;
}

/* By default it's not affixed in mobile views, so undo that */

@media (min-width: 768px) {
  .epg-sidebar {
    padding-left: 20px;
  }
}

/* First level of nav */
.epg-sidenav {
  margin-top: 20px;
  margin-bottom: 20px;
}

/* All levels of nav */
.epg-sidebar .nav > li > a {
  display: block;
  padding: 4px 20px;
  font-size: 13px;
  font-weight: 500;
  color: #767676;
}
.epg-sidebar .nav > li > a:hover,
.epg-sidebar .nav > li > a:focus {
  padding-left: 19px;
  color: #563d7c;
  text-decoration: none;
  background-color: transparent;
  border-left: 1px solid #563d7c;
}
.epg-sidebar .nav > .active > a,
.epg-sidebar .nav > .active:hover > a,
.epg-sidebar .nav > .active:focus > a {
  padding-left: 18px;
  font-weight: bold;
  color: #563d7c;
  background-color: transparent;
  border-left: 2px solid #563d7c;
}

.epg-sidebar-info .nav > li > a:hover,
.epg-sidebar-info .nav > li > a:focus {
  color: #337ab7;
  border-left-color: #337ab7;
}
.epg-sidebar-info .nav > .active > a,
.epg-sidebar-info .nav > .active:hover > a,
.epg-sidebar-info .nav > .active:focus > a {
  color: #337ab7;
  border-left-color: #337ab7;
}

.epg-sidebar-danger .nav > li > a:hover,
.epg-sidebar-danger .nav > li > a:focus {
  color: #ce4844;
  border-left-color: #ce4844;
}
.epg-sidebar-danger .nav > .active > a,
.epg-sidebar-danger .nav > .active:hover > a,
.epg-sidebar-danger .nav > .active:focus > a {
  color: #ce4844;
  border-left-color: #ce4844;
}

/* Show and affix the side nav when space allows it */
@media (min-width: 992px) {
  .epg-sidebar .nav > .active > ul {
    display: block;
  }
  /* Widen the fixed sidebar */
  .epg-sidebar.affix,
  .epg-sidebar.affix-bottom {
    width: 213px;
  }
  .epg-sidebar.affix {
    position: fixed; /* Undo the static from mobile first approach */
    top: 20px;
  }
  .epg-sidebar.affix-bottom .epg-sidenav,
  .epg-sidebar.affix .epg-sidenav {
    margin-top: 0;
    margin-bottom: 0;
  }
}
@media (min-width: 1200px) {
  /* Widen the fixed sidebar again */
  .epg-sidebar.affix-bottom,
  .epg-sidebar.affix {
    width: 463px;
  }
}
ol.breadcrumb {
  margin-bottom: 0px;
}

</style>

<div class="row">
    <div class="col-md-12"> 
        <div class="box">
          <div class="box-header">
                <ol class="breadcrumb">
                  <li><b>{{@__('Channel')}} </b></li>
                  <li><span class="label-{{ \App\Models\Channel::DOTS[$group] }}" style="width: 8px;height: 8px;padding: 0;border-radius: 50%;display: inline-block;"></span>
                  {{ \App\Models\Channel::GROUPS[$group] }}</li>
                  <li><b>{{ @__('Start date')}}</b></li>
                  <li class="active"> {{$begin}} </li>
                  <li><b>{{ @__('Simulate days')}}</b></li>
                  <li>{{ $days }} </li>
                  <li>&nbsp; </li>
                  @if($error)
                  <li><b>运行结果</b></li>
                  <li><b class="text-danger">出错</b></li>
                  @else
                  <li><b>运行结果</b></li>
                  <li><b class="text-success">通过</b></li>
                  @endif
                  @foreach($r as $pr=>$c)
                  <li><b>$pr</b></li>
                  <li><b>$c</b></li>
                  @endforeach
                </ol>
                
            </div>
            <div class="box-body">
              <div class="col-md-8"> 
              @foreach ($data as $model)
                <div class="row">
                  <div class="col-md-12"> 
                    
                  @if(count($model['data']) > 0)
                
                  @foreach($model['data'] as $program) 
                  <div id="channel{{$model['id']}}" class="epg-callout epg-callout-{{$program['error']?'danger':'info'}}">
                      <h4>{{$model['air_date']}} ({{@\App\Models\TemplateRecords::DAYS[date('N', strtotime($model['air_date']))]}}) {{$program['start_at']}} - {{$program['end_at']}} &nbsp;<small>{{$program['duration']}} </small>&nbsp; &nbsp; | {{$program['name']}} 
                          || &nbsp; 
                          </h4>
                      
                      <ul class="list-group">
                        <li class="list-group-item disabled">
                        模版状态: 
                          @if($program['template']) 
                          (ID: {{$program['template']['id']}}) &nbsp; &nbsp;{{$program['template']['data']['episodes']}} - {{$program['template']['data']['name']}} <span class="text-danger">{{$program['template']['data']['result']}}</span> <span class="pull-right text-warning">{{$program['template']['data']['unique_no']}} </span>
                          @else
                            无
                          @endif
                        </li>
                        @if($program['error'])
                        <li class="list-group-item text-danger">{{$program['error']}}</li>
                        @else
                        @foreach ($program['program']['data'] as $item)
                        <li class="list-group-item">{!!$item!!}</li>
                        @endforeach
                        @endif
                  </div>
                  @endforeach
                  @endif
                  </div>
                </div>
                @endforeach
              </div>
              <div class="col-md-4"> 
                <nav class="epg-sidebar hidden-print hidden-sm hidden-xs" id="epgAffix">
                  <ul class="nav epg-sidenav"> 
                    @foreach ($data as $model)
                    <li> <a href="#channel{{$model['id']}}"><b>{{ @__('Air date')}}</b> | {{$model['air_date']}} ({{@\App\Models\TemplateRecords::DAYS[date('N', strtotime($model['air_date']))]}}) {{$model['error']?"错误":''}}</a> </li>
                    @endforeach
                  </ul>
              </div>
              
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
  $(function () {
    $('#epgAffix').affix({offset: {top: 130}});
    $('body').scrollspy({ target: '#epgAffix' })
  });
</script>