<style type="text/css">
.docs-section { margin-bottom: 60px;}
    .docs-section p { margin: 0 0 10px; }
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
.epg-sidebar .nav .nav>li>a {
    padding-top: 1px;
    padding-bottom: 1px;
    padding-left: 30px;
    font-size: 12px;
    font-weight: 400;
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
            <div class="box-header"></div>
            <div class="box-body table-responsive">
                {!!$content!!}
            </div>
        </div>
    </div>
</div>