$(function() {
    common.getAjax(apiPath + "getbasedata?requireItems=bankrollType,bankrollDirection,commonStatus2", function(a) {
        apiPath += "balanceadjustment/";
        $("#divSearch select[data-field=bankrollType]").initSelect(a.result.bankrollType, "key", "value", "资金类型");
        $("#divSearch select[data-field=bankrollDirection]").initSelect(a.result.bankrollDirection, "key", "value", "资金方向");
        $("#divSearch select[data-field=status]").initSelect(a.result.commonStatus2, "key", "value", "状态");
        // $("#selBankrollType").initSelect(a.result.bankrollType, "key", "value", "请选择资金类型");
        $("#btnSearch").initSearch(apiPath + "search", getColumns(), {
            success_callback: buildSummary
        });
    });
    common.getAjax(apiPath + "balanceadjustment/getbasedata", function (a) {
        $("#selBankrollDirection").initSelect(a.code, "key", "value", "请选择资金方向");
    })
    $("#txtAmount").bind("input propertychange", function() {
        if (!common.isDecimal($(this).val())) {
            $(this).val("")
        }
    });
    //充值时显示手续费
    $('#selBankrollDirection').change(function(){
        if($(this).val() == 'Recharge'){
            $('#div-sys-fee').show();
            $('#div-fact-fee').show();
        }else{
            $('#div-sys-fee').hide();
            $('#div-fact-fee').hide();
        }
    });
    $("#btnAdd").click(function() {
        showEditModal()
    });
    //获取充值手续费
    $("#btnGetFee").click(getFee)

    $("#btnSubmit").click(submit)
    common.initSection()
    common.initDateTime("txtAuditBeginTime")
    common.initDateTime("txtAuditEndTime")
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
        field: "merchantNo",
        title: "商户号",
        align: "center"
    }, {
        field: "shortName",
        title: "商户简称",
        align: "center"
    }, {
        field: "platformOrderNo",
        title: "平台订单号",
        align: "center"
    }, {
        field: "bankrollTypeDesc",
        title: "资金类型",
        align: "center"
    }, {
        field: "bankrollDirectionDesc",
        title: "资金方向",
        align: "center"
    }, {
        field: "amount",
        title: "金额",
        align: "center",
        formatter: function(b, c, a) {
            return common.fixAmount(b)
        }
    }, {
        field: "summary",
        title: "摘要",
        align: "center"
    }, {
        field: "statusDesc",
        title: "状态",
        align: "center"
    }, {
        field: "applyPerson",
        title: "申请人",
        align: "center"
    }, {
        field: "applyTime",
        title: "申请时间",
        align: "center",
        formatter: function(b, c, a) {
            return common.toDateStr("yyyy-MM-dd HH:mm:ss", b)
        }
    }, {
        field: "auditPerson",
        title: "审核人",
        align: "center"
    }, {
        field: "auditTime",
        title: "审核时间",
        align: "center",
        formatter: function(b, c, a) {
            return common.toDateStr("yyyy-MM-dd HH:mm:ss", b)
        }
    }, {
        field: "status",
        title: "操作",
        align: "center",
        formatter: function(b, c, a) {
           return b == "Freeze" ? "<a onclick='unFreeze(" + JSON.stringify(c) + ")'>解冻</a>" : '' ;
        }
    }]
}

function unFreeze(c) {
    var merchant = c.merchantNo;
    var orderNo = c.platformOrderNo;
    var amount = c.amount;
    myConfirm.show({
        title: "您确定要解冻商户" + merchant + "的" + amount + "元",
        sure_callback: function() {
            common.getAjax(apiPath + "unFreeze" + "?merchant=" + merchant +"&orderNo=" + orderNo, function(e) {
                if (e && e.success) {
                    myAlert.success("操作成功", undefined, function() {
                        location.href = location.href
                    })
                } else {
                    myAlert.error(e.result);
                }
            })
        }
    })
}
function showEditModal(a) {
    $("#btnSubmit").attr('disabled',false);
    common.getAjax(apiPath + "getRandom",function (e) {
        if(e.success) {
            $('#txtRandom').val(e.random);
        }else{
            myAlert.warning("请重新刷新页面");
        }
        return;
    });
    $('#div-sys-fee').hide();
    $('#div-fact-fee').hide();
    var b = $("#editModal");
    b.find("div.modal-body").find("input[id],select[id]").val("");
    b.modal()
}
function submit() {
    if ($("#txtMerchantNo").val() == "") {
        myAlert.warning($("#txtMerchantNo").attr("placeholder"));
        return
    }
    if ($("#selBankrollType").val() == "") {
        myAlert.warning($("#selBankrollType").find("option:first").html());
        return
    }
    if ($("#selBankrollDirection").val() == "") {
        myAlert.warning($("#selBankrollDirection").find("option:first").html());
        return
    }
    if ($("#txtAmount").val() == "") {
        myAlert.warning($("#txtAmount").attr("placeholder"));
        return
    }
    if ($("#txtSummary").val() == "") {
        myAlert.warning($("#txtSummary").attr("placeholder"));
        return
    }
    if ($("#selBankrollDirection").val() == "Recharge" && $("#factFee").val() == "") {
        myAlert.warning("实际充值手续费不能为空");
        return
    }
    $("#btnSubmit").attr('disabled','disabled');
    common.submit(apiPath + "insert", "editModal", function() {
        location.href = location.href;

    })
}

//获取充值手续费
function getFee() {
    if ($("#txtMerchantNo").val() == "") {
        myAlert.warning($("#txtMerchantNo").attr("placeholder"));
        return
    }
    if ($("#selBankrollDirection").val() != "Recharge") {
        myAlert.warning("请选择资金方向为充值！");
        return
    }
    if ($("#txtAmount").val() == "") {
        myAlert.warning($("#txtAmount").attr("placeholder"));
        return
    }
    $("#btnGetFee").attr('disabled', 'disabled');
    str = "?merchantNo=" + $("#txtMerchantNo").val() + "&amount=" + $("#txtAmount").val();
    common.getAjax(apiPath + "getRechargeRate" + str, function (a) {
        $("#btnGetFee").removeAttr('disabled');
        if(a.success == 0){
            myAlert.warning(a.result);
        }else{
            $('#sysFee').val(a.result);
            $('#factFee').val(a.result);
        }
    });
    // $("#btnGetFee").removeAttr('disabled');
}

function buildSummary(i) {
    if (i && i.success && i.rows.length > 0) {
        var a = $("<div class='fixed-table-summary'></div>");
        var h = $("<table></table>");
        var d = $("<tbody></tbody>");
        var e = $("<tr></tr>");
        var c = $("<tr></tr>");
        e.append("<td class='title'>笔数统计</td>").append("<td>总笔数：" + i.stat.adjustmentId + "</td>");
        c.append("<td class='title'>金额统计</td>").append("<td>总金额：" + i.stat.amount + "</td>");
        $("#tabMain").parent().parent().parent().append(a.append(h.append(d.append(e).append(c))))
    }
}
;