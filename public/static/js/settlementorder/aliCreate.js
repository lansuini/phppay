$(function() {
    $("#txtOrderAmount").bind("input propertychange", function() {
        if (!common.isDecimal($(this).val())) {
            $(this).val("")
        }
    });
    $("#btnConfirm").click(showConfirmModal);
    $("#btnSubmit").click(submit)
});

function showConfirmModal() {
    if ($("#txtOrderAmount").val() == "") {
        myAlert.warning($("#txtOrderAmount").attr("placeholder"));
        return
    }

    if ($("#txtAliAccountNo").val() == "") {
        myAlert.warning($("#txtAliAccountNo").attr("placeholder"));
        return
    }
    if ($("#txtAliAccountNo2").val() == "") {
        myAlert.warning($("#txtAliAccountNo2").attr("placeholder"));
        return
    }
    if ($("#txtAliAccountNo").val() != $("#txtAliAccountNo2").val()) {
        myAlert.warning("两次输入的收款账号不一致，请重新输入");
        return
    }
    if ($("#accountName").val() == "") {
        myAlert.warning($("#accountName").attr("placeholder"));
        return
    }
    if ($("#txtOrderReason").val() == "") {
        myAlert.warning($("#txtOrderReason").attr("placeholder"));
        return
    }
    $("#spanOrderAmount").html($("#txtOrderAmount").val());
    $("#spanAliAccountNo").html($("#txtAliAccountNo").val());
    $("#spanaliAccountName").html($("#txtAliAccountName").val());
    $("#spanOrderReason").html($("#txtOrderReason").val());
    $("#txtApplyPerson").val("");
    $("#confirmModal").modal()
}
function submit() {
    document.getElementById('btnSubmit').disabled=true;
    if ($("#txtApplyPerson").val() == "") {
        document.getElementById('btnSubmit').disabled=false;
        myAlert.warning($("#txtApplyPerson").attr("placeholder"));
        return
    }
    if ($("#forGoogleAuth").css("display") == 'block'&& $("#googleAuth").val() == "") {
        document.getElementById('btnSubmit').disabled=false;
        myAlert.warning($("#googleAuth").attr("placeholder"));
        return
    }


    myConfirm.show({
        title: "确定要发起代付？",
        sure_callback: function() {

            common.getAjax(common.perfectUrl(apiPath + "settlementorder/aliSettlement", "divContainer"), function(d) {

                if(d.success === 1){

                    window.location.href = contextPath + "settlementorder";

                } else if (d.success === 2) {

                    myAlert.error(d.result);
                    $("#forGoogleAuth").css("display","block");
                    $("#googleAuth").attr("type","text");
                    document.getElementById('btnSubmit').disabled=false;

                }else if(d.success === 3){
                    myAlert.error(d.result);
                    $("#notifyDiv").css("display","block");
                    $("#notifyText").html(d.result);
                    $("#notify").val(1);
                    document.getElementById('btnSubmit').disabled=false;
                } else {
                    myAlert.error(d.result);
                    document.getElementById('btnSubmit').disabled=false;
                }
            },function () {
                document.getElementById('btnSubmit').disabled=false;
            })
        }
    })
}
;