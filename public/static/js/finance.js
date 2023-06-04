$(function() {
    common.getAjax(apiPath + "getbasedata?requireItems=financeType", function(a) {
        // $("#selAccountType").initSelect(a.result.accountType, "key", "value", "账户类型");
        $("#selFinanceType").initSelect(a.result.financeType, "key", "value", "收支类型");
        $("#btnSearch").initSearch(apiPath + "finance/search", getColumns())
        $("#btnExport").bind('click',function () {
            $("#btnExport").initExport(apiPath + "finance/search", getColumns(), {})
        })
    });
    common.initSection(false)
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
        field: "merchantNo",
        title: "商户号",
        align: "center"
    }, {
        field: "shortName",
        title: "商户简称",
        align: "center"
    }, {
        field: "sourceDesc",
        title: "订单类型",
        align: "center"
    }, {
        field: "platformOrderNo",
        title: "平台订单号",
        align: "center"
    }, {
        field: "accountDate",
        title: "账务日期",
        align: "center"
    }, {
        field: "financeTypeDesc",
        title: "收支类型",
        align: "center"
    }, {
        field: "amount",
        title: "交易金额",
        align: "center",
        formatter: function(b, c, a) {
            return common.fixAmount(b)
        }
    }, {
        field: "balance",
        title: "余额",
        align: "center",
        formatter: function(b, c, a) {
            return common.fixAmount(b)
        }
    }, {
        field: "summary",
        title: "交易摘要",
        align: "center"
    }, {
        field: "insTime",
        title: "交易日期",
        align: "center",
        formatter: function(b, c, a) {
            return common.toDateStr("yyyy-MM-dd HH:mm:ss", b)
        }
    }]
}
;