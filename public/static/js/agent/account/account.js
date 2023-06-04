$(function() {
    $("#btnSearch").initSearch(apiPath + "agent/search", getColumns());
    common.getAjax(apiPath + "getbasedata?requireItems=commonStatus,switchType,agentType", function(a) {
        $("#selStatus").initSelect(a.result.commonStatus, "key", "value", "请选择用户状态");
        $("#selStatus2").initSelect(a.result.commonStatus, "key", "value", "请选择用户状态");
        var type='';
        for (var i=0;i<a.result.agentType.length;i++) {
            if(a.result.agentType[i].key=='settleDay'){
                type=a.result.agentType[i].value
            }
        }
        $("#selSettlementType").initSelect(type, "key", "value");
    });
    $("#btnSubmit").click(submit);

    $("#addBtnSubmit").click(addSubmit);

    $("#btnAdd").click(function() {
        showEditModal();
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
        field: "loginName",
        title: "代理账号",
        align: "center"
    }, {
        field: "nickName",
        title: "代理昵称",
        align: "center"
    }, {
            field: "takeBalance",
            title: "余额",
            align: "center",
            formatter: function(b, c, a) {
                return common.fixAmount(b)
            }
        }, {
        field: "balance",
        title: "可提余额",
        align: "center",
        formatter: function(b, c, a) {
            return common.fixAmount(b)
        }
    }, {
        field: "freezeBalance",
        title: "冻结金额",
        align: "center",
        formatter: function(b, c, a) {
            return common.fixAmount(b)
        }
    }, {
        field: "bailBalance",
        title: "保证金",
        align: "center",
        formatter: function(b, c, a) {
            return common.fixAmount(b)
        }
    }, {
        field: "inferisorNum",
        title: "下级商户人数",
        align: "center"
    }, {
        field: "settleAccWay",
        title: "结算方式",
        align: "center"
    }, {
        field: "settleAccRatio",
        title: "结算比例百分比",
        align: "center"
    }, {
        field: "statusDesc",
        title: "代理账号状态",
        align: "center",
        cellStyle:function(b, c, a){
            return c.statusDesc!='正常'?{css:{"color":"red"}}:'';
        }
    }, {
        field: "loginIP",
        title: "最后登陆IP",
        align: "center"
    }, {
        field: "loginDate",
        title: "最近登录时间",
        align: "center"
    }, {
        field: "created_at",
        title: "账号创建时间",
        align: "center"
    }, {
        field: "-",
        title: "操作",
        align: "center",
        formatter: function(b, c, a) {
            var statusStr=c.statusDesc=="正常" ? "禁用":"启用";
            str = "<a onclick='showEditMoney(" + JSON.stringify(c) + ")'>资金管理</a>" +
                "<a onclick='resetPwd(\"" + c.id + "\", true)'>重置登录密码</a>" +
                "<a onclick='resetPwd(\"" + c.id + "\", false)'>重置支付密码</a>"+
                "<a onclick='updateStatus(\"" + c.id + "\",\""+c.status+"\")'>"+statusStr+"</a>"+
                "<a onclick='showEditModal(" + JSON.stringify(c) + ")'>编辑</a>"
            return str

        }
    }]
}

function showEditMoney(a) {
    $("#txtId").val(a.id);
    $("#editModal").modal()
}

function showEditModal(data) {
    $("#selSettlementType").val('D0');//默认选中T1结算方式
    if(data){
        $("#txtloginName").val(data.loginName).attr("disabled", "disabled");
        $('#getHide').hide();
        $("#settleAccRatio").val(data.settleAccRatio);
        $("#selStatus2").val(data.status);
        $("#txtId").val(data.id);
        $("#txtNickName").val(data.nickName);
    }

    $("#addModal").modal()
}

function submit() {
    if ($("#txtMoney").val() == "") {
        myAlert.warning($("#txtMoney").attr("placeholder"));
        return
    }
    var  type=$('input:radio:checked').val();
    $('#type').val(type);
    common.submit(apiPath + "agent/editMoney", "editModal", function() {
        location.href = location.href
    })
}

function resetPwd(b, a) {
    var c = a ? "登录密码" : "支付密码";
    var d = a ? "resetloginpwd" : "resetsecurepwd";
    myConfirm.show({
        title: "您确定要重置" + c,
        sure_callback: function() {
            common.getAjax(apiPath + "agent/updatePwd?userId=" + b+"&type="+d, function(e) {
                if (e && e.success) {
                    myAlert.success("操作成功，新" + c + "：" + e.result.newPwd , undefined, function() {
                        location.href = location.href
                    })

                } else {
                    myAlert.error(e.result);
                }
            })
        }
    })
}

function updateStatus(id,status) {
    myConfirm.show({
        title: "您确定?",
        sure_callback: function() {
            common.getAjax(apiPath + "agent/editAccount?id=" + id+"&type=updStatus&status="+status, function(e) {
                if (e && e.success) {
                    myAlert.success("操作成功" ,undefined, function() {
                        location.href = location.href
                    })
                } else {
                    myAlert.error(e.result);
                }
            })
        }
    })
}

function addSubmit() {
    if ($("#txtLoginPwd").val() != $("#txtTrueLoginPwd").val()) {
        myAlert.warning('两次输入登录密码不一致！');
        return
    }

    if ($("#txtSecurePwd").val() != $("#txtTrueSecurePwd").val()) {
        myAlert.warning('两次输入支付密码不一致！');
        return
    }

    var id=$("#txtId").val();
    if(id){
        common.submit(apiPath + "agent/editAccount?id="+id, "addModal", function() {
            location.href = location.href
        })
    }else{
        common.submit(apiPath + "agent/addAccount", "addModal", function() {
            location.href = location.href
        })
    }


}