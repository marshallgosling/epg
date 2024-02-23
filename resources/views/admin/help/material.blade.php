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
    <div class="col-md-8 docs-section">

<h1 id="section1" class="page-header">同步和扫描素材入库</h1>
<h4>1. 检查素材记录信息</h4>
<p>找到素材状态为不可用的记录</p>
<p><img class="img-thumbnail img-responsive" src="/images/help/material/编单系统-物料管理-1.png"/></p>
<p>点击“编辑”按钮，可以查看素材是否有文件路径信息</p>
<p><img class="img-thumbnail img-responsive" src="/images/help/material/编单系统-物料管理-2.png"/></p>

<h4>2. 准备及检查素材文件（mxf格式文件）</h4>
<p>准备好相应的素材文件，mxf格式的媒体文件，复制到相应的目录<br/>可用的目录有:</p>
<figure class="highlight"><pre><code class="language-html" data-lang="html">//可用路径，请确认盘符
Y:\MV\
Y:\宣传片垫片\
Y:\卡通\
Y:\电视剧\
Y:\电影\
Y:\音综\
Y:\其他\
</code></pre></figure>

<p>需要注意的是：</p>
<ul>
<li>文件夹必须是上述列出的位置</li> <li>文件名必须是以 <code>素材名称</code>.<code>播出编号</code>.<code>mxf</code> 统一命名规则 </li>
</ul>
<p><img class="img-thumbnail img-responsive" src="/images/help/material/1708692865086.jpg" /></p>

<h4 class="page-header">3. 批量选择</h4>
<p>确认好素材文件已经复制到指定的目录以后，就可以进行批量同步操作了</p>
<p><img class="img-thumbnail img-responsive" src="/images/help/material/编单系统-物料管理-3.png" /></p>
<p>点击完以后，就可以等待通知结果，查看同步信息。<br/>如果同步成功，素材和对应节目库里的记录状态都会变为“可用”状态</p>
<p><img class="img-thumbnail img-responsive" src="/images/help/material/编单系统-物料管理-4.png" /></p>
<p>&nbsp;</p><p>&nbsp;</p>
<h1 id="section2" class="page-header">添加全新素材入库</h1>
<p>该情况下，请先手动添加素材信息（可批量创建）</p>
<p><img class="img-thumbnail img-responsive" src="/images/help/material/编单系统-物料管理-5.png" /></p>
<h4>1. 新增单条详细记录 </h4>
<p><img class="img-thumbnail img-responsive" src="/images/help/material/编单系统-物料管理-6.png" /></p>
<h4>2. 批量添加记录 </h4>
<p><img class="img-thumbnail img-responsive" src="/images/help/material/编单系统-物料管理-7.png" /></p>
<p>注：批量创建会自动生成播出编号（按序列生成），请注意文件名中的编号是否一致。</p>
<h4>3. 同步和扫描素材入库</h4>
<p>此处和情况 1 的处理流程完全一致，请参考上述流程</p>
<p>&nbsp;</p><p>&nbsp;</p>
<h1 id="section3" class="page-header">通知</h1>
<p>不管同步情况如何，都会在通知列表里得到一个同步结果通知，可以去菜单 统计 -> 通知 内查看</p>
<p><img class="img-thumbnail img-responsive" src="/images/help/material/1708696765986.jpg" /></p>

</div>
<div class="col-md-4"> 
                <nav class="epg-sidebar epg-sidebar-info hidden-print hidden-sm hidden-xs" id="epgAffix">
                  <ul class="nav epg-sidenav"> 
                    
                    <li> <a href="#section1"> 1. 同步和扫描素材入库 </a> </li>
                    <li> <a href="#section2"> 2. 添加全新素材入库 </a> </li>
                    <li> <a href="#section3"> 3. 通知 </a> </li>
                   
                  </ul>
              </div>
</div>
<script type="text/javascript">
  $(function () {
    $('#epgAffix').affix({offset: {top: 130}});
    $('body').scrollspy({ target: '#epgAffix' })
  });
</script>