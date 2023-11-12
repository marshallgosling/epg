<div class="row"><div class="col-md-6"><div class="box">

    <div class="box-header">

        <div class="btn-group">
            <a class="btn btn-primary btn-sm tree-654ce72915b12-tree-tools" data-action="expand" title="展开">
                <i class="fa fa-plus-square-o"></i> 展开
            </a>
            <a class="btn btn-primary btn-sm tree-654ce72915b12-tree-tools" data-action="collapse" title="收起">
                <i class="fa fa-minus-square-o"></i> 收起
            </a>
        </div>

                <div class="btn-group">
            <a class="btn btn-info btn-sm tree-654ce72915b12-save" title="保存"><i class="fa fa-save"></i><span class="hidden-xs"> 保存</span></a>
        </div>
        
                <div class="btn-group">
            <a class="btn btn-warning btn-sm tree-654ce72915b12-refresh" title="刷新"><i class="fa fa-refresh"></i><span class="hidden-xs"> 刷新</span></a>
        </div>
        
        <div class="btn-group">
            
        </div>

        
    </div>
    <!-- /.box-header -->
    <div class="box-body table-responsive no-padding">
        <div class="dd" id="tree-programs">
            <ol class="dd-list">
                <li class="dd-item" data-id="1">
                    <div class="dd-handle">
                        <i class="fa fa-bar-chart"></i> <strong>Dashboard</strong>   <a href="http://localhost:8088/admin" class="dd-nodrag">http://localhost:8088/admin</a>
                        <span class="pull-right dd-nodrag">
                            <a href="http://localhost:8088/admin/auth/menu/1/edit"><i class="fa fa-edit"></i></a>
                            <a href="javascript:void(0);" data-id="1" class="tree_branch_delete"><i class="fa fa-trash"></i></a>
                        </span>
                    </div>
                </li>
                <li class="dd-item" data-id="2">
                    <div class="dd-handle">
                        <i class="fa fa-user"></i> <strong>Roles</strong>   <a href="http://localhost:8088/admin/auth/roles" class="dd-nodrag">http://localhost:8088/admin/auth/roles</a>
                        <span class="pull-right dd-nodrag">
                            <a href="http://localhost:8088/admin/auth/menu/4/edit"><i class="fa fa-edit"></i></a>
                            <a href="javascript:void(0);" data-id="4" class="tree_branch_delete"><i class="fa fa-trash"></i></a>
                        </span>
                    </div>
                </li>
            </ol></div>
    </div>
    <!-- /.box-body -->
</div>
</div><div class="col-md-6"><div class="box box-success">
            <div class="box-header with-border">
            <h3 class="box-title">新增</h3>
            <div class="box-tools pull-right">
                            </div><!-- /.box-tools -->
        </div><!-- /.box-header -->
        <div class="box-body" style="display: block;">
        <form id="widget-form-654ce72926f27" method="POST" action="http://localhost:8088/admin/auth/menu" class="form-horizontal" accept-charset="UTF-8" pjax-container="1">
    <div class="box-body fields-group">

                    <div class="form-group  ">

<label for="parent_id" class="col-sm-2  control-label">父级菜单</label>

    <div class="col-sm-8">

        
        <input type="hidden" name="parent_id"><select class="form-control parent_id" style="width: 100%;" name="parent_id" data-value=""><option value=""></option><option value="0" selected>ROOT</option><option value="1">┝  Dashboard</option><option value="15">┝  库管理</option><option value="14">       ┝ 分类管理</option><option value="16">       ┝ 素材管理</option><option value="12">       ┝ 节目库</option><option value="13">┝  模版库</option><option value="19">       ┝ Channel V</option><option value="17">┝  编单</option><option value="18">       ┝ Channel 【V】</option><option value="2">┝  配置项</option><option value="3">       ┝ Users</option><option value="4">       ┝ Roles</option><option value="5">       ┝ Permission</option><option value="6">       ┝ Menu</option><option value="8">       ┝ Scheduling</option><option value="7">       ┝ Operation log</option><option value="9">       ┝ Log viewer</option><option value="10">       ┝ Config</option><option value="11">       ┝ Redis manager</option></select></div>
</div>
                    <div class="form-group  ">

    <label for="title" class="col-sm-2 asterisk control-label">标题</label>

    <div class="col-sm-8">

        
        <div class="input-group">

                        <span class="input-group-addon"><i class="fa fa-pencil fa-fw"></i></span>
            
            <input type="text" id="title" name="title" value="" class="form-control title" placeholder="输入 标题"></div>

        
    </div>
</div>
                    <div class="form-group  ">

    <label for="icon" class="col-sm-2 asterisk control-label">图标</label>

    <div class="col-sm-8">

        
        <div class="input-group">

                        <span class="input-group-addon"><i class="fa fa-pencil fa-fw"></i></span>
            
            <input style="width: 140px" type="text" id="icon" name="icon" value="fa-bars" class="form-control icon" placeholder="输入 图标"></div>

        <span class="help-block">
    <i class="fa fa-info-circle"></i> For more icons please see <a href="http://fontawesome.io/icons/" target="_blank">http://fontawesome.io/icons/</a>
</span>

    </div>
</div>
                    <div class="form-group  ">

    <label for="uri" class="col-sm-2  control-label">路径</label>

    <div class="col-sm-8">

        
        <div class="input-group">

                        <span class="input-group-addon"><i class="fa fa-pencil fa-fw"></i></span>
            
            <input type="text" id="uri" name="uri" value="" class="form-control uri" placeholder="输入 路径"></div>

        
    </div>
</div>
                    <div class="form-group  ">

    <label for="roles" class="col-sm-2  control-label">角色</label>

    <div class="col-sm-8">

        
        <select class="form-control roles" style="width: 100%;" name="roles[]" multiple data-placeholder="输入 角色" data-value=""><option value="1">Administrator</option></select><input type="hidden" name="roles[]"></div>
</div>
                    <div class="form-group  ">

<label for="permission" class="col-sm-2  control-label">权限</label>

    <div class="col-sm-8">

        
        <input type="hidden" name="permission"><select class="form-control permission" style="width: 100%;" name="permission" data-value=""><option value=""></option><option value="*">All permission</option><option value="dashboard">Dashboard</option><option value="auth.login">Login</option><option value="auth.setting">User setting</option><option value="auth.management">Auth management</option><option value="ext.scheduling">Scheduling</option><option value="ext.log-viewer">Logs</option><option value="ext.config">Admin Config</option><option value="ext.redis-manager">Redis Manager</option></select></div>
</div>
                    <input type="hidden" name="_token" value="Z0NAZmwhGxLRAcd2uTmGBYcegiLDFKcJQ7o0gAOu" class="_token"></div>

            <input type="hidden" name="_token" value="Z0NAZmwhGxLRAcd2uTmGBYcegiLDFKcJQ7o0gAOu"><!-- /.box-body --><div class="box-footer">
        <div class="col-md-2"></div>

        <div class="col-md-8">
                        <div class="btn-group pull-left">
                <button type="reset" class="btn btn-warning pull-right">重置</button>
            </div>
            
                        <div class="btn-group pull-right">
                <button type="submit" class="btn btn-info pull-right">提交</button>
            </div>
                    </div>
    </div>
    </form>

    </div><!-- /.box-body -->
    </div>

<script>
    $(function () { 
        $('#tree-programs').nestable({maxDepth: 1});


    });
</script></div></div>