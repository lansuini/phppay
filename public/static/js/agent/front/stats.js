
$(function() {
    // 基于准备好的dom，初始化echarts实例
    $("#btnSearch").initSearch(apiPath + "stats/search", getColumns())
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
            title: "账单日期",
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
            align: "center"
        }, {
            field: "payAmount",
            title: "今日支付",
            align: "center"
        }, {
            field: "settlementAmount",
            title: "今日代付",
            align: "center",
        }, {
            field: "chargeAmount",
            title: "今日充值",
            align: "center",
        },  {
            field: "merchantFees",
            title: "今日商户手续费",
            align: "center",
        },{
            field: "fees",
            title: "平台手续费",
            align: "center",
        },{
            field: "profit",
            title: "今日收入",
            align: "center",
        }]
    }
});
;