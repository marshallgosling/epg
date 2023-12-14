function loadNotifications()
{
    $.ajax({
        url: "/admin/api/notifications",
        dataType: 'json',
        success: function (data) {
            setupHTML(data);
        }
    });
}

function setupHTML(data)
{
    if(data.total > 0) {
        $('.notify-total').html(data.total).addClass('label label-danger');
        $('.notify-head').html(' &nbsp; &nbsp;共有 '+data.total+' 个通知');
    }
    else {
        $('.notify-total').html('');
        $('.notify-head').html(" &nbsp; &nbsp;没有未读通知");
    }
    var html = "";
    //if(data.generate > 0) {
        html += '<li><a href="/admin/notifications"> &nbsp; &nbsp;<i class="fa fa-film text-aqua"></i> &nbsp;' + data.generate + ' 个自动生成编单通知</a></li>';
    //}

    //if(data.excel > 0) {
        html += '<li><a href="/admin/notifications"> &nbsp; &nbsp;<i class="fa fa-users text-yellow"></i> &nbsp;' + data.audit + ' 个审核通知</a></li>';
    //}

    html += '<li><a href="/admin/notifications"> &nbsp; &nbsp;<i class="fa fa-file-excel-o text-green"></i> &nbsp;' + data.excel + ' 个Excel导出通知</a></li>';
    
    html += '<li><a href="/admin/notifications"> &nbsp; &nbsp;<i class="fa fa-play"></i> &nbsp;' + data.xml + ' 个导出串联单通知</a></li>';
    
    html += '<li><a href="/admin/notifications"> &nbsp; &nbsp;<i class="fa fa-calculator text-read"></i> &nbsp;' + data.statistic + ' 个统计数据通知</a></li>';
    
    $('#notification_list').html(html);
}

setInterval('loadNotifications()', 30000);
loadNotifications();