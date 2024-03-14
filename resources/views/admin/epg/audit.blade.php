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
                  <li><span class="label-{{ \App\Models\Channel::DOTS[$model->name] }}" style="width: 8px;height: 8px;padding: 0;border-radius: 50%;display: inline-block;"></span>
                  {{ \App\Models\Channel::GROUPS[$model->name] }}</li>
                  <li><b>{{ @__('Air date')}}</b></li>
                  <li class="active"> {{$model->air_date}} </li>
                  <li><b>{{ @__('Status')}}</b></li>
                  <li><span class="label label-success">{{ \App\Models\Channel::STATUS[$model->status] }} </span></li>
                  <li><b>{{ @__('Lock status')}}</b></li>
                  <li><span class="label label-info">{{ \App\Models\Channel::LOCKS[$model->lock_status] }}</span></li>
                  
                </ol>
                
            </div>
            <div class="box-body">
              @if(count($data) > 0)
              <div class="col-md-8"> 
                @foreach($data as $program) 
                <div id="content{{$program->id}}" class="epg-callout epg-callout-{{$color}}">
                    <h4>{{$program->start_at}} - {{@substr($program->end_at, 11)}} &nbsp;<small>{{@\App\Tools\ChannelGenerator::formatDuration($program->duration)}} </small>&nbsp; &nbsp; | {{$program->name}}  </h4>
                    <ul class="list-group">
                      @if(strpos($program->data, 'replicate'))
                        <li class="list-group-item disabled"> 副本节目单 </li>
                      @else
                      @foreach (json_decode($program->data) as $t)
                      <li class="list-group-item">{{$t->start_at}} - {{$t->end_at}} <small class="pull-right text-warning">{{$t->unique_no}} {!!$materials[$t->unique_no] ? '<i class="fa fa-check text-green"></i>':'<i class="fa fa-close text-red"></i>'!!}</small> &nbsp;{{$t->name}} &nbsp; <small class="text-info">{{@substr($t->duration, 0, 8)}}</small></li>
                      @endforeach
                      @endif
                    </ul>   
                </div>
                @endforeach
              </div>
              <div class="col-md-4"> 
                <nav class="epg-sidebar epg-sidebar-{{$color}} hidden-print hidden-sm hidden-xs" id="epgAffix">
                  <ul class="nav epg-sidenav"> 
                    @foreach($data as $program) 
                    <li> <a href="#content{{$program->id}}">{{$program->start_at}} - {{@substr($program->end_at, 11)}} &nbsp; | {{$program->name}} </a> </li>
                    @endforeach
                  </ul>
              </div>
              @else
              <h4>没有可以预览的串联单</h4>
              @endif
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