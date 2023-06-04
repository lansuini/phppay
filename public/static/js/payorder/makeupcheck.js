$(function() {
    apiPath += "payorder/";
    var a = common.getQuery("id");
    if (a == undefined) {
        alert("请求错误");
        location.href = contextPath + "check";
        return
    }
    common.getAjax(apiPath + "getMakeUp?id=" + a, function(b) {
        if (b.success) {
            bindData(b.result);        
        } else {
            alert(b.result);
            location.href = contextPath + "check"
        }
    });
    $("#pass, #noPass").click(submit);
    /* common.initDateTime("txtChannelNoticeTime") */
});
function bindData(a) {
    /* var b = $("#editModal"); */

    var type = common.getQuery("type");
    if(type == 'info'){
        $("#pass, #noPass").attr('style','display:none');
        $("#check_ip").val(a.check_ip);
        $("#admin_id").val(a.admin_id);
        $("#check_time").val(common.toDateStr("yyyy-MM-dd HH:mm:ss", a.check_time));
        $("#desc").val(a.desc);
        $("#desc").attr('disabled','disabled');
    } else {
        $(".check_ip, .admin_id,.check_time").attr('style','display:none');
    }

    if(a.status != '待审核'){
        $("#pass, #noPass").attr('style','display:none');
    }

    $("#id").val(a.id);
    $("#commiter_id").val(a.commiter_id);
    $("#platformOrderNo").val(a.platformOrderNo);
    $("#orderAmount").val(a.orderAmount);
    $("#channel").val(a.channel);
    $("#channelMerchantNo").val(a.channelMerchantNo);
    $("#channelOrderNo").val(a.channelOrderNo);
    $("#channelNoticeTime").val(common.toDateStr("yyyy-MM-dd HH:mm:ss", a.channelNoticeTime));
    $("#orderStatus").val(a.orderStatus);
    $("#commiter_desc").val(a.commiter_desc);
    $("#ip").val(a.ip);
    $("#created_at").val(common.toDateStr("yyyy-MM-dd HH:mm:ss", a.created_at));
    $("#pic").attr('src','data:image/png;base64,'+a.pic);
    $("#pic").attr('onclick','javascript:showimage("'+a.pic+'")');
    /* $("#checkUrl").val(a.url); */
    /* b.modal(); */
}
function submit() {
    var status = $(this).data('info');
    var id = $('#id').val();
    var desc = $('#desc').val();
    /* $("#status").val(status); */
    common.submit(apiPath + 'domakeUpCheck?id='+id+'&desc='+desc+'&status='+status, "editModal", function() {
        /* location.href = location.href */
        location.href = contextPath + "check"
    })
}


function showimage(source)
{
    $("#ShowImage_Form").find("#img_show").html("<image src='data:image/png;base64,"+source+"' class='carousel-inner img-responsive img-rounded' />");
    $("#ShowImage_Form").modal();
}
;