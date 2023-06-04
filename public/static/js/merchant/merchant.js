var loginNameCode;
$(function() {
    common.getAjax(apiPath + "getbasedata?requireItems=commonStatus,switchType,settlementType", function(a) {
        apiPath += "merchant/";
        $("#divSearch select[data-field=status]").initSelect(a.result.commonStatus, "key", "value", "商户状态");
        $("#selStatus").initSelect(a.result.commonStatus, "key", "value", "请选择商户状态");
        $(".v-form-open").initSelect(a.result.switchType, "key", "value", "请选择");
        $("#selSettlementType,#selSetSettlementType").initSelect(a.result.settlementType, "key", "value", "请选择商户结算方式");
        $("#btnSearch").initSearch(apiPath + "search", getColumns(),{
            success_callback: buildSummary
        });
    });
    $("#txtMerchantNo4Edit").bind("input propertychange", function() {
        if (!common.isInt($(this).val(), true)) {
            $(this).val("")
        }
        $("#txtPlatformNo").val($(this).val());
        $("#txtLoginName").val($(this).val() + loginNameCode);
    });
    $("#btnAdd").click(function() {
        showEditModal();
    });
    $("#btnSubmit").click(submit);
    common.initSection(false);
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
        sortable:true,
        align: "center"
    }, {
        field: "shortName",
        title: "商户简称",
        align: "center"
    }, {
        field: "fullName",
        title: "商户全称",
        align: "center"
    }, {
        field: "platformNo",
        title: "所属平台",
        align: "center"
    }, {
        field: "loginName",
        title: "所属代理",
        align: "center"
    }, {
        field: "statusDesc",
        title: "商户状态",
        align: "center"
    }, {
        field: "settlementAmount",
        title: "余额",
        align: "center",
        sortable:true,
        fomatter: function(b, c, a){
            b = parseFloat(b)
            return b.toFixed(2)
        }
    },{
        field: "insTime",
        title: "开户时间",
        align: "center",
        formatter: function(b, c, a) {
            return common.toDateStr("yyyy-MM-dd HH:mm:ss", b)
        }
    }, {
        field: "-",
        title: "操作",
        align: "center",
        formatter: function(b, c, a) {
            return "<a onclick='showEditModal(" + JSON.stringify(c) + ")'>修改</a><a onclick='showSetModal(\"" + c.merchantNo + "\")'>权限配置</a>"
        }
    }]
}
function showEditModal(a) {
    var b = $("#editModal");
    b.find("div.modal-body").find("input,select").val("").end().find("div.form-add").hide().end().find("div.form-edit").hide();
    if (a == undefined) {
        common.getAjax(apiPath + "getnextmerchantno", function(c) {
            if (c.success) {
                b.find("h4.modal-title").html("增加商户信息").end().find("div.form-add").show();
                loginNameCode = c.result.loginNameCode;
                $("#txtMerchantNo4Edit").val(c.result.merchantNo).removeAttr("disabled");
                $("#txtPlatformNo").val(c.result.merchantNo);
                $("#txtLoginName").val(c.result.merchantNo + loginNameCode);
                $("#selSettlementType").val('T1');//默认选中T1结算方式
                b.modal();
            } else {
                myAlert.error("可用商户号获取失败");
            }
        })
    } else {
        b.find("h4.modal-title").html("修改商户信息").end().find("div.form-edit").show();
        $("#txtMerchantId4Edit").val(a.merchantId);
        $("#txtMerchantNo4Edit").val(a.merchantNo).attr("disabled", "disabled");
        $("#txtShortName").val(a.shortName);
        $("#txtFullName").val(a.fullName);
        $("#selStatus").val(a.status);

        $("#txtAgentLoginName").val(a.loginName);

        // $("#divLoginName").hide();

        b.modal();
    }
}
function submit() {
    if ($("#txtMerchantNo4Edit").val() == "") {
        myAlert.warning($("#txtMerchantNo4Edit").attr("placeholder"));
        return
    }
    if ($("#txtMerchantNo4Edit").val().length < 8) {
        myAlert.warning("商户号最少8位");
        return
    }
    if ($("#txtShortName").val() == "") {
        myAlert.warning($("#txtShortName").attr("placeholder"));
        return
    }
    if ($("#txtFullName").val() == "") {
        myAlert.warning($("#txtFullName").attr("placeholder"));
        return
    }
    var a;
    if ($("#txtMerchantId4Edit").val() == "") {
        a = "insert";
        if ($("#txtDescription").val() == "") {
            myAlert.warning($("#txtDescription").attr("placeholder"));
            return
        }

        if ($("#selSettlementType").val() == "") {
            myAlert.warning($("#selSettlementType").find("option:first").html());
            return
        }

        if ($("#txtLoginName").val() == "") {
            myAlert.warning($("#txtLoginName").attr("placeholder"));
            return
        }
        if ($("#txtLoginPwd").val() == "") {
            myAlert.warning($("#txtLoginPwd").attr("placeholder"));
            return
        }
        if ($("#txtLoginPwd").val().length < 6) {
            myAlert.warning("登陆密码最少6位");
            return
        }
        // if ($("#txtUserName").val() == "") {
        //     myAlert.warning($("#txtUserName").attr("placeholder"));
        //     return
        // }
        if ($("#txtSecurePwd").val() == "") {
            myAlert.warning($("#txtSecurePwd").attr("placeholder"));
            return
        }
        if ($("#txtSecurePwd").val().length < 6) {
            myAlert.warning("支付密码最少6位");
            return
        }
    } else {
        a = "update";
        if ($("#selStatus").val() == "") {
            myAlert.warning($("#selStatus").find("option:first").html());
            return
        }
    }
    common.submit(apiPath + a, "editModal", function() {
        location.href = location.href
    })
};

function buildSummary(k) {
    if (k && k.success && k.rows.length > 0) {
        var b = k.stat;

        var a = $("<div class='fixed-table-summary'></div>");
        var j = $("<table></table>");
        var e = $("<tbody></tbody>");
        var d = $("<tr></tr>");
        var c = $("<tr></tr>");
        d.append("<td class='title'>金额统计</td>").append("<td>全部商户余额：" + b.totalAmount + "</td>");

        d.append("<td class='item'>" + "当前商户余额：" + b.currentAmount + "</td>");

        $("#tabMain").parent().parent().parent().append(a.append(j.append(e.append(d).append(c))));

    }
}