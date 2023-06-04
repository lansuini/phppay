$(function() {
    $("#btnResetSignKey").click(getsignkey);
    $("#btnSubmit").click(submit);

    common.getAjax("getNotice", function(c) {
        if (c.success) {
            arr=c.result;
            console.log(c.result);
            str='';
            len = arr.length
            for(var j = 0,len; j < len; j++){
                str+='<dt>'+arr[j].title+'：'+arr[j].content+'</dt><dd>'+arr[j].published_time+'</dd>';
            }
            if(len>0){
                $("#divNotice").append('<div class="modal-footer" ><input type="button" onclick="noticeBtn()" class="btn btn-default" data-dismiss="modal" value="点击查看更多" /></div>');
            }
            $("#noticeDetail").html(str);
        }
    });
});

function noticeBtn() {
    location.href = "/notice";
}


function showSignKeyModal(a) {
    $("#signKeyModal").modal();
}

function getsignkey() {
    var a = $("#merchantNo").val();
    var code = $("#code").val();
    common.getAjax("getsignkey?merchantNo=" + a+"&code="+code, function(b) {
        if (b.success && b.result.signKey && b.result.signKey.length > 0) {
            $("#seeSignKey").hide();
            $("#signKey").val(b.result.signKey);
            $("#signKey").show();
        } else {
            myAlert.error("验证失败，请重试")
        }
    });
}

function showMoneyModal() {
    $("#moneyModal").modal();
}

function submit() {
    if ($("#toMerchantNo").val() == "") {
        myAlert.warning($("#toMerchantNo").attr("placeholder"));
        return
    }
    if ($("#shortName").val() == "") {
        myAlert.warning($("#shortName").attr("placeholder"));
        return
    }
    var money = $("#money").val();
    if (money == "") {
        myAlert.warning($("#money").attr("placeholder"));
        return
    }else if(money < 0){
        myAlert.warning('转账金额不能为负数');
        return
    }
    if ($("#gcode").val() == "") {
        myAlert.warning($("#gcode").attr("placeholder"));
        return
    }
    if ($("#paycode").val() == "") {
        myAlert.warning($("#paycode").attr("placeholder"));
        return
    }
    $("#btnSubmit").attr('disabled',"disabled");
    common.submit(apiPath + 'index/transform', "moneyModal", function() {
        location.href = location.href
    });
    $("#btnSubmit").removeAttr('disabled');
}