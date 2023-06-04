$(function() {

    common.getAjax(apiPath + "getbasedata?requireItems=psqlBankCode", function(a) {
        $("#selBankCode").initSelect(a.result.psqlBankCode, "key", "value", "请选择收款银行")
    });

    common.getAjax(apiPath + "rechargeorder/choosechannel", function(a) {
        $("#selSetId").initSelect(a.result.channel, "key", "value", "请选充值渠道")
    });

    $("#txtOrderAmount").bind("input propertychange", function() {
        if (!common.isDecimal($(this).val())) {
            $(this).val("")
        }
    });

    $("#btnConfirm").click(showConfirmModal);
    $("#btnSubmit").click(submit)
    $("#copyLink").click(copyLink);
    $("#openLink").click(openLink);
});


function showConfirmModal() {
    if ($("#txtOrderAmount").val() == "") {
        myAlert.warning($("#txtOrderAmount").attr("placeholder"));
        return
    }


    if ($("#selSetId").val() == "") {
        myAlert.warning($("#selSetId").find("option:first").html());
        return
    }

    if ($("#txtBankName").val() == "") {
        myAlert.warning($("#txtBankName").attr("placeholder"));
        return
    }
    if ($("#txtOrderReason").val() == "") {
        myAlert.warning($("#txtOrderReason").attr("placeholder"));
        return
    }
    $("#spanOrderAmount").html($("#txtOrderAmount").val());
    $("#spanChannel").html($("#selSetId option:selected").html());
    $("#spanBankCode").html($("#selBankCode option:selected").html());

    $("#spanOrderReason").html($("#txtOrderReason").val());
    $("#txtApplyPerson").val("");
    $("#confirmModal").modal()
}
function submit() {
    if ($("#txtApplyPerson").val() == "") {
        myAlert.warning($("#txtApplyPerson").attr("placeholder"));
        return
    }

    myConfirm.show({
        title: "确定要发起充值？",
        sure_callback: function() {
            // common.submit(apiPath + "rechargeorder/create" , "divContainer", function() {
            //     window.location.href = contextPath + "rechargeorder"
            // })
            common.getAjax(common.perfectUrl(apiPath + "rechargeorder/create" , "divContainer"), function(d) {
                if (d.success) {

                    $("#confirmModal").modal('hide');
                    $("#rechargeAddress").text(d.payUrl);
                    $("#resultModal").modal();
                    // myAlert.success("操作成功！", undefined, function() {
                    //     // window.open(d.payUrl);
                    //     // location.reload()
                    // })
                } else {
                    myAlert.error(d.result.length > 0 ? d.result : "操作异常")
                }
            }, function(d) {
                myAlert.error("操作失败")
            })
        }
    })
}
function copyLink() {
    // window.clipboardData.setData('Text',document.getElementById('Fcode'+key).innerHTML);
    var ob = "rechargeAddress";
    console.log(ob);
    const range = document.createRange();
    range.selectNode(document.getElementById(ob));
    const selection = window.getSelection();
    if(selection.rangeCount > 0) selection.removeAllRanges();
    selection.addRange(range);
    document.execCommand('copy');
    alert("已复制好，可贴粘。");
}
function openLink() {
    $("#resultModal").modal('hide');
    window.open(document.getElementById('rechargeAddress').innerHTML);
    location.reload();
}
;