$(function() {
    common.getAjax(apiPath + "getbasedata?requireItems=merchantUserLevel,merchantUserStatus", function(a) {
        $("#selLevel").initSelect(a.result.merchantUserLevel, "key", "value", "用户权限级别");
        $("#divSearch select[data-field=userLevel]").initSelect(a.result.merchantUserLevel, "key", "value", "用户权限级别");
        $("#divSearch select[data-field=status]").initSelect(a.result.merchantUserStatus, "key", "value", "用户状态");
        $("#selStatus").initSelect(a.result.merchantUserStatus, "key", "value", "请选择用户状态");
        $("#btnSearch").initSearch(apiPath + "merchant/user/search", getColumns())
    });
    $("#btnSubmit").click(submit)
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
        field: "loginName",
        title: "登录账号",
        align: "center"
    }, {
        field: "userName",
        title: "用户名称",
        align: "center"
    }, {
        field: "platformType",
        title: "用户级别",
        align: "center",
        formatter: function(b, c, a) {
            return b == "Normal" ? "商户管理员" : "代理管理员"
        }
    }, {
        field: "userLevel",
        title: "商户权限级别",
        align: "center",
        formatter: function(b, c, a) {
            return b == "MerchantManager" ? "商户管理员" : "充值管理员"
        }
    }, {
        field: "merchantNo",
        title: "所属商户",
        align: "center"
    }, {
        field: "platformNo",
        title: "所属平台",
        align: "center"
    }, {
        field: "loginFailNum",
        title: "连续登录失败次数",
        align: "center"
    }, {
        field: "loginPwdAlterTime",
        title: "最后密码修改时间",
        align: "center",
        formatter: function(b, c, a) {
            return common.toDateStr("yyyy-MM-dd HH:mm:ss", b)
        }
    }, {
        field: "latestLoginTime",
        title: "最后登录时间",
        align: "center",
        formatter: function(b, c, a) {
            return common.toDateStr("yyyy-MM-dd HH:mm:ss", b)
        }
    }, {
        field: "statusDesc",
        title: "用户状态",
        align: "center"
    }, {
        field: "-",
        title: "操作",
        align: "center",
        formatter: function(b, c, a) {
            str = "<a onclick='showEditModal(" + JSON.stringify(c) + ")'>修改</a>" +
                "<a onclick='resetPwd(\"" + c.userId + "\", true)'>重置登录密码</a>" +
                "<a onclick='resetPwd(\"" + c.userId + "\", false)'>重置支付密码</a>"
            if(c.googleAuthSecretKey != '')
                str = str + "<a onclick='googleAuthSecretKey(\"" + c.userId + "\")'>关闭登陆谷歌验证</a>"
            return str

        }
    }]
}
function googleAuthSecretKey(b) {
    myConfirm.show({
        title: "确定关闭该商户的登陆谷歌验证!",
        sure_callback: function() {
            common.getAjax(apiPath + "merchant/user/googleAuthSecretKey?userId=" + b, function(e) {
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
function resetPwd(b, a) {
    var c = a ? "登录密码" : "支付密码";
    var d = a ? "resetloginpwd" : "resetsecurepwd";
    myConfirm.show({
        title: "您确定要重置" + c,
        sure_callback: function() {
            common.getAjax(apiPath + "merchant/user/" + d + "?userId=" + b, function(e) {
                if (e && e.success) {
                    myAlert.success("操作成功，新" + c + "：" + e.result.newPwd + "审核后即可用", undefined, function() {
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
    $("#txtUserId").val(a.userId);
    $("#txtLoginName").val(a.loginName);
    $("#txtUserName").val(a.userName);
    $("#selStatus").val(a.status);
    $("#selLevel").val(a.userLevel);
    $("#editModal").modal()
}
function submit() {
    if ($("#txtLoginName").val() == "") {
        myAlert.warning($("#txtLoginName").attr("placeholder"));
        return
    }
    if ($("#txtUserName").val() == "") {
        myAlert.warning($("#txtUserName").attr("placeholder"));
        return
    }
    if ($("#selStatus").val() == "") {
        myAlert.warning($("#selStatus").find("option:first").html());
        return
    }
    if ($("#selStatus").val() == "Exception") {
        myAlert.warning("商户状态不能选择[异常]");
        return
    }
    common.submit(apiPath + "merchant/user/update", "editModal", function() {
        location.href = location.href
    })
}
;