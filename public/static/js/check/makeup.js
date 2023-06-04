$(function() {
    common.getAjax(apiPath + "getbasedata?requireItems=checkStatusCode", function(a) {
        $("#selCheckStatus").initSelect(a.result.checkStatusCode, "key", "value", "审核状态");
        /*$("#selPayType").initSelect(a.result.payType, "key", "value", "支付方式");
        $("#selChannel").initSelect(a.result.channel, "key", "value", "支付渠道"); */
        $("#btnSearch").initSearch(apiPath + "check/getMakeUp", getColumns(), {
            /* success_callback: buildSummary */
        })
    });
    /*apiPath += "payorder/";*/
    $("#btnSubmit").click(submitPerfect);
    common.initSection()
    common.initDateTime("channelNoticeTime")
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
        field: "type",
        title: "类型",
        align: "center"
    }, {
        field: "desc",
        title: "备注",
        align: "center"
    }, {
        field: "status",
        title: "状态",
        align: "center"
    }, {
        field: "commiter_id",
        title: "提审人id",
        align: "center",
        
    }, {
        field: "ip",
        title: "提审人ip",
        align: "center"
    },{
        field: "created_at",
        title: "提审时间",
        align: "center"
    }, {
        field: "-",
        title: "操作",
        align: "center",
        formatter: function(b, c, a) {
            if(c.status == '未审核'){
                return "<a onclick='showEditModal(" + JSON.stringify(c) + ")'>编辑</a>";
            }
        }
    }]
}
function showEditModal(a) {    
    var b = $("#editModal");
    b.find("div.modal-body").find("input,select").val("").end().find("div.form-add").hide().end().find("div.form-edit").hide();
    b.find("h4.modal-title").html("审核").end().find("div.form-edit").show();
    if(a.type == 'payOrderSupplement'){
        console.log(a.content);
        $("#id").val(a.id);
        $("#orderId").val(a.content.orderId);
        $("#platformOrderNo").val(a.content.platformOrderNo);
        $("#orderAmount").val(a.content.orderAmount);
        $("#channel").val(a.content.channel);
        $("#channelMerchantNo").val(a.content.channelMerchantNo);
        $("#channelOrderNo").val(a.content.channelOrderNo);
        $("#channelNoticeTime").val(a.content.channelNoticeTime);
        $("#orderStatus").val(a.content.orderStatus);
    }
        b.modal()
}

function submitPerfect() {
    if ($("#txtChannelOrderNo").val() == "") {
        myAlert.warning($("#txtChannelOrderNo").attr("placeholder"));
        return
    }
    if ($("#txtChannelNoticeTime").val() == "") {
        myAlert.warning($("#txtChannelNoticeTime").attr("placeholder"));
        return
    }
    common.submit(apiPath + "payorder/perfect?orderId=" + common.getQuery("orderId")+'&type=up', "editModal", function() {
        location.href = location.href
    })
}
;