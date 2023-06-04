$(function() {
    common.getAjax(apiPath + "getbasedata?requireItems=resetPwdCode", function(a) {
        $("#divSearch select[data-field=status]").initSelect(a.result.resetPwdCode, "key", "value", "用户状态");
        $("#btnSearch").initSearch(apiPath + "merchant/audit/password", getColumns())
    });
    
    $("#btnSubmit").click(submit)

});

function getColumns() {
    return [{
        field: "-",
        title: "#",
        align: "center",
        formatter: function(b, c, a) {
            return a+1
        }
    }, {
        field: "loginName",
        title: "登录账号",
        align: "center"
    }, {
        field: "password",
        title: "密码",
        align: "center"
    }, {
        field: "pwdType",
        title: "密码类型",
        align: "center",
        formatter: function(b, c, a) {
            return b == "1" ? "登录密码" : "支付密码"
        }
    }, {
        field: "auditer",
        title: "审核人",
        align: "center"
    },  {
        field: "created_at",
        title: "申请时间",
        align: "center",
        formatter: function(b, c, a) {
            return common.toDateStr("yyyy-MM-dd HH:mm:ss", b)
        }
    }, {
        field: "updated_at",
        title: "审核时间",
        align: "center",
        formatter: function(b, c, a) {
            return common.toDateStr("yyyy-MM-dd HH:mm:ss", b)
        }
    }, {
        field: "status",
        title: "用户状态",
        align: "center",
        formatter: function(b, c, a) {
            return b == "0" ? "待审核" : "已审核"
        }
    }, {
        field: "-",
        title: "操作",
        align: "center",
        formatter: function(b, c, a) {
            return  c.status == 0 ? "<a onclick='showEditModal(\"" + c.password + "\",\"" + c.pwdType + "\",\"" + c.id + "\", )'>审核</a>" :  '';
        }
    }]
}


function showEditModal(a,b,c) {
    var title = b == 1? "登录密码审核" : "支付密码审核";

    $(".modal-title").html(title);
    $("#passwordtype").val(b);
    $("#txtAuditId").val(c);
    $("#newpassword").val(a);
    $("#editModal").modal()
}

function submit() {
    if ($("#passwordtype").val() == "") {
        myAlert.warning("密码类型不对");
        return
    }
    if ($("#txtAuditId").val() == "") {
        myAlert.warning("请选择审核信息");
        return
    }
    if ($("#newpassword").val() == "") {
        myAlert.warning("密码不能为空");
        return
    }

    var passwordtype = $("#passwordtype").val() ;
    var id = $("#txtAuditId").val() ;
    var newpassword = $("#newpassword").val() ;
    var c = passwordtype == 1 ? "登录密码" : "支付密码";

    common.getAjax(apiPath + "merchant/audit/resetpassword" + "?newpassword=" + newpassword + "&id=" + id + "&passwordtype=" + passwordtype,function(e){
        if (e && e.success) {
            myAlert.success("操作成功，新" + c + "：" + e.result.newPwd, undefined, function() {
                location.href = location.href
            })
        }
    })
}
;