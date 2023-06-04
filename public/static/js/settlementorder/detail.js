$(function() {
    apiPath += "settlementorder/";
    var a = common.getQuery("orderId");
    if (a == undefined) {
        alert("请求错误");
        location.href = contextPath + "settlementorder/";
        return
    }
    common.getAjax(apiPath + "detail?orderId=" + a, function(b) {
        if (b.success) {
            bindData(b.result)
            $("#btnSearch").initSearch(apiPath + "getMakeUpList?platformOrderNo="+ b.result.platformOrderNo, getColumns(), {
                /* success_callback: buildSummary */
            })
        } else {
            location.href = contextPath + "settlementorder/"
        }
    });
    common.initDateTime("txtChannelNoticeTime")

    $("#btnFile").change(function(){
        var v = $(this).val();
        var reader = new FileReader();
        reader.readAsDataURL(this.files[0]);
        reader.onload = function(e){
          $('#file_base64').val(e.target.result);
        };
     });

    $("#btnSynchronizedState").click(function(){
        common.getAjax(apiPath + "getStatus?orderId=" + a, function(b) {
            console.log(b)
            if (b.status == 'Success') {
                alert('银行划款成功!')
            } else if(b.status == 'Fail') {
                alert('银行划款失败：' + b.failReason)
            } else  {
                alert('银行划款中：' + b.failReason)
            }
            location.reload();
        })
    });


});
function bindData(a) {
    $("#divWest").append(common.buildTabNav("订单信息")).append(buildOrder(a)).append(common.buildTabNav("收款人信息")).append(buildBank(a)).append(common.buildTabNav("订单金额与手续费信息")).append(buildAmount(a));
    $("#divEast").append(common.buildTabNav("商户信息")).append(buildMerchant(a)).append(common.buildTabNav("上游渠道信息")).append(buildChannel(a)).append(common.buildTabNav("审核信息")).append(buildCheck(a));
    switch (a.orderStatus) {
    case "Exception":
    case "Transfered":
        $("#txtPlatformOrderNo").val(a.platformOrderNo);
        $("#txtOrderAmount").val(common.fixAmount(a.orderAmount));
        $("#txtChannel").val(a.channelDesc);
        $("#txtChannelMerchantNo").val(a.channelMerchantNo);
        $("#selOrderStatus").change(function() {
            if ($(this).val() == "Fail") {
                $("#txtFailReason").parent().show()
            } else {
                $("#txtFailReason").val("").parent().hide()
            }
        });
        $("#btnPerfect").show().click(showPerfectModal);
        $("#btnSynchronizedState").show()
        $("#btnSubmit").click(submitPerfect);
        break;
    case "Fail": 
        $("#btnSendNotify").show().click(function() {
            common.submit(apiPath + "notify?orderId=" + common.getQuery("orderId"))
        });
        break;
    case "Success":
        $("#btnSendNotify").show().click(function() {
            common.submit(apiPath + "notify?orderId=" + common.getQuery("orderId"))
        });
        break;
    }
    initShowModal("showMerchantParam", "回传参数", a.merchantParam);
    initShowModal("showBackNoticeUrl", "异步通知地址", a.backNoticeUrl);
    common.getAjax(apiPath + "getsuborderno?orderId=" + a.orderId, function(b) {
        if (b && b.result.subOrderNo && b.result.subOrderNo.length > 0) {
            $("span[data-holder]").html(b.result.subOrderNo)
        }
    })
}
function appendShowLink(b, a) {
    return a != null && a.length > 0 ? "<a id='" + b + "' style='color:#6dc3ea; cursor:pointer'>查看</a>" : "-"
}
function initShowModal(c, b, a) {
    $("#" + c).click(function() {
        $("#showModal").find(".modal-title").html(b).end().find(".modal-body").html(a).end().modal()
    })
}
function buildOrder(b) {
    var a = [];
    a.push({
        key: "账务日期",
        value: b.accountDate
    });
    // a.push({
    //     key: "交易流水号",
    //     value: b.transactionNo
    // });
    a.push({
        key: "申请人",
        value: b.applyPerson
    });
    a.push({
        key: "申请人IP",
        value: b.applyIp
    });
    a.push({
        key: "申请时间",
        value: common.toDateStr("yyyy-MM-dd HH:mm:ss", b.applyTime)
    });
    a.push({
        key: "平台订单号",
        value: b.platformOrderNo
    });
    a.push({
        key: "平台子订单号",
        value: "-",
        holder: "subOrderNo"
    });
    a.push({
        key: "订单状态",
        value: b.orderStatusDesc
    });
    a.push({
        key: "订单创建时间",
        value: common.toDateStr("yyyy-MM-dd HH:mm:ss", b.createTime)
    });


    return common.buildTabPanel(a)
}

function buildCheck(b) {
    var a = [];

    a.push({
        key: "审核人",
        value: b.auditPerson
    });
    a.push({
        key: "审核时间",
        value: common.toDateStr("yyyy-MM-dd HH:mm:ss", b.auditTime)
    });
    a.push({
        key: "审核人IP",
        value: b.auditIp
    });
    return common.buildTabPanel(a)
}
function buildMerchant(b) {
    var a = [];
    a.push({
        key: "商户号",
        value: b.merchantNo
    });
    a.push({
        key: "商户订单号",
        value: b.merchantOrderNo
    });
    a.push({
        key: "代付原因/用途",
        value: b.orderReason
    });
    a.push({
        key: "交易摘要",
        value: b.tradeSummary
    });
    a.push({
        key: "回传参数",
        value: appendShowLink("showMerchantParam", b.merchantParam)
    });
    a.push({
        key: "异步通知地址",
        value: appendShowLink("showBackNoticeUrl", b.backNoticeUrl)
    });
    a.push({
        key: "商户请求时间",
        value: common.toDateStr("yyyy-MM-dd HH:mm:ss", b.merchantReqTime)
    });
    a.push({
        key: "回调商户",
        value: b.callbackSuccess ? '成功' : '失败'
    });
    a.push({
        key: "回调商户次数",
        value: b.callbackLimit
    });
    return common.buildTabPanel(a)
}
function buildBank(b) {
    var a = [];
    a.push({
        key: "收款银行",
        value: b.bankCodeDesc
    });
    a.push({
        key: "收款人卡号",
        value: b.bankAccountNo
    });
    a.push({
        key: "收款人姓名",
        value: b.bankAccountName
    });
    a.push({
        key: "开户行所属省",
        value: b.province
    });
    a.push({
        key: "开户行所属市",
        value: b.city
    });
    a.push({
        key: "开户行",
        value: b.bankName
    });
    return common.buildTabPanel(a)
}
function buildAmount(b) {
    var a = [];
    a.push({
        key: "订单金额",
        value: common.fixAmount(b.orderAmount)
    });
    // a.push({
    //     key: "划款状态",
    //     value: b.orderStatusDesc
    // });
    a.push({
        key: "商户手续费",
        value: common.fixAmount(b.serviceCharge)
    });
    a.push({
        key: "上游手续费",
        value: common.fixAmount(b.channelServiceCharge)
    });

    return common.buildTabPanel(a)
}
function buildChannel(b) {
    var a = [];
    switch(b.channelAccountStatus){
        case 'Normal' : status = '正常';break;
        case 'Close' : status = '关闭';break;
        case 'Exception' : status = '异常';break;
        case 'Deleted' : status = '删除';break;
    }
    a.push({
        key: "上游渠道",
        value: b.channelDesc
    });
    a.push({
        key: "上游商户号",
        value: b.channelMerchantNo
    });
    a.push({
        key: "向上游推送时间",
        value: common.toDateStr("yyyy-MM-dd HH:mm:ss", b.pushChannelTime)
    });
    a.push({
        key: "上游账户",
        value: b.channelAccount
    });
    a.push({
        key: "上游账户状态",
        value: status
    });
    a.push({
        key: "上游订单号",
        value: b.channelOrderNo
    });
    a.push({
        key: "上游处理时间",
        value: common.toDateStr("yyyy-MM-dd HH:mm:ss", b.channelNoticeTime)
    });
    a.push({
        key: "处理标识",
        value: b.processTypeDesc
    });
    a.push({
        key: "失败原因",
        value: b.failReason
    });
   
  
    return common.buildTabPanel(a)
}
function showPerfectModal() {
    $("#txtChannelOrderNo,#txtChannelNoticeTime,#selOrderStatus,#txtFailReason").val("");
    $("#txtChannelNoticeTime").val(common.toDateStr("yyyy-MM-dd HH:mm:ss", {
        time: new Date().getTime()
    }));
    $("#txtFailReason").parent().hide();
    $("#perfectModal").modal()
}
function submitPerfect() {
    if ($("#txtChannelOrderNo").val() == "") {
        myAlert.warning($("#txtChannelOrderNo").attr("placeholder"));
        return
    }
    if ($("#txtChannelNoticeTime").val() == "") {
        myAlert.warning($("#txtChannelNoticeTime").attr("placeholder"));
        return
    }
    if ($("#selOrderStatus").val() == "") {
        myAlert.warning($("#selOrderStatus").find("option:selected").html());
        return
    }
    if ($("#selOrderStatus").val() == "Fail" && $("#txtFailReason").val() == "") {
        myAlert.warning($("#txtFailReason").attr("placeholder"));
        return
    }
    
    /* common.submit(apiPath + "perfect?orderId=" + common.getQuery("orderId"), "perfectModal", function() {
        location.href = location.href
    }) */

    var a = $("#btnFile")[0].files;
    if (a.length == 0) {
        myAlert.warning("请选择要上传的截图");
        return
    }

    if(a[0]['size'] > 2097152){
        myAlert.warning("上传图片不能大于2M");
        return
    }

    if(a[0]['type'] != 'image/png' && a[0]['type'] != 'image/jpeg' && a[0]['type'] != 'image/jpg'){
        myAlert.warning("只能上传图片");
        return
    }

    var b = new FormData();
    b.append("file", a[0]);
    b.append("orderId", common.getQuery("orderId"));
    b.append("type", 'in');
    b.append("channelOrderNo", $("#txtChannelOrderNo").val());
    b.append("channelNoticeTime", $("#txtChannelNoticeTime").val());
    b.append("desc", $("#desc").val());
    b.append("file_base64",$('#file_base64').val());
    b.append("orderStatus",$('#selOrderStatus').val());
    b.append("failReason",$('#txtFailReason').val());

    common.uploadFile(apiPath + "perfect", b, function(c) {
        if (c.success == 1) {
            myAlert.success("操作成功");
            $("#perfectModal").modal("hide");
            $("#btnSearch").click()
            window.location.reload();
        } else {
            myAlert.error(c.result.length > 0 ? c.result : "操作失败")
        }
    }) 
}

function getColumns() {
    return [{
        field: "channelOrderNo",
        title: "上游订单号",
        align: "center"
    }, {
        field: "channelNoticeTime",
        title: "上游交易时间",
        align: "center",
        formatter: function(b, c, a) {
            return common.toDateStr("yyyy-MM-dd HH:mm:ss", b)
        }
    }, {
        field: "created_at",
        title: "提审时间",
        align: "center",
        formatter: function(b, c, a) {
            return common.toDateStr("yyyy-MM-dd HH:mm:ss", b)
        }
    }, {
        field: "check_time",
        title: "审核时间",
        align: "center",
        formatter: function(b, c, a) {
            return common.toDateStr("yyyy-MM-dd HH:mm:ss", b)
        }
    }, {
        field: "status",
        title: "审核状态",
        align: "center",
        formatter: function(b, c, a) {
            if(b == '待审核'){
                $('#btnPerfect').attr('style','display:none');
            }
            return b;
        }
    }, {
        field: "commiter_desc",
        title: "提审备注",
        align: "center"
    }, {
        field: "desc",
        title: "审核备注",
        align: "center"
    }]
}
;