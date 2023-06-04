$(function() {
    common.getAjax(apiPath + "getquickDefined?requireItems=blackUserSettlementType", function(a) {
        apiPath += "manager/";
        $("#divSearch select[data-field=blackUserType]").initSelect(a.result.blackUserSettlementType, "key", "value", "请选择代付方式");
        $("#selBlackUserType").initSelect(a.result.blackUserSettlementType, "key", "value", "请选择代付方式");
        $("#selBlackUserStatus").initSelect(a.result.blackUserSettlementStatus, "key", "value", "请选择状态");
        $("#btnSearch").initSearch(apiPath + "blackUserSettlement", getColumns());
    });

    $("#btnAdd").click(function() {
        showEditModal();
    });

    $("#btnSubmit").click(submit);
    common.initSection(true)
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
        field: "blackUserName",
        title: "用户名",
        align: "center"
    }, {
        field: "blackUserAccount",
        title: "账号",
        align: "center"
    }, {
        field: "blackUserStatus",
        title: "状态",
        align: "center",
        formatter: function(b, c, a) {
            if(b == 'enable'){
                return '启用';
            } else {
                return '禁用';
            }
        }
    }, {
        field: "blackUserType",
        title: "用户代付方式",
        align: "center",
        formatter: function(b, c, a) {
            if(b == 'ALIPAY'){
                return '支付宝';
            }else {
                return '银行卡';
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
        formatter: function(b, c, a) {
            return "<a onclick='showEditModal(" + JSON.stringify(c) +  ")'>修改</a><a id=\"delBlackUser\" onclick='delblackUserSettlement(\"" + c.blackUserId + "\")'>删除</a>"
        }
    }]
}

function showEditModal(a) {
    var b = $("#editModal");
    b.find("div.modal-body").find("input,select").val("").end().find("div.form-add").hide().end().find("div.form-edit").hide();
    if (a == undefined) {

        b.find("h4.modal-title").html("添加黑名单用户").end().find("div.form-add").show();
        $("#selBlackUserType").val('EBANK');
        $("#selBlackUserStatus").val('enable');
        b.modal()

    } else {
        b.find("h4.modal-title").html("修改黑名单").end().find("div.form-edit").show();

        $("#blackUserId").val(a.blackUserId);
        $("#blackUserName").val(a.blackUserName);
        $("#blackUserAccount").val(a.blackUserAccount);
        $("#selBlackUserType").val(a.blackUserType);
        $("#selBlackUserStatus").val(a.blackUserStatus);
        b.modal()
    }
}

function submit() {

    var a;
    if ($("#blackUserId").val() == "") {
        a = "blackUserSettlement/create";

        if ($("#blackUserName").val() == "" && $("#blackUserAccount").val() == "") {
            myAlert.warning("用户名或账号至少填写一项！");
            return
        }

        if ($("#selBlackUserType").val() == "") {
            myAlert.warning($("#selBlackUserType").find("option:first").html());
            return
        }
        if ($("#selStatus").val() == "") {
            myAlert.warning($("#selStatus").find("option:first").html());
            return
        }

    } else {
        a = "blackUserSettlement/update";

        if ($("#blackUserId").val() == "") {
            myAlert.warning("用户id缺失");
            return
        }

        if ($("#blackUserName").val() == "" && $("#blackUserAccount").val() == "") {
            myAlert.warning("用户名或账号至少填写一项！");
            return
        }

        if ($("#selBlackUserType").val() == "") {
            myAlert.warning($("#selBlackUserType").find("option:first").html());
            return
        }
        if ($("#selStatus").val() == "") {
            myAlert.warning($("#selStatus").find("option:first").html());
            return
        }
    }
    
    common.submit(apiPath + a, "editModal", function(res) {  
        location.href = location.href
    })
}


function delblackUserSettlement(id){
    myConfirm.show({
        title: "确定删除？",
        sure_callback: function() {
            $.ajax({
                url: apiPath + "blackUserSettlement/delete",
                data: {
                    blackUserId: id,
                },
                async: true,   
                cache: false, 
                type: "get",
                dataType: "json",  
                success: function(result){
                    if(result){
                        location.href = location.href
                    }else{
                        myAlert.error("操作失败")
                    }
                }
              });
        }
    })
}