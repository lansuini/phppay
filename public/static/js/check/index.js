$(function() {
    common.getAjax(apiPath + "getbasedata?requireItems=checkStatusCode,bankrollType,bankrollDirection", function(a) {
        $("#selCheckStatus").initSelect(a.result.checkStatusCode, "key", "value");
        $("#selCheckStatus").val(0);//设置默认选中
        $("#btnSearch").initSearch(apiPath + "check/search", getColumns());
        $("#selBankrollType").initSelect(a.result.bankrollType, "key", "value", "请选择资金类型");
        $("#selBankrollDirection").initSelect(a.result.bankrollDirection, "key", "value", "请选择资金方向");
    });
    $("#btnSubmit").click(submitPerfect);
    $("#btnSoSubmit").click(submitSoPerfect);
    $(".pwdBtnSubmit").click(pwdBtnSubmit);

    $("#editSoModal #btnFile, #editModal #btnFile").change(function(){
        var v = $(this).val();
        var reader = new FileReader();
        reader.readAsDataURL(this.files[0]);
        reader.onload = function(e){
          $('#editModal #file_base64, #editSoModal #file_base64').val(e.target.result);
        };
     });

    $("#editSoModal #selOrderStatus").change(function() {
        if ($(this).val() == "Fail") {
            $("#txtFailReason").parent().show()
        } else {
            $("#txtFailReason").val("").parent().hide()
        }
    });


});
function getColumns() {
    return [{
        field: "-",
        title: "#",
        align: "center",
        formatter: function(b, c, a) {
            return a + 1
        }
    }, {
        field: "type",
        title: "类型",
        align: "center"
    }, {
        field: "relevance",
        title: "提审内容",
        align: "center",
    }, {
        field: "commiter_id",
        title: "提审人",
        align: "center",
    }, {

        field: "created_at",
        title: "提审时间",
        align: "center",
        formatter: function(b, c, a) {
            return common.toDateStr("yyyy-MM-dd HH:mm:ss", b)
        }
    }, {
        field: "status",
        title: "状态",
        align: "center"
    }, {
        field: "-",
        title: "操作",
        align: "center",
        formatter: function(b, c, a) {
            if(c.type == "支付密码修改" || c.type == "登录密码修改" || c.type == "余额调整" || c.type == '渠道余额取款') {
                return c.status == "待审核"? "<a onclick='showPwdModal(" + JSON.stringify(c) + ")'>审核</a> | <a onclick='showPwdModal(" + JSON.stringify(c) + ")'>详情</a>" : "<a onclick='showPwdModal(" + JSON.stringify(c) + ")'>详情</a>" ;
            }
            if(c.status == '待审核'){
                if(c.type == '支付补单'){
                    return "<a onclick='showEditModal(" + JSON.stringify(c) + ")'>编辑</a> | <a target='_blank' href='" + c.url + "?id=" + c.id + "&type=check'>审核</a> |<a target='_blank' href='" + c.url + "?id=" + c.id + "&type=info'>详情</a>";
                } else if(c.type == '代付补单'){
                    return "<a onclick='showEditSoModal(" + JSON.stringify(c) + ")'>编辑</a> | <a target='_blank' href='" + c.url + "?id=" + c.id + "&type=check'>审核</a> |<a target='_blank' href='" + c.url + "?id=" + c.id + "&type=info'>详情</a>";
                } else {
                    return "<a target='_blank' href='" + c.url + "?id=" + c.id + "&type=check'>审核</a> |<a target='_blank' href='" + c.url + "?id=" + c.id + "&type=info'>详情</a>";
                }
            } else if(c.status == '审核通过' || c.status == '审核不通过') {
                return "<a target='_blank' href='" + c.url + "?id=" + c.id + "&type=info'>详情</a>";
            }

        }
    }]
}


function showPwdModal(a) {
    $('#div-sys-fee').hide();
    $('#div-fact-fee').hide();
    if(a.type == "余额调整") {
        var title = "余额调整审核";
        $('.resetPassword').hide();
        $(".submitWithdraw").hide();
        $(".balanceAjustment").show();
        $("#selBankrollType").val(a.content.bankrollType);
        $("#selBankrollDirection").val(a.content.bankrollDirection);
        // 充值显示系统手续费和实际手续费
        if(a.content.bankrollDirection == 'Recharge'){
            $("#sysFee").val(a.content.sysFee);
            $("#factFee").val(a.content.factFee);
            $('#div-sys-fee').show();
            $('#div-fact-fee').show();
        }
        $("#txtAmount").val(a.content.amount);
        $("#txtSummary").val(a.content.summary);

    } else if (a.type == "渠道余额取款") {
        var title = "渠道余额取款";
        $('.resetPassword').hide();
        $(".balanceAjustment").hide();
        $(".submitWithdraw").show();
        $("#accountNoLabel").html('取款订单');
        $("#withdrawbank").val(a.content.bankName);
        $("#withdrawname").val(a.content.userName);
        $("#withdrawcard").val(a.content.cardNo);
        $("#withdrawmoney").val(a.content.issueAmount);
        $("#withdrawdesc").val(a.content.desc);
    } else {
        var title = a.type == "登录密码修改"? "登录密码审核" : "支付密码审核";
        $(".balanceAjustment").hide();
        $(".submitWithdraw").hide();
        $('.resetPassword').show();
    }
    $(".modal-title").html(title);
    $("#txtAuditId").val(a.id);
    $("#passwordtype").val(a.type);
    $("#accountNo").val(a.relevance);
    $("#merchantNoLabel").hide();
    if(a.merchantShortName){
        $("#merchantNoLabel").show();
        $("#merchantShortName").val(a.merchantShortName);
    }
    $("#ip").val(a.ip);
    $("#commiter_id").val(a.commiter_id);
    var admin_id = '';
    if (a.status != "待审核") {
        admin_id = a.admin_id;
    }

    $("#admin_id").val(admin_id);
    $("#check_ip").val(a.check_ip);
    $("#created_at").val(common.toDateStr("yyyy-MM-dd HH:mm:ss", a.created_at));
    $("#check_time").val(a.check_time);
    $("#newpassword").val(a.content.password);
    $(".pwdButton").hide();
    if (a.status == "待审核") {
        $(".pwdButton").show();
    }
    $("#editPwdModal").modal()
}

function pwdBtnSubmit() {
    if ($("#editPwdModal .checkPwd").val() == "") {
        myAlert.warning("请输入审核密码");
        return
    }
    if ($("#passwordtype").val() == "") {
        myAlert.warning("密码类型不对");
        return
    }
    if ($("#txtAuditId").val() == "") {
        myAlert.warning("请选择审核信息");
        return
    }
    // if ($("#newpassword").val() == "") {
    //     myAlert.warning("密码不能为空");
    //     return
    // }
    var passwordtype = $("#passwordtype").val() ;
    var id = $("#txtAuditId").val() ;
    var msg = '';
    var method = $(this).attr('data-field');

    if(passwordtype == "余额调整") {
        var action = method == "resetpassword" ? "balanceAudit" : "balanceUnaudit";
        var url = "balanceadjustment/" + action + "?id=" + id + "&auditType=" + passwordtype;
    }else if(passwordtype == "渠道余额取款"){
        var result = method == "resetpassword" ? "audit" : "unaudit";
        var url = "channel/settlementbalance/withdrawSubmit?id=" + id + "&result=" + result + "&desc=" + $("#withdrawdesc").val();
    }else {
        var newpassword = $("#newpassword").val() ;
        var c = passwordtype == "登录密码修改" ? "登录密码" : "支付密码";
        if (method == "resetpassword") {
            msg = "，新" + c + "：" + newpassword;
        }
        var url = "merchant/audit/" + method + "?newpassword=" + newpassword + "&id=" + id + "&passwordtype=" + passwordtype;
    }
    url = url + '&checkPwd=' + $("#editPwdModal .checkPwd").val()
    $(".pwdBtnSubmit").attr('disabled',"disabled");
    common.getAjax(apiPath + url,function(e){
        if (e && e.success) {
            myAlert.success("操作成功" + msg, undefined, function() {
                location.href = location.href
            })
        }else {
            myAlert.error(e.result, undefined, function () {
                location.href = location.href;
            })
        }
    })
}

function showEditModal(a) {    
    console.log(a.id);
    console.log(a);
    var b = $("#editModal");
    b.find("div.modal-body").find("input,select").val("").end().find("div.form-add").hide().end().find("div.form-edit").hide();
    b.find("h4.modal-title").html("审核").end().find("div.form-edit").show();
    if(a.type == '支付补单' || a.type == '代付补单'){
        $("#editModal #id").val(a.id);
        $("#editModal #orderId").val(a.content.orderId);
        $("#editModal #platformOrderNo").val(a.content.platformOrderNo);
        $("#editModal #orderAmount").val(a.content.orderAmount);
        $("#editModal #channel").val(a.content.channel);
        $("#editModal #channelMerchantNo").val(a.content.channelMerchantNo);
        $("#editModal #channelOrderNo").val(a.content.channelOrderNo);
        $("#editModal #channelNoticeTime").val(a.content.channelNoticeTime);
        $("#editModal #selOrderStatus").val(a.content.orderStatus);
        $("#editModal #desc").val(a.content.desc);
        $("#editModal .type").val(a.type);
        if(a.content.pic != undefined){
            $("#editModal #pic").attr('src','data:image/png;base64,'+a.content.pic);
        } else {
            $("#editModal #pic").attr('style', 'display:none');
        }
    }
        b.modal()
}

function showEditSoModal(a) {    
    $("#txtFailReason").parent().hide();


    var b = $("#editSoModal");
    b.find("div.modal-body").find("input,select").val("").end().find("div.form-add").hide().end().find("div.form-edit").hide();
    b.find("h4.modal-title").html("审核").end().find("div.form-edit").show();
    if(a.type == '代付补单'){
        $("#editSoModal #id").val(a.id);
        $("#editSoModal #orderId").val(a.content.orderId);
        $("#editSoModal #platformOrderNo").val(a.content.platformOrderNo);
        $("#editSoModal #orderAmount").val(a.content.orderAmount);
        $("#editSoModal #channel").val(a.content.channel);
        $("#editSoModal #channelMerchantNo").val(a.content.channelMerchantNo);
        $("#editSoModal #channelOrderNo").val(a.content.channelOrderNo);
        $("#editSoModal #channelNoticeTime").val(a.content.channelNoticeTime);
        $("#editSoModal #selOrderStatus").val(a.content.orderStatus);
        /* $("#editSoModal #selOrderStatus").text("划款成功"); */
        /* $("#editSoModal #selOrderStatus").find("option[text='划款成功']").attr("selected","selected"); */
        /* $("#editSoModal #selOrderStatus").selectpicker({'selectedText': '划款成功'}); */
        $("#editSoModal #desc").val(a.content.desc);        
        $("#editSoModal .type").val(a.type);
        if(a.content.failReason != ''){
            $("#editSoModal #txtFailReason").val(a.content.failReason);
            $("#txtFailReason").parent().show()
        }
        if(a.content.pic != undefined){
            $("#editSoModal #pic").attr('src','data:image/png;base64,'+a.content.pic);
        } else {
            $("#editSoModal #pic").attr('style', 'display:none');
        }
    }
        b.modal()
}

function submitPerfect() {
    if ($("#editModal #channelOrderNo").val() == "") {
        myAlert.warning($("#editModal #channelOrderNo").attr("placeholder"));
        return
    }
    if ($("#editModal #channelNoticeTime").val() == "") {
        myAlert.warning($("#editModal #channelNoticeTime").attr("placeholder"));
        return
    }

    if ($("#editModal .checkPwd").val() == "") {
        myAlert.warning("请输入审核密码");
        return
    }

    var a = $("#editModal #btnFile")[0].files;

    if(a.length > 0 && a[0]['size'] > 2097152){
        myAlert.warning("上传图片不能大于2M");
        return
    }

    if(a.length > 0 && (a[0]['type'] != 'image/png' && a[0]['type'] != 'image/jpeg' && a[0]['type'] != 'image/jpg')){
        myAlert.warning("只能上传图片");
        return
    }

    var type = $("#editModal .type").val();

    var b = new FormData();
    b.append("file", a[0]);
    b.append("orderId", $("#editModal #orderId").val());
    b.append("type", 'up');
    b.append("channelOrderNo", $("#editModal #channelOrderNo").val());
    b.append("channelNoticeTime", $("#editModal #channelNoticeTime").val());
    b.append("desc", $("#editModal #desc").val());
    b.append("id", $("#editModal #id").val());
    b.append("file_base64",$('#editModal #file_base64').val());
    b.append("checkPwd",$("#editModal .checkPwd").val());

    common.uploadFile(apiPath + "payorder/perfect", b, function(c) {
        if (c.success == 1) {
            myAlert.success("操作成功");
            $("#editModal").modal("hide");
            $("#btnSearch").click()
        } else {
            myAlert.error(c.result.length > 0 ? c.result : "操作失败")
        }
    }) 
}


function submitSoPerfect() {
    if ($("#editSoModal .checkPwd").val() == "") {
        myAlert.warning("请输入审核密码");
        return
    }

    if ($("#editSoModal #channelOrderNo").val() == "") {
        myAlert.warning($("#editSoModal #channelOrderNo").attr("placeholder"));
        return
    }
    if ($("#editSoModal #channelNoticeTime").val() == "") {
        myAlert.warning($("#editSoModal #channelNoticeTime").attr("placeholder"));
        return
    }
    /* console.log($("#editSoModal #selOrderStatus").val()); */

    if ($("#editSoModal #selOrderStatus").val() == "") {
        myAlert.warning($("#editSoModal #selOrderStatus").find("option:selected").html());
        return
    }
    if ($("#editSoModal #selOrderStatus").val() == "Fail" && $("#editSoModal #txtFailReason").val() == "") {
        myAlert.warning($("#editSoModal #txtFailReason").attr("placeholder"));
        return
    }

    var a = $("#editSoModal #btnFile")[0].files;

    if(a.length > 0 && a[0]['size'] > 2097152){
        myAlert.warning("上传图片不能大于2M");
        return
    }

    if(a.length > 0 && (a[0]['type'] != 'image/png' && a[0]['type'] != 'image/jpeg' && a[0]['type'] != 'image/jpg')){
        myAlert.warning("只能上传图片");
        return
    }

    var type = $(".type").val();

    var b = new FormData();
    b.append("file", a[0]);
    b.append("orderId", $("#editSoModal #orderId").val());
    b.append("type", 'up');
    b.append("channelOrderNo", $("#editSoModal #channelOrderNo").val());
    b.append("channelNoticeTime", $("#editSoModal #channelNoticeTime").val());
    b.append("desc", $("#editSoModal #desc").val());
    b.append("id", $("#editSoModal #id").val());
    b.append("file_base64",$('#editSoModal #file_base64').val());
    b.append("orderStatus",$('#editSoModal #selOrderStatus').val());
    b.append("failReason",$('#editSoModal #txtFailReason').val());
    b.append("checkPwd",$("#editSoModal .checkPwd").val());

    common.uploadFile(apiPath + "settlementorder/perfect", b, function(c) {
        if (c.success == 1) {
            myAlert.success("修改成功，请等待审核");
            $("#editSoModal").modal("hide");
            $("#btnSearch").click()
        } else {
            myAlert.error(c.result.length > 0 ? c.result : "操作失败")
        }
    }) 
}
;