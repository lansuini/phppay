
$(function() {
    feeCon = {}
    // 基于准备好的dom，初始化echarts实例
    common.getAjax(apiPath + "getbasedata?requireItems=payType,productType,rateType", function(a) {
        $("#selProType").initSelect(a.result.productType, "key", "value", "产品类型");
        $("#selPayType").initSelect(a.result.payType, "key", "value", "支付方式");
        $("#selRateType").initSelect(a.result.rateType, "key", "value", "费率类型");
        $("#btnSearch").initSearch(apiPath + "rate/search", getColumns())
    });
    common.initSection()
    function getColumns() {
        return [{
            field: "-",
            title: "#",
            align: "center",
            formatter: function(b, c, a) {
                return a + 1
            }
        }, {
            field: "payTypeDesc",
            title: "产品类型",
            align: "center"
        }, {
            field: "productTypeDesc",
            title: "支付方式",
            align: "center"
        }, {
            field: "bankCodeDesc",
            title: "银行",
            align: "center"
        }, {
            field: "cardTypeDesc",
            title: "卡种",
            align: "center"
        }, {
            field: "rateTypeDesc",
            title: "费率类型",
            align: "center"
        }, {
            field: "rate",
            title: "费率值",
            align: "center",
        }, {
            field: "fixed",
            title: "固定收取",
            align: "center",
        },{
            field: "minServiceCharge",
            title: "最小手续费",
            align: "center",
        },{
            field: "maxServiceCharge",
            title: "最大手续费",
            align: "center",
        }]
    }
});
;