$(function() {
    common.getAjax(apiPath + "getbasedata?requireItems=channel", function(a) {
        apiPath += "rechargeorder/";
        // $("#selChannel").initSelect(a.result.channel, "key", "value", "支付渠道");
        $("#btnSearch").initSearch(apiPath + "paychannel/search", getColumns())
    });

    $("#btnSubmit").click(function() {
        recharge()
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
        field: "channelDesc",
        title: "支付渠道",
        align: "center"
    }, {
        field: "channelMerchantNo",
        title: "渠道商户号",
        align: "center"
    }, {
        field: "merchantNo",
        title: "商户号",
        align: "center"
    }, {
        field: "shortName",
        title: "商户简称",
        align: "center"
    }, {
        field: "payTypeDescs",
        title: "支付方式",
        align: "center"
    }, {
        field: "-",
        title: "操作",
        align: "center",
        formatter: function(b, c, a) {
            return "<a onclick='showEditModal(\"" + c.setId + "\")'>充值</a>"
        }
    }]
}

function showEditModal(a) {

    // $("#btnSubmit").attr('disabled',false);
    var d = $("#editModal");
    d.find("div.modal-body").find("input[id],select[id]").val("");
    $("#txtMerchantNo").val(a)
    d.modal()
}


function recharge(){
    if ($("#txtMerchantNo").val() == "") {
        myAlert.warning($("#txtMerchantNo").attr("placeholder"));
        return
    }
    if ($("#txtAmount").val() == "") {
        myAlert.warning($("#txtAmount").attr("placeholder"));
        return
    }
    // location.href = apiPath + 'insiderecharge?setId=' + $("#txtMerchantNo").val() + '&amount=' + $("#txtAmount").val();
    $("#btnSubmit").attr('disabled','disabled');
    common.getAjax(apiPath + "insiderecharge?setId=" + $("#txtMerchantNo").val() + '&amount=' + $("#txtAmount").val(), function(e) {
        if (e && e.success) {

            myAlert.success(e.msg, undefined, function() {
                window.open(e.payUrl)
                location.href = location.href
            })
        }else {
            myAlert.error(e.msg, undefined, function () {
                location.href = location.href
            })
        }
    })
}

function showFileModal(a) {
    if (a != undefined) {
        $("#txtMerchantNo").val(a).attr("disabled", "disabled")
    } else {
        $("#txtMerchantNo").val("").removeAttr("disabled")
    }
    $("#btnFile").val("");
    $("#fileModal").modal()
}
function uploadFile() {
    if ($("#txtMerchantNo").val() == "") {
        myAlert.warning("请输入商户号");
        return
    }
    var a = $("#btnFile")[0].files;
    if (a.length == 0) {
        myAlert.warning("请选择文件");
        return
    }
    var b = new FormData();
    b.append("file", a[0]);
    b.append("merchantNo", $("#txtMerchantNo").val());
    common.uploadFile(apiPath + "import", b, function(c) {
        if (c.success == 1) {
            myAlert.success("操作成功");
            $("#fileModal").modal("hide");
            $("#btnSearch").click()
        } else {
            myAlert.error(c.result.length > 0 ? c.result : "操作失败")
        }
    })
}
;