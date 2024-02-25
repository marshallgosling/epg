<div class="row">
    <div class="col-md-8 docs-section">

<h1 id="section1" class="page-header">模版类型</h1>
<h4>前置说明</h4>
<div class="bs-callout bs-callout-primary"> 
  <h4>星空中国和星空国际模版为有状态模版</h4> 
  <p><code>有状态模版</code>，顾名思义，即模版会保存每次生成节目单后的<code>上下文</code>状态</p> 
  <p>这样在再次生成节目单时，会根据<code>上下文</code>选择符合逻辑的节目</p>
</div>
<div class="bs-callout bs-callout-info"> 
  <h4>Channel V（ V China ）为无状态模版</h4> 
  <p><code>无状态模版</code>，顾名思义，即模版没有<code>上下文</code>状态。</p> 
  <p>这样在每次生成节目单时，都不会考虑前后节目单之间是否有关联。</p>
</div>
<p>&nbsp;</p>
<h1 id="section1" class="page-header">星空中国和星空国际模版</h1>
<h4>创建单条模版记录</h4>
<ul>
<li>第一步，输入名称，开始时间（24H），时长（一般为30分钟的倍数，如：00:30:00），播选择出计划</li>
<li>第二步，设定排序值（1 ～ 24）的数字，每从小到大排序，用于模版记录的排序和展示。</li>
<li>第三步，保存模版记录。</li>
<li>第四步，点击模版名称进入<code>普通模版编排</code>，添加模版具体<code>编排规则</code>记录</li>
<li>第五步，保存编排规则记录</li>
<li>第六步，启用模版</li>
</ul>
<p><img class="img-thumbnail img-responsive" src="/images/help/template/编单系统-星空中国-模版-1.png" /></p>

<p>&nbsp;</p>
<div class="bs-callout bs-callout-warning"> 
  <h4>编排规则</h4> 
  <p>编排规则代表某一个模版内可以编排的内容类型和约束</p>
  <p>生成节目单时，会根据<code>上下文</code>选择符合逻辑的节目</p>
</div>
<p><img class="img-thumbnail img-responsive" src="/images/help/template/编单系统-普通模版编排-1.png" /></p>
<h4>规则：类型</h4>
<p>类型： 固定、随机、广告。 </p>
<ul>
<li>固定： 固定选择某一档节目进行编排，必须要指定节目名称（一般为剧集名称），编排完后重复。</li> 
<li>随机： 系统随机选择一档节目进行编排 ，必须要指定节目栏目（电影，电视剧，卡通等），编排完后重新随机选择下一部。</li>
<li>广告： 目前没有使用</li>
</ul>
<p>&nbsp;</p> 
<h4>规则：分类标签</h4>
<p>选择一个对应的分类，系统在编排的时候会只再该分类下进行搜索匹配。</p>
<p>当规则类型为<code>随机</code>时，分类标签为<code>必填</code></p> 
<p>&nbsp;</p> 
<div class="bs-callout bs-callout-warning"> 
  <h4>编排数据</h4> 
  <p>编排数据存放着生成编单时的上下文数据</p>
  <p>生成节目单时，会根据<code>上下文</code>选择符合逻辑的节目</p>
  <p>可以<code>手动修改</code>这部分数据，影响上下文编排，如强制更换节目，重复播放等</p>
</div>

<p><img class="img-thumbnail img-responsive" src="/images/help/template/编单系统-普通模版编排-2.png" /></p>
<h4>编排数据：剧集</h4>
<p>在规则类型为<code>固定</code>时，必须要指定一个剧集名。</p>
<p>这里保存着编排时的剧集名称</p> 
<p>&nbsp;</p> 
<h4>编排数据：播出编号</h4>
<p><code>播出编号</code>存放着最后一次选中的节目播出编号</p>
<p>可以通过修改播出编号，让编单程序在下次编排的时候从选中的节目下一集开始编排</p> 
<p>&nbsp;</p> 
<h4>编排数据：日期范围和播出日</h4>
<p><code>日期范围</code>该项编排规则使用的日期范围，超过时间则自动忽略</p>
<p><code>播出日</code>该项编排的应用周期，如周六周日，选中的日期匹配上才会被编排</p> 
<p>&nbsp;</p> 
<h4>编排数据：连载集数</h4>
<p><code>连载集数</code>一次编排情况下，系统选择多少集内容</p>
<p>取值范围在1～4</p> 
<p>&nbsp;</p> 
<h4>编排数据：标题和状态</h4>
<p><code>标题</code>仅用于展示播出编号对应的节目标题，方便识别</p>
<p><code>状态</code>标识该规则当前的状态，编排中，编排完成，错误</p> 
<p>&nbsp;</p> 
<p></p>