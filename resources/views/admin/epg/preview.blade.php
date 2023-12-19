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

/* By default it's not affixed in mobile views, so undo that */

@media (min-width: 768px) {
  .bs-docs-sidebar {
    padding-left: 20px;
  }
}

/* First level of nav */
.bs-docs-sidenav {
  margin-top: 20px;
  margin-bottom: 20px;
}

/* All levels of nav */
.bs-docs-sidebar .nav > li > a {
  display: block;
  padding: 4px 20px;
  font-size: 13px;
  font-weight: 500;
  color: #767676;
}
.bs-docs-sidebar .nav > li > a:hover,
.bs-docs-sidebar .nav > li > a:focus {
  padding-left: 19px;
  color: #563d7c;
  text-decoration: none;
  background-color: transparent;
  border-left: 1px solid #563d7c;
}
.bs-docs-sidebar .nav > .active > a,
.bs-docs-sidebar .nav > .active:hover > a,
.bs-docs-sidebar .nav > .active:focus > a {
  padding-left: 18px;
  font-weight: bold;
  color: #563d7c;
  background-color: transparent;
  border-left: 2px solid #563d7c;
}

/* Nav: second level (shown on .active) */
.bs-docs-sidebar .nav .nav {
  display: none; /* Hide by default, but at >768px, show it */
  padding-bottom: 10px;
}
.bs-docs-sidebar .nav .nav > li > a {
  padding-top: 1px;
  padding-bottom: 1px;
  padding-left: 30px;
  font-size: 12px;
  font-weight: normal;
}
.bs-docs-sidebar .nav .nav > li > a:hover,
.bs-docs-sidebar .nav .nav > li > a:focus {
  padding-left: 29px;
}
.bs-docs-sidebar .nav .nav > .active > a,
.bs-docs-sidebar .nav .nav > .active:hover > a,
.bs-docs-sidebar .nav .nav > .active:focus > a {
  padding-left: 28px;
  font-weight: 500;
}

/* Back to top (hidden on mobile) */
.back-to-top,
.bs-docs-theme-toggle {
  display: none;
  padding: 4px 10px;
  margin-top: 10px;
  margin-left: 10px;
  font-size: 12px;
  font-weight: 500;
  color: #999;
}
.back-to-top:hover,
.bs-docs-theme-toggle:hover {
  color: #563d7c;
  text-decoration: none;
}
.bs-docs-theme-toggle {
  margin-top: 0;
}

@media (min-width: 768px) {
  .back-to-top,
  .bs-docs-theme-toggle {
    display: block;
  }
}

/* Show and affix the side nav when space allows it */
@media (min-width: 992px) {
  .bs-docs-sidebar .nav > .active > ul {
    display: block;
  }
  /* Widen the fixed sidebar */
  .bs-docs-sidebar.affix,
  .bs-docs-sidebar.affix-bottom {
    width: 213px;
  }
  .bs-docs-sidebar.affix {
    position: fixed; /* Undo the static from mobile first approach */
    top: 20px;
  }
  .bs-docs-sidebar.affix-bottom .bs-docs-sidenav,
  .bs-docs-sidebar.affix .bs-docs-sidenav {
    margin-top: 0;
    margin-bottom: 0;
  }
}
@media (min-width: 1200px) {
  /* Widen the fixed sidebar again */
  .bs-docs-sidebar.affix-bottom,
  .bs-docs-sidebar.affix {
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
                  <li class="active"><b>{{ @__('Air date')}}</b></li>
                  <li> {{$model->air_date}} </li>
                  <li><span class="label label-success">{{ \App\Models\Channel::STATUS[$model->status] }} </span> 
                  <li><span class="label label-info">{{ \App\Models\Channel::AUDIT[$model->audit_status] }}</span></li>
                  
                </ol>
                
            </div>
            <div class="box-body">
              @if(count($data) > 0)
              <div class="col-md-8"> 
                @foreach($order as $pro_id) 
                <div class="bs-callout bs-callout-info">
                    <h4 id="content{{$pro_id}}">{{$data[$pro_id]['start_at']}} - {{$data[$pro_id]['end_at']}} &nbsp;<small>{{$data[$pro_id]['duration']}} </small>&nbsp; &nbsp; | {{$data[$pro_id]['name']}}  </h4>
                    <ul class="list-group">
                      @foreach ($data[$pro_id]['items'] as $item)
                      <li class="list-group-item">{!!$item!!}</li>
                      @endforeach
                    </ul>   
                </div>
                @endforeach
              </div>
              <div class="col-md-4"> 
                <nav class="bs-docs-sidebar hidden-print hidden-sm hidden-xs" data-spy="affix" data-offset-top="20" data-offset-bottom="100">
                  <ul class="nav bs-docs-sidenav"> 
                    @foreach($order as $pro_id) 
                    <li> <a href="#content{{$pro_id}}">{{$data[$pro_id]['start_at']}} - {{$data[$pro_id]['end_at']}} &nbsp; | {{$data[$pro_id]['name']}}  </a> </li>
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
