$(function () {
    common.getAjax(apiPath + "getbasedata?requireItems=systemAccountRoleCode,systemAccountstatusCode", function (a) {
        apiPath += "manager/";
        /* $("#divSearch select[data-field=status]").initSelect(a.result.commonStatus, "key", "value", "商户状态"); */
        $("#txtRole").initSelect(a.result.systemAccountRoleCode, "key", "value", "请选择管理员角色");
        $("#selStatus").initSelect(a.result.systemAccountstatusCode, "key", "value", "请选择管理员状态");
        $("#btnSearch").initSearch(apiPath + "/getmanagerlist", getColumns());
    });
    $("#btnSubmit").click(submit);

    //删除时的谷歌验证码验证
    $("#btnAuthCodeSubmit").click(delAccount);
    common.initSection(true)
});

function getColumns() {
    return [{
        field: "-",
        title: "#",
        align: "center",
        formatter: function (b, c, a) {
            return a + 1
        }
    }, {
        field: "userName",
        title: "用户名称",
        align: "center"
    }, {
        field: "loginName",
        title: "登录账号",
        align: "center"
    }, {
        field: "googleBind",
        title: "是否绑定谷歌验证码",
        align: "center"
    }, {
        field: "status",
        title: "状态",
        align: "center",
        formatter: function (b, c, a) {
            if (b == 'Normal') {
                return '正常';
            } else {
                return '封号';
            }
        }
    }, {
        field: "role",
        title: "角色",
        align: "center",
        formatter: function (b, c, a) {
            if (b == '1') {
                return '客服';
            } else if (b == '2') {
                return '财务';
            } else if (b == '3') {
                return '运维';
            } else if (b == '4') {
                return '主管';
            } else if (b == '5') {
                return '管理员';
            } else if (b == '12') {
                return '财务(精简版)';
            }
        }
    }, {
        field: "created_at",
        title: "创建时间",
        align: "center",
    }, {
        field: "-",
        title: "操作",
        align: "center",
        formatter: function (b, c, a) {
            return "<a onclick='showEditModal(" + JSON.stringify(c) + ")'>修改</a><a id=\"accountId\" onclick='delaccountShow(\"" + c.id + "\")'>删除</a>"
        }
    }]
}

function showEditModal(a) {
    var b = $("#editModal");
    b.find("div.modal-body").find("input,select").val("").end().find("div.form-add").hide().end().find("div.form-edit").hide();
    if (a == undefined) {
        common.getAjax(apiPath + "getnextmerchantno", function (c) {
            if (c.success) {
                b.find("h4.modal-title").html("增加管理员").end().find("div.form-add").show();
                loginNameCode = c.result.loginNameCode;
                $("#txtMerchantNo4Edit").val(c.result.merchantNo).removeAttr("disabled");
                $("#txtPlatformNo").val(c.result.merchantNo);
                $("#txtLoginName").val(c.result.merchantNo + loginNameCode);
                b.modal()
            } else {
                myAlert.error("可用商户号获取失败")
            }
        })
    } else {
        b.find("h4.modal-title").html("修改管理员").end().find("div.form-edit").show();
        $("#acctondId").val(a.id);
        $("#txtRole").val(a.role);
        $("#selStatus").val(a.status);
        b.modal()
    }
}

function submit() {
    if ($("#txtMerchantNo4Edit").val() == "") {
        myAlert.warning($("#txtMerchantNo4Edit").attr("placeholder"));
        return
    }

    if ($("#txtRole").val() == "") {
        myAlert.warning($("#txtRole").attr("placeholder"));
        return
    }

    if ($("#selStatus").val() == "") {
        myAlert.warning($("#selStatus").attr("placeholder"));
        return
    }
    var a;
    if ($("#acctondId").val() == "") {
        a = "insert";
        if ($("#txtDescription").val() == "") {
            myAlert.warning($("#txtDescription").attr("placeholder"));
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
        if ($("#txtUserName").val() == "") {
            myAlert.warning($("#txtUserName").attr("placeholder"));
            return
        }
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

        if ($("#googleAuthCode").val() == "") {
            document.getElementById('btnSubmit').disabled = false;
            myAlert.warning($("#googleAuthCode").attr("placeholder"));
            return
        }
    }


    common.submit(apiPath + a, "editModal", function (res) {
        location.href = location.href
    })
}


function delaccountShow(id) {
    $("#id").val(id);
    $("#editGoogleModal").modal();

}


function delAccount() {
    var code = $("#authCode").val();
    var id = $("#id").val();

    if ($("#authCode").val() == "") {
        myAlert.warning($("#authCode").attr("placeholder"));
        return
    }


    myConfirm.show({
        title: "确定删除？",
        sure_callback: function () {
            $.ajax({
                url: apiPath + "delaccount",
                data: {
                    id: id,
                    googleAuth: code
                },
                async: true,
                cache: false,
                type: "GET",
                dataType: "json",
                success: function (result) {
                    if (result.success) {
                        location.href = location.href
                    } else {
                        myAlert.error(result.result)
                    }
                }
            });
        }
    })
}