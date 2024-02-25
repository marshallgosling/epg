<div class="row">
    <div class="col-md-8 docs-section">

<h1 id="section1" class="page-header">同步和扫描物料入库</h1>
<h4>0. 前置说明</h4>
<div class="bs-callout bs-callout-warning"> 
  <h4>物料库和节目库的处理关系</h4> 
  <p>物料库和节目库<code>不限制先后创建顺序</code>，它们之间通过 <code>播出编号</code> 建立唯一关联。</p> 
  <p>先创建物料再导入节目库，或先在节目库内批量创建，再创建物料库，都是可行的。<code>在最终送达播出前三天会进行内容一致性检查，如缺失物料则会通知警告！</code></p>
  <p>一旦节目库内的播出编号生成，并且进入了编单，那<code>播出编号将不能修改</code>。</p>
</div>
<p>&nbsp;</p>

<h4>1. 检查物料记录信息</h4>
<div class="bs-callout bs-callout-info"> 
  <h4>物料库和节目库的状态字段说明</h4> 
  <p>物料库和节目库的<code>状态</code>字段<code>不可手动修改</code>，以免导致可用性失信。</p> 
  <p><i class="fa fa-check text-green"></i> 表示“可用”；<i class="fa fa-close text-red"></i> 表示“不可用”</p>
  <p>在物料库内进行<code>批量同步</code>后，包括<code>物料库和对应节目库</code>内的<code>状态</code>字段都会进行修改。</p>
</div>

<p>找到物料状态为不可用的记录</p>
<p><img class="img-thumbnail img-responsive" src="/images/help/material/编单系统-物料管理-1.png"/></p>
<p>点击“编辑”按钮，可以查看物料是否有文件路径信息</p>
<p><img class="img-thumbnail img-responsive" src="/images/help/material/编单系统-物料管理-2.png"/></p>

<h4>2. 准备及检查物料文件（mxf格式文件）</h4>
<p>准备好相应的物料文件，mxf格式的媒体文件，复制到相应的目录<br/>可用的目录有:</p>
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
<li>文件夹必须是上述列出的位置</li> <li>文件名必须是以 <code>物料名称</code>.<code>播出编号</code>.<code>mxf</code> 统一命名规则 </li>
</ul>
<p><img class="img-thumbnail img-responsive" src="/images/help/material/1708692865086.jpg" /></p>

<h4 class="page-header">3. 批量同步</h4>
<p>确认好物料文件已经复制到指定的目录以后，就可以进行批量同步操作了</p>
<p><img class="img-thumbnail img-responsive" src="/images/help/material/编单系统-物料管理-3.png" /></p>
<p>点击完以后，就可以等待通知结果，查看同步信息。<br/>如果同步成功，物料和对应节目库里的记录状态都会变为“可用”状态</p>
<p><img class="img-thumbnail img-responsive" src="/images/help/material/编单系统-物料管理-4.png" /></p>
<p>&nbsp;</p><p>&nbsp;</p>
<h1 id="section2" class="page-header">添加全新物料入库（可批量创建）</h1>
<p>如未找到对应的物料记录，请先手动添加物料信息</p>
<p><img class="img-thumbnail img-responsive" src="/images/help/material/编单系统-物料管理-5.png" /></p>
<h4>1. 新增单条详细记录 </h4>
<p><img class="img-thumbnail img-responsive" src="/images/help/material/编单系统-物料管理-6.png" /></p>
<h4>2. 批量添加记录 </h4>
<p><img class="img-thumbnail img-responsive" src="/images/help/material/编单系统-物料管理-7.png" /></p>
<p>注：批量创建会自动生成播出编号（按序列生成），请注意文件名中的编号是否一致。</p>
<h4>3. 同步和扫描物料入库</h4>
<p>此处和情况 “同步和扫描物料入库” 的处理流程完全一致，请参考上述流程</p>
<p>&nbsp;</p><p>&nbsp;</p>
<h1 id="section3" class="page-header">检查媒体文件信息</h1>
<p>不管何时，都可以进行媒体文件信息的检查，只需在<code>物料库</code>里<code>点击</code>相应的<code>播出编号</code>字段即可</p>
<p><img class="img-thumbnail img-responsive" src="/images/help/material/1708829998580.jpg" /></p>
<div class="bs-callout bs-callout-warning"> 
  <h4>使用说明</h4> 
  <p>读取媒体文件的信息需要时间，首次分析一般需要 3 秒，因此需要耐心等待，成功后再次查看则无需等待。</p> 
  <p>媒体信息的各项字段，这里不作说明。</p>
</div>
<p>&nbsp;</p><p>&nbsp;</p>
<h1 id="section4" class="page-header">通知</h1>
<p>不管同步情况如何，都会在通知列表里得到一个同步结果通知，可以去菜单 统计 -> 通知 内查看</p>
<p><img class="img-thumbnail img-responsive" src="/images/help/material/1708696765986.jpg" /></p>

</div>
<div class="col-md-4"> 
                <nav class="epg-sidebar epg-sidebar-info hidden-print hidden-sm hidden-xs" id="epgAffix">
                  <ul class="nav epg-sidenav"> 
                    
                    <li> <a href="#section1"> 同步和扫描物料入库 </a> </li>
                    <li> <a href="#section2"> 添加全新物料入库 </a> </li>
                    <li> <a href="#section3"> 检查媒体文件信息 </a> </li>
                    <li> <a href="#section4"> 通知 </a> </li>
                   
                  </ul>
              </div>
</div>
<script type="text/javascript">
  $(function () {
    $('#epgAffix').affix({offset: {top: 130}});
    $('body').scrollspy({ target: '#epgAffix' })
  });
</script>