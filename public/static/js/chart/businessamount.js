$(function() {
    $("#btnSearch").initSearch(apiPath + "/amount", getColumns(), {
        // success_callback: buildSummary
    });
    $("#btnExport").bind('click',function () {
        $("#btnExport").initExport(apiPath + "/amount", getColumns(), {})
    })
    common.initSection(true)
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
        field: "newDate",
        title: "日期",
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
        field: "merchantBalance",
        title: "账户余额",
        align: "center",
        formatter: function(b, c, a) {
            return common.fixAmount(b)
        }
    }, {
        field: "payAmount",
        title: "今日支付",
        align: "center",
        formatter: function(b, c, a) {
            return b == null ?common.fixAmount(0):common.fixAmount(b);
        }
    }, {
        field: "settlementAmount",
        title: "今日代付",
        align: "center",
        formatter: function(b, c, a) {
            return b == null ?common.fixAmount(0):common.fixAmount(b);
        }
    }, {
        field: "todayServiceCharge",
        title: "今日商户手续费",
        align: "center",
        formatter: function(b, c, a) {
            var payServiceCharge = c.payServiceCharge == null ? 0 : c.payServiceCharge;
            var settlementServiceCharge = c.settlementServiceCharge == null ? 0 : c.settlementServiceCharge;
            var serviceCharge = parseFloat(payServiceCharge) +  parseFloat(settlementServiceCharge)
            return common.fixAmount(serviceCharge)
        }
    }, {
        field: "todayChannelServiceCharge",
        title: "今日上游手续费",
        align: "center",
        formatter: function(b, c, a) {
            var payCSC = c.payCSC == null ? 0 : c.payCSC;
            var channelServiceCharge = c.channelServiceCharge == null ? 0 : c.channelServiceCharge;
            var channelServiceCharge = parseFloat(payCSC) +  parseFloat(channelServiceCharge)
            return common.fixAmount(channelServiceCharge)
        }
    }, {
        field: "incomeAmount",
        title: "平台今日收入",
        align: "center",
        formatter: function(b, c, a) {
            var payServiceCharge = c.payServiceCharge == null ? 0 : c.payServiceCharge;
            var settlementServiceCharge = c.settlementServiceCharge == null ? 0 : c.settlementServiceCharge;
            var payCSC = c.payCSC == null ? 0 : c.payCSC;
            var channelServiceCharge = c.channelServiceCharge == null ? 0 : c.channelServiceCharge;
            var serviceCharge = parseFloat(payServiceCharge) +  parseFloat(settlementServiceCharge)
            var channelServiceCharge = parseFloat(payCSC) +  parseFloat(channelServiceCharge)
            return common.fixAmount(serviceCharge - channelServiceCharge)
        }
    }]
}
