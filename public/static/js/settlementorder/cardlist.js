var loginNameCode;
$(function() {
    common.getAjax(apiPath + "getbasedata?requireItems=bankCode", function(a) {
        apiPath += "settlementorder/";
        $("#divSearch select[data-field=bankCode]").initSelect(a.result.bankCode, "key", "value", "请选择银行");
        $("#bankCode").initSelect(a.result.bankCode, "key", "value", "请选择银行");
        $("#btnSearch").initSearch(apiPath + "cardsearch", getColumns())
    });
    $("#txtMerchantNo4Edit").bind("input propertychange", function() {
        if (!common.isInt($(this).val(), true)) {
            $(this).val("")
        }
        $("#txtPlatformNo").val($(this).val());
        $("#txtLoginName").val($(this).val() + loginNameCode)
    });
    $("#btnAdd").click(function() {
        showEditModal()
    });
    $("#btnSubmit").click(submit);
    common.initSection()
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
        field: "bankName",
        title: "银行名称",
        align: "center"
    }, {
        field: "accountName",
        title: "开户名",
        align: "center"
    }, {
        field: "accountNo",
        title: "开户帐户",
        align: "center"
    }, {
        field: "province",
        title: "所在省",
        align: "center"
    }, {
        field: "city",
        title: "所在市",
        align: "center"
    }, {
        field: "district",
        title: "所在区/县",
        align: "center",
    }, {
        field: "-",
        title: "操作",
        align: "center",
        formatter: function(b, c, a) {
            return "<a onclick='showEditModal(" + JSON.stringify(c) + ")'>修改</a><a onclick='deleteCard(\"" + c.merchantId + "\", \"" + c.id+ "\")'>删除</a>"
        }
    }]
}
function showEditModal(a) {
    var b = $("#editModal");
    // b.find("div.modal-body").find("input,select").val("").end().find("div.form-add").hide().end().find("div.form-edit").hide();
    if (a == undefined) {
        var title = "新增银行卡";
        $("#bankCode").val('');
        // $("#merchantNo").val('').removeAttr('disabled');
        $("#accountNo").val('');
        $("#accountName").val('');
        $("#province").val('');
        $("#city").val('');
        $("#district").val('');
        $("#cardId").val('');
    } else {
        var title = "修改银行卡";
        // b.find("h4.modal-title").html("修改商户信息").end().find("div.form-edit").show();
        $("#bankCode").val(a.bankCode);
        // $("#merchantNo").val(a.merchantNo).attr("disabled", "disabled");
        $("#accountNo").val(a.accountNo);
        $("#accountName").val(a.accountName);
        $("#province").val(a.province);
        $("#city").val(a.city);
        $("#district").val(a.district);
        $("#cardId").val(a.id);

    }
    b.find("h4.modal-title").html(title);
    b.modal()
}

function deleteCard(b,a) {
    myConfirm.show({
        title: "您确定要删除此银行卡",
        sure_callback: function() {
            common.getAjax(apiPath + "deleteCard" + "?merchantNo=" + b + "&cardId=" + a, function(e) {
                if (e && e.success) {
                    myAlert.success(e.result, undefined, function() {
                        location.href = location.href
                    })
                }else {
                    myAlert.error(e.result, undefined, function () {
                        location.href = location.href
                    })
                }
            })
        }
    })
}

function submit() {
    if ($("#bankCode").val() == "") {
        myAlert.warning($("#bankCode").attr("placeholder"));
        return
    }
    // if ($("#merchantNo").val().length < 8) {
    //     myAlert.warning("商户号最少8位");
    //     return
    // }

    if ($("#accountNo").val() == "") {
        myAlert.warning($("#accountNo").attr("placeholder"));
        return
    }
    if ($("#accountName").val() == "") {
        myAlert.warning($("#accountName").attr("placeholder"));
        return
    }
    if ($("#province").val() == "") {
        myAlert.warning($("#province").attr("placeholder"));
        return
    }
    if ($("#city").val() == "") {
        myAlert.warning($("#city").attr("placeholder"));
        return
    }

    if ($("#district").val() == "") {
        myAlert.warning($("#district").attr("placeholder"));
        return
    }

    var methode = "addMerchantCard";
    if ($("#cardId").val() != "") {
        methode = "updateMerchantCard";
    }

    common.submit(apiPath + methode, "editModal", function() {
        location.href = location.href
    })
}
;