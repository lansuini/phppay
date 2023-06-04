$(function() {
    common.getAjax(apiPath + "getbasedata?requireItems=channel", function(a) {
        $("#divSearch select[data-field=channel]").initSelect(a.result.channel, "key", "value", "上游渠道");
        $("#btnSearch").initSearch(apiPath + "channel/issuerecord/search", getColumns())
    });

    $("#btnSubmit").click(submit);
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
        field: "channel",
        title: "上游渠道",
        align: "center"
    }, {
        field: "channelNo",
        title: "渠道号",
        align: "center"
    },{
        field: "issueOrderNo",
        title: "下发订单号",
        align: "center"
    }, {
        field: "cardNo",
        title: "卡号",
        align: "center"
    },  {
        field: "bankCode",
        title: "银行",
        align: "center"
    }, {
        field: "userName",
        title: "姓名",
        align: "center"
    },{
        field: "issueAmount",
        title: "提现金额",
        align: "center"
    },{
        field: "orderStatusDes",
        title: "订单状态",
        align: "center"
    },{
        field: "adminName",
        title: "操作人",
        align: "center"
    }, {
        field: "created_at",
        title: "创建时间",
        align: "center"
    }, {
        field: "updated_at",
        title: "更新时间",
        align: "center"
    }, {
        field: "-",
        title: "操作",
        align: "center",
        formatter: function(b, c, a) {
            if(c.orderStatus == 'Transfered'){
                return "<a onclick='orderQuery(\"" + c.issueId + "\")'>同步上游状态</a>";
            }else{
                return "";
            }
        }
    }]
}

function orderQuery(issueId){
    common.getAjax(apiPath + "channel/issuerecord/orderquery?issueId=" + issueId, function(c) {
        if (c.success) {
            myAlert.success(c.msg, undefined, function() {
                location.href = location.href;
            });
        } else {
            myAlert.error(c.msg);
        }
    });
}
