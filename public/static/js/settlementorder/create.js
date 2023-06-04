$(function() {
    common.getAjax(apiPath + "getbasedata?requireItems=bankCode", function(a) {
        $("#selBankCode").initSelect(a.result.bankCode, "key", "value", "请选择收款银行")
    });
    common.getAjax(apiPath + "settlementorder/chooseMerchantCard", function(a) {
        $("#txtBankAccountInfo").initSelect(a.comInfos, "key", "value", "请选择收款人信息")
    });
    $("#txtOrderAmount").bind("input propertychange", function() {
        if (!common.isDecimal($(this).val())) {
            $(this).val("")
        }
    });
    $("#btnConfirm").click(showConfirmModal);
    $("#txtBankAccountInfo").change(showBankInfo);
    $("#btnSubmit").click(submit)
});

function showBankInfo() {
   var id = $(this).children('option:selected').val();
   common.getAjax(apiPath + "settlementorder/chooseMerchantCard?cardId="+ id , function(a) {
       $("#selBankCode").val(a.rows[0].bankCode).attr('disabled',"disabled");
       $("#selBankName").val(a.rows[0].bankName).attr('disabled',"disabled");
       $("#txtCity").val(a.rows[0].city).attr('disabled',"disabled");
       $("#txtBankAccountNo").val(a.rows[0].accountNo).attr('disabled',"disabled");
       $("#txtBankAccountName").val(a.rows[0].accountName).attr('disabled',"disabled");
       $("#txtProvince").val(a.rows[0].province).attr('disabled',"disabled");
       $("#txtDistrict").val(a.rows[0].district).attr('disabled',"disabled");
   })

}
function showConfirmModal() {
    if ($("#txtOrderAmount").val() == "") {
        myAlert.warning($("#txtOrderAmount").attr("placeholder"));
        return
    }
    if ($("#selBankCode").val() == "") {
        myAlert.warning($("#selBankCode").find("option:first").html());
        return
    }
    if ($("#txtBankAccountNo").val() == "") {
        myAlert.warning($("#txtBankAccountNo").attr("placeholder"));
        return
    }
    // if ($("#txtBankAccountNo2").val() == "") {
    //     myAlert.warning($("#txtBankAccountNo2").attr("placeholder"));
    //     return
    // }
    // if ($("#txtBankAccountNo").val() != $("#txtBankAccountNo2").val()) {
    //     myAlert.warning("两次输入的收款账号不一致，请重新输入");
    //     return
    // }
    if ($("#accountName").val() == "") {
        myAlert.warning($("#accountName").attr("placeholder"));
        return
    }
    if ($("#txtProvince").val() == "") {
        myAlert.warning($("#txtProvince").attr("placeholder"));
        return
    }
    if ($("#txtCity").val() == "") {
        myAlert.warning($("#txtCity").attr("placeholder"));
        return
    }

    if ($("#txtDistrict").val() == "") {
        myAlert.warning($("#txtDistrict").attr("placeholder"));
        return
    }
    // if ($("#txtBankName").val() == "") {
    //     myAlert.warning($("#txtBankName").attr("placeholder"));
    //     return
    // }
    if ($("#txtOrderReason").val() == "") {
        myAlert.warning($("#txtOrderReason").attr("placeholder"));
        return
    }
    $("#spanOrderAmount").html($("#txtOrderAmount").val());
    $("#spanBankCode").html($("#selBankCode option:selected").html());
    // $("#spanBankCode").html($("#selBankName").val());
    $("#spanBankAccountNo").html($("#txtBankAccountNo").val());
    $("#spanBankAccountName").html($("#txtBankAccountName").val());
    $("#spanProvince").html($("#txtProvince").val());
    $("#spanCity").html($("#txtCity").val());
    // $("#spanBankName").html($("#txtBankName").val());
    $("#spanBankDistrict").html($("#txtDistrict").val());
    $("#spanOrderReason").html($("#txtOrderReason").val());
    $("#txtApplyPerson").val("");
    $("#confirmModal").modal()
}
function submit() {
    document.getElementById('btnSubmit').disabled=true;
    if ($("#txtApplyPerson").val() == "") {
        myAlert.warning($("#txtApplyPerson").attr("placeholder"));
        document.getElementById('btnSubmit').disabled=false;
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

            common.getAjax(common.perfectUrl(apiPath + "settlementorder/create", "divContainer"), function(d) {

                if(d.success === 1){

                    window.location.href = contextPath + "settlementorder"

                } else if (d.success === 2) {

                    myAlert.error(d.result)
                    $("#forGoogleAuth").css("display","block");
                    $("#googleAuth").attr("type","text");
                    document.getElementById('btnSubmit').disabled=false;

                } else {
                    myAlert.error(d.result)
                    document.getElementById('btnSubmit').disabled=false;
                }
            },function () {
                document.getElementById('btnSubmit').disabled=false;
            })
        }
    })
}
;