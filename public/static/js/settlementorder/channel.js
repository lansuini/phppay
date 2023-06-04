$(function() {
    common.getAjax(apiPath + "getbasedata/merchantChannel", function(a) {
        apiPath += "settlementorder/channel/";
        $("#selChannel").initSelect(a.result.channel, "key", "value", "代付渠道");
        $("#btnSearch").initSearch(apiPath + "search", getColumns());
    });
    $("#btnSubmit").click(function() {
        recharge()
    });

});
function showEditModal(a) {

    // $("#btnSubmit").attr('disabled',false);
    var d = $("#editModal");
    d.find("div.modal-body").find("input[id],select[id]").val("");
    $("#txtMerchantNo").val(a)
    d.modal()
}

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
        title: "代付渠道",
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
        field: "settlementAccountTypeDesc",
        title: "代付账户",
        align: "center"
    // }, {
    //     field: "accountBalance",
    //     title: "账户余额",
    //     align: "center",
    //     formatter: function(b, c, a) {
    //         return common.fixAmount(b)
    //     }
    }, {
        field: "-",
        title: "操作",
        align: "center",
        formatter: function(b, c, a) {
            return "<a onclick='showEditModal(\"" + c.setId + "\")'>充值</a>"
        }
    }]
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
    location.href = apiPath + 'recharge?setId=' + $("#txtMerchantNo").val() + '&amount=' + $("#txtAmount").val();
    // $("#btnSubmit").attr('disabled','disabled');
    // common.getAjax(apiPath + "recharge?setId=" + a, function(e) {
    //     if (e && e.success) {
    //
    //         myAlert.success(e.msg, undefined, function() {
    //             location.href = apiPath + 'recharge?amount='+ $("#txtAmount").val();
    //             // location.href = location.href
    //         })
    //     }else {
    //         myAlert.error(e.msg, undefined, function () {
    //             // location.href = location.href
    //         })
    //     }
    // })
}


;