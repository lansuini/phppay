$(function() {
    common.getAjax(apiPath + "getbasedata?requireItems=bankCode", function(a) {
        $("select[name='selBankCode']").initSelect(a.result.bankCode, "key", "value", "请选择收款银行")
    });
    common.getAjax(apiPath + "settlementorder/chooseMerchantCard", function(a) {
        $("select[name='txtBankAccountInfo']").initSelect(a.comInfos, "key", "value", "请选择收款人信息")
    });

    $("#txtOrderAmount").bind("input propertychange", function() {
        if (!common.isDecimal($(this).val())) {
            $(this).val("")
        }
    });
    $("#btnSubmit").click(submit)
    
    //文本填写方式批量代付
    $("#btnSubmitAll").click(submitAll);

    $("#btnAliSubmitAll").click(submitAliAll);
    // $("input[name='txtBankAccountNo']").blur(selectBankInfo);
});

function selectBankInfo(_this) {
    // console.log($(_this).val());
    if($(_this).val() != '') {
        bankObj = $(_this).parent().prev().children();
        common.getAjax(apiPath + "settlementbatchorder/getBankCode?cardNo=" + $(_this).val(), function (a) {
            if(a.result != 'no'){
                bankObj.val(a.result)
            }
        })
    }
}

function showConfirmModal() {

    if ($("#txtOrderAmount").val() == "") {
        myAlert.warning($("#txtOrderAmount").attr("placeholder"));
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
    var a = $("#btnFile")[0].files;
    if (a.length == 0) {
        document.getElementById('btnSubmit').disabled=false;
        myAlert.warning("请选择文件");
        return
    }
    if ($("#home #googleAuth").val() == "") {
        document.getElementById('btnSubmit').disabled=false;
        myAlert.warning($("#googleAuth").attr("placeholder"));
        return
    }
    if ($("#txtApplyPerson").val() == "") {
        document.getElementById('btnSubmit').disabled=false;
        myAlert.warning($("#txtApplyPerson").attr("placeholder"));
        return
    }
    if ($("#txtOrderReason").val() == "") {
        document.getElementById('btnSubmit').disabled=false;
        myAlert.warning($("#txtOrderReason").attr("placeholder"));
        return
    }
    myConfirm.show({
        title: "确定要发起批量代付？",
        sure_callback: function() {
            var b = new FormData();
            b.append("file", a[0]);
            b.append("applyPerson", $("#txtApplyPerson").val());
            b.append("orderReason", $("#txtOrderReason").val());
            b.append("uploadNotify", $("#uploadNotify").val());
            b.append("googleAuth", $("#home #googleAuth").val());
            // b.append("loginName", $("#txtLoginName").val());
            document.getElementById('btnSubmit').disabled=true;
            common.uploadFile(contextPath + "api/settlementbatchorder/doCreate", b, function(c) {
                if (c.success == 1) {
                    myAlert.success(c.result, undefined, function() {
                        window.location.href = contextPath + "settlementorder";
                    });
                //     $("#btnFile").val('');
                //     $("#txtApplyPerson").val('');
                //     $("#txtOrderReason").val('');
                //     $("#uploadNotify").val('');
                //     myAlert.success(c.result);
                // // } else {
                //     document.getElementById('btnSubmit').disabled=false;
                }else if(c.success == 3){
                    myAlert.error(c.result);
                    $("#uploadNotifyDiv").css("display","block");
                    $("#uploadNotifyText").html(c.result);
                    $("#uploadNotify").val(1);
                    document.getElementById('btnSubmit').disabled=false;
                } else {
                    myAlert.error(c.result);
                    document.getElementById('btnSubmit').disabled=false;
                }
            });
        }
    })
}

//文本填写方式银行卡批量代付
function submitAll() {
    if ($("#txtOrderReasonStr").val() == "") {
        myAlert.warning($("#txtOrderReasonStr").attr("placeholder"));
        return
    }
    if ($("#txtApplyPersonStr").val() == "") {
        myAlert.warning($("#txtApplyPersonStr").attr("placeholder"));
        return
    }

    if ($("#card #googleAuth").val() == "") {
        document.getElementById('btnSubmit').disabled=false;
        myAlert.warning($("#googleAuth").attr("placeholder"));
        return
    }

    var rows=$("#cardInfoTable").find("tr").length;
    for (var i=1;i<rows+1;i++){
        if ($("#trNum"+i+" input[name='txtOrderAmount']").val() == "") {
            myAlert.warning($("#trNum"+i+" input[name='txtOrderAmount']").attr("placeholder"));
            return
        }
        if ( $("#trNum"+i+" select[name='selBankCode']").val() == "") {
            myAlert.warning($("#trNum"+i+" input[name='selBankCode']").find("option:first").html());
            return
        }
        if ( $("#trNum"+i+" input[name='txtBankAccountNo']").val() == "") {
            myAlert.warning($("#trNum"+i+" input[name='txtBankAccountNo']").attr("placeholder"));
            return
        }
        if ($("#trNum"+i+" input[name='txtBankAccountName']").val() == "") {
            myAlert.warning($("#trNum"+i+" input[name='txtBankAccountName']").attr("placeholder"));
            return
        }
        if ($("#trNum"+i+" input[name='txtProvince']").val() == "") {
            myAlert.warning($("#trNum"+i+" input[name='txtProvince']").attr("placeholder"));
            return
        }
        if ($("#trNum"+i+" input[name='txtCity']").val() == "") {
            myAlert.warning($("#trNum"+i+" input[name='txtCity']").attr("placeholder"));
            return
        }

        if ( $("#trNum"+i+" input[name='txtDistrict']").val() == "") {
            myAlert.warning( $("#trNum"+i+" input[name='txtDistrict']").attr("placeholder"));
            return
        }
    }

    myConfirm.show({
        title: "确定要发起批量代付？一旦确定，代付不可更改，不可撤销及退回，请自行承担",
        sure_callback: function() {
            var arr = new Array();
            for (var i=0;i<rows;i++){
                arr[i] = {};
                arr[i]['orderAmount']=$("#trNum"+(i+1)+" input[name='txtOrderAmount']").val();
                arr[i]['bankAccountInfo']=$("#trNum"+(i+1)+" select[name='txtBankAccountInfo']").val();
                arr[i]['bankCode']=$("#trNum"+(i+1)+" select[name='selBankCode']").val();
                arr[i]['bankAccountNo']=$("#trNum"+(i+1)+" input[name='txtBankAccountNo']").val();
                arr[i]['bankAccountName']=$("#trNum"+(i+1)+" input[name='txtBankAccountName']").val();
                arr[i]['province']=$("#trNum"+(i+1)+" input[name='txtProvince']").val();
                arr[i]['city']=$("#trNum"+(i+1)+" input[name='txtCity']").val();
                arr[i]['bankName']=$("#trNum"+(i+1)+" input[name='txtDistrict']").val();
            }
            var data= new FormData();
            data.append('data',JSON.stringify(arr));
            data.append("applyPerson", $("#txtApplyPersonStr").val());
            data.append("orderReason", $("#txtOrderReasonStr").val());
            data.append("googleAuth", $("#card #googleAuth").val());
            document.getElementById('btnSubmitAll').disabled=true;
            common.uploadFile(contextPath + "api/settlementbatchorder/inputDoCreate", data, function(c) {
                if (c.success == 1) {
                    myAlert.success(c.result, undefined, function() {
                        window.location.href = contextPath + "settlementorder";
                    });
                } else {
                    myAlert.warning(c.result);
                    document.getElementById('btnSubmitAll').disabled=false;
                }
            })
        }
    })

}

//文本填写方式支付宝批量代付
function submitAliAll() {
    if ($("#txtOrderReasonStrAli").val() == "") {
        myAlert.warning($("#txtApplyPersonStrAli").attr("placeholder"));
        return
    }
    if ($("#txtApplyPersonStrAli").val() == "") {
        myAlert.warning($("#txtApplyPersonStrAli").attr("placeholder"));
        return
    }

    if ($("#alipy #googleAuth").val() == "") {
        document.getElementById('btnSubmit').disabled=false;
        myAlert.warning($("#googleAuth").attr("placeholder"));
        return
    }

    var rows=$("#aliInfoTable").find("tr").length;
    for (var i=1;i<rows+1;i++){
        if ($("#trAliNum"+i+" input[name='txtOrderAmount']").val() == "") {
            myAlert.warning($("#trAliNum"+i+" input[name='txtOrderAmount']").attr("placeholder"));
            return
        }
        if ( $("#trAliNum"+i+" input[name='txtAliAccountNo']").val() == "") {
            myAlert.warning($("#trAliNum"+i+" input[name='txtAliAccountNo']").attr("placeholder"));
            return
        }
        if ($("#trAliNum"+i+" input[name='txtAliAccountNo2']").val() == "") {
            myAlert.warning($("#trAliNum"+i+" input[name='txtAliAccountNo2']").attr("placeholder"));
            return
        }
        if ($("#trAliNum"+i+" input[name='txtAliAccountName']").val() == "") {
            myAlert.warning($("#trAliNum"+i+" input[name='txtAliAccountName']").attr("placeholder"));
            return
        }
        if($("#trAliNum"+i+" input[name='txtAliAccountNo']").val()!=$("#trAliNum"+i+" input[name='txtAliAccountNo2']").val()){
            myAlert.warning("两次输入的收款账号不一致！");
            return
        }
    }

    myConfirm.show({
        title: "确定要发起支付宝批量代付？一旦确定，代付不可更改，不可撤销及退回，请自行承担",
        sure_callback: function() {
            var arr = new Array();
            for (var i=0;i<rows;i++){
                arr[i] = {};
                arr[i]['orderAmount']=$("#trAliNum"+(i+1)+" input[name='txtOrderAmount']").val();
                arr[i]['aliAccountNo']=$("#trAliNum"+(i+1)+" input[name='txtAliAccountNo']").val();
                arr[i]['aliAccountName']=$("#trAliNum"+(i+1)+" input[name='txtAliAccountName']").val();
            }
            var data= new FormData();
            data.append('data',JSON.stringify(arr));
            data.append("applyPerson", $("#txtApplyPersonStrAli").val());
            data.append("orderReason", $("#txtOrderReasonStrAli").val());
            data.append("type",'alipay');
            data.append("alipayNotify", $("#alipayNotify").val());
            data.append("googleAuth", $("#alipy #googleAuth").val());
            document.getElementById('btnAliSubmitAll').disabled=true;
            common.uploadFile(contextPath + "api/settlementbatchorder/inputDoCreate", data, function(c) {
                if (c.success == 1) {
                    myAlert.success(c.result, undefined, function() {
                        window.location.href = contextPath + "settlementorder";
                    });
                }else if(c.success == 3){
                    myAlert.error(c.result);
                    $("#alipayNotifyDiv").css("display","block");
                    $("#alipayNotifyText").html(c.result);
                    $("#alipayNotify").val(1);
                    document.getElementById('btnAliSubmitAll').disabled=false;
                }  else {
                    document.getElementById('btnAliSubmitAll').disabled=false;
                    myAlert.warning(c.result);
                }
            })
        }
    })
}

//点击新增，新增一列

function addDiv(num){
    var num=num+1;

    var str='<tr id="trNum'+num+'"><td><input type="text" class="form-control" name="txtOrderAmount" data-field="orderAmount" placeholder="请输入付款金额" maxlength="15" /></td>' +
        '<td><select type="text" class="form-control" name="txtBankAccountInfo" onchange="showBankInfo('+num+')" data-field="bankAccountInfo" maxlength="30" ></select></td>' +
        '<td><select class="form-control" name="selBankCode" data-field="bankCode"  placeholder="请输入收款银行"></select></td>\n' +
        '<td><input type="text" class="form-control" onchange="selectBankInfo(this)" name="txtBankAccountNo" data-field="bankAccountNo" placeholder="请输入收款账号" maxlength="30" /></td>' +
        '<td><input type="text" class="form-control" name="txtBankAccountName" data-field="bankAccountName" placeholder="请输入收款人姓名" maxlength="30" ></td>' +
        '<td><input type="text" class="form-control" name="txtProvince" data-field="province" placeholder="请输入开户省份" maxlength="10" /></td>' +
        '<td> <input type="text" class="form-control" name="txtCity" data-field="city" placeholder="请输入开户城市" maxlength="10" /></td>' +
        '<td><input type="text" class="form-control" name="txtDistrict" data-field="bankName" placeholder="请输入开户区/县" maxlength="10" /></td>\n' +
        // '<td> <input type="text" class="form-control" name="txtOrderReason" data-field="orderReason" placeholder="请输入付款原因" maxlength="100" /></td>' +
        '<td><button type="button" class="btn btn-danger btn-sm" onclick="delDiv(this)">删除</button></td></tr>';


    $("#cardInfoTable").append(str);
    common.getAjax(apiPath + "getbasedata?requireItems=bankCode", function(a) {
        $("#trNum"+num+" select[name='selBankCode']").initSelect(a.result.bankCode, "key", "value", "请选择收款银行")
    });

    common.getAjax(apiPath + "settlementorder/chooseMerchantCard", function(a) {
        $("#trNum"+num+" select[name='txtBankAccountInfo']").initSelect(a.comInfos, "key", "value", "请选择收款人信息")
    });

    $('#btnStrs').html('<button type="button" class="btn btn-primary btn-sm" onclick="addDiv('+num+')">增加</button>');
}

function addAliDiv(num){
    var num=num+1;

    var str='<tr id="trAliNum'+num+'"><td><input type="text" class="form-control" name="txtOrderAmount" data-field="orderAmount" placeholder="请输入付款金额" maxlength="15" /></td>' +
        '<td><input type="text" class="form-control" name="txtAliAccountNo" data-field="aliAccountNo" placeholder="请输入收款账号" maxlength="30" /></td>' +
        '<td><input type="text" class="form-control" name="txtAliAccountNo2" placeholder="请再次输入收款账号" maxlength="30" /></td>' +
        '<td><input type="text" class="form-control" name="txtAliAccountName" data-field="aliAccountName" placeholder="请输入收款人姓名" maxlength="30" ></td>' +
        // '<td> <input type="text" class="form-control" name="txtOrderReason" data-field="orderReason" placeholder="请输入付款原因" maxlength="100" /></td>' +
        '<td><button type="button" class="btn btn-danger btn-sm" onclick="delDiv(this)">删除</button></td></tr>';

    $("#aliInfoTable").append(str);

    $('#btnAliStrs').html('<button type="button" class="btn btn-primary btn-sm" onclick="addAliDiv('+num+')">增加</button>');
}

function delDiv(tdobject) {
    var td=$(tdobject);
    td.parents("tr").remove();
}

function showBankInfo(num) {
    var id = $('#trNum'+num+' option:selected').val();
    common.getAjax(apiPath + "settlementorder/chooseMerchantCard?cardId="+ id , function(a) {
        $("#trNum"+num+" input[name='selBankCode']").val(a.rows[0].bankCode).attr('disabled',"disabled");
        $("#trNum"+num+" input[name='selBankName']").val(a.rows[0].bankName).attr('disabled',"disabled");
        $("#trNum"+num+" input[name='txtCity']").val(a.rows[0].city).attr('disabled',"disabled");
        $("#trNum"+num+" input[name='txtBankAccountNo']").val(a.rows[0].accountNo).attr('disabled',"disabled");
        $("#trNum"+num+" input[name='txtBankAccountName']").val(a.rows[0].accountName).attr('disabled',"disabled");
        $("#trNum"+num+" input[name='txtProvince']").val(a.rows[0].province).attr('disabled',"disabled");
        $("#trNum"+num+" input[name='txtDistrict']").val(a.rows[0].district).attr('disabled',"disabled");
    })

}
;