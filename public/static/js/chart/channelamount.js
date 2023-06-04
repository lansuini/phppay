$(function() {
    $("#btnSearch").initSearch(apiPath + "/amount", getColumns(), {
        // success_callback: buildSummary
    });
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
        field: "accountDate",
        title: "财务日期",
        align: "center"
    }, {
        field: "merchantNo",
        title: "上游商户号",
        align: "center"
    }, {
        field: "shortName",
        title: "商户简称",
        align: "center",
        formatter: function(b, c, a) {
            return common.fixAmount(b)
        }
    }, {
        field: "balance",
        title: "账户余额",
        align: "center",
        formatter: function(b, c, a) {
            return common.fixAmount(b)
        }
    }, {
        field: "payAmount",
        title: "支付",
        align: "center",
        formatter: function(b, c, a) {
            return common.fixAmount(b)
        }
    }, {
        field: "settlementAmount",
        title: "代付",
        align: "center",
        formatter: function(b, c, a) {
            return common.fixAmount(b)
        }
    }, {
        field: "serviceCharge",
        title: "商户手续费",
        align: "center",
        formatter: function(b, c, a) {
            return common.fixAmount(b)
        }
    }, {
        field: "channelServiceCharge",
        title: "上游手续费",
        align: "center",
        formatter: function(b, c, a) {
            return common.fixAmount(b)
        }
    }, {
        field: "incomeAmount",
        title: "平台收入",
        align: "center",
        formatter: function(b, c, a) {
            return common.fixAmount(c.channelServiceCharge - c.serviceCharge)
        }
    }]
}
