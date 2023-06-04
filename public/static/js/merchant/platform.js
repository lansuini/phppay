$(function() {
    common.getAjax(apiPath + "getbasedata?requireItems=platformType,commonStatus,openType", function(a) {
        apiPath += "merchant/platform/";
        $("#divSearch select[data-field=type]").initSelect(a.result.platformType, "key", "value", "平台类型");
        $("#divSearch select[data-field=status]").initSelect(a.result.commonStatus, "key", "value", "平台状态");
        $("#selStatus").initSelect(a.result.commonStatus, "key", "value", "请选择平台状态");
        $("#selOpenCheckAccount").initSelect(a.result.openType, "key", "value", "请选择对账开关");
        $("#selOpenCheckDomain").initSelect(a.result.openType, "key", "value", "请选择域名验证开关");
        $("#selOpenFrontNotice").initSelect(a.result.openType, "key", "value", "请选择前台通知开关");
        $("#selOpenBackNotice").initSelect(a.result.openType, "key", "value", "请选择后台通知开关");
        $("#selOpenRepayNotice").initSelect(a.result.openType, "key", "value", "请选择后台通知开关"); 
        $("#selOpenManualSettlement").initSelect(a.result.openType, "key", "value", "请选择后台通知开关");
        $("#btnSearch").initSearch(apiPath + "search", getColumns());
    });
    $("#btnResetSignKey").click(resetSignKey);
    $("#btnSubmit").click(submit);
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
        field: "platformNo",
        title: "平台代码",
        align: "center"
    }, {
        field: "description",
        title: "平台描述",
        align: "center"
    }, {
        field: "typeDesc",
        title: "平台类型",
        align: "center"
    }, {
        field: "statusDesc",
        title: "平台状态",
        align: "center"
    }, {
        field: "openCheckAccount",
        title: "对账开关",
        align: "center",
        formatter: function(b, c, a) {
            return b ? "开通" : "关闭"
        }
    }, {
        field: "openCheckDomain",
        title: "域名验证开关",
        align: "center",
        formatter: function(b, c, a) {
            return b ? "开通" : "关闭"
        }
    }, {
        field: "openFrontNotice",
        title: "前台通知开关",
        align: "center",
        formatter: function(b, c, a) {
            return b ? "开通" : "关闭"
        }
    }, {
        field: "-",
        title: "后台通知开关",
        align: "center",
        formatter: function(b, c, a) {
            return (c.openBackNotice ? "开通" : "关闭") + " <a title='失败时最大尝试重复通知次数'>(" + c.backNoticeMaxNum + ")</a>"
        }
    },{
        field: "openRepayNotice",
        title: "代付通知开关",
        align: "center",
        formatter: function(b, c, a) {
            return b ? "开通" : "关闭"
        }
    },{
        field: "openManualSettlement",
        title: "手动代付开关",
        align: "center",
        formatter: function(b, c, a) {
            return b ? "开通" : "关闭"
        }
    }, {
        field: "-",
        title: "操作",
        align: "center",
        formatter: function(b, c, a) {
            return "<a onclick='showEditModal(\"" + c.platformNo + "\")'>修改</a><a onclick='showSignKeyModal(\"" + c.platformNo + "\")'>查看加密Key</a>"
        }
    }]
}
function showSignKeyModal(a) {
    common.getAjax(apiPath + "getsignkey?platformNo=" + a, function(b) {
        if (b.success && b.result.signKey && b.result.signKey.length > 0) {
            $("#spanPlatformNo").html(a);
            $("#spanSignKey").html(b.result.signKey);
            $("#signKeyModal").modal()
        } else {
            myAlert.error("查询失败，请重试")
        }
    });
}
function resetSignKey() {
    myConfirm.show({
        title: "您确定要重置加密Key？",
        sure_callback: function() {
            common.getAjax(apiPath + "resetsignkey?platformNo=" + $("#spanPlatformNo").html(), function(a) {
                if (a.success && a.result.signKey && a.result.signKey.length > 0) {
                    myAlert.success("操作成功");
                    $("#spanSignKey").html(a.result.signKey)
                } else {
                    myAlert.error("操作异常")
                }
            })
        }
    })
}
function showEditModal(a) {
    common.getAjax(apiPath + "detail?platformNo=" + a, function(b) {
        if (b.success) {
            $("#txtPlatformNo").val(a);
            $("#txtPlatformId").val(b.result.platformId);
            $("#txtDescription").val(b.result.description);
            $("#txtType").val(b.result.typeDesc);
            $("#selStatus").val(b.result.status);
            $("#selOpenCheckAccount").val(b.result.openCheckAccount ? "1" : "0");
            $("#selOpenCheckDomain").val(b.result.openCheckDomain ? "1" : "0");
            $("#selOpenFrontNotice").val(b.result.openFrontNotice ? "1" : "0");
            $("#selOpenBackNotice").val(b.result.openBackNotice ? "1" : "0");
            $("#selOpenRepayNotice").val(b.result.openRepayNotice ? "1" : "0");
            $("#selOpenManualSettlement").val(b.result.openManualSettlement ? "1" : "0");
            $("#txtDomains").val(b.result.domains.join("\n"));
            $("#txtIpwhite").val(b.result.ipWhite);
            $("#txtLoginIpWhite").val(b.result.loginIpWhite);
            $("#editModal").modal()
        } else {
            myAlert.error("查询失败，请重试");
        }
    });
}
function submit() {
    if ($("#txtDescription").val() == "") {
        myAlert.warning($("#txtDescription").attr("placeholder"));
        return false
    }
    if ($("#selStatus").val() == "") {
        myAlert.warning($("#selStatus").find("option:first").html());
        return false
    }
    if ($("#selOpenCheckAccount").val() == "") {
        myAlert.warning($("#selOpenCheckAccount").find("option:first").html());
        return false
    }
    if ($("#selOpenCheckDomain").val() == "") {
        myAlert.warning($("#selOpenCheckDomain").find("option:first").html());
        return false
    }
    if ($("#selOpenFrontNotice").val() == "") {
        myAlert.warning($("#selOpenFrontNotice").find("option:first").html());
        return false
    }
    if ($("#selOpenBackNotice").val() == "") {
        myAlert.warning($("#selOpenBackNotice").find("option:first").html());
        return false
    }
    if ($("#selOpenRepayNotice").val() == "") {
        myAlert.warning($("#selOpenRepayNotice").find("option:first").html());
        return false
    }
    if ($("#selOpenManualSettlement").val() == "") {
        myAlert.warning($("#selOpenManualSettlement").find("option:first").html());
        return false
    }
    common.submit(apiPath + "update?domains=" + encodeURI($("#txtDomains").val().replace(/\n/g, "|")), "editModal", function() {
        $("#editModal").modal("hide");
        $("#btnSearch").click();
    });
}