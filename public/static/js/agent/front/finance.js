
$(function() {
    feeCon = {}
    // 基于准备好的dom，初始化echarts实例
    common.getAjax(apiPath + "getbasedata/classItem?requireItems=finance", function(a) {
        $("#selType").initSelect(a.result.finance, "key", "value", "请选择类型");
        $("#btnSearch").initSearch(apiPath + "finance/search", getColumns())
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
            field: "created_at",
            title: "申请时间",
            align: "center"
        }, {
            field: "platformOrderNo",
            title: "订单号",
            align: "center"
        }, {
            field: "dealTypeDesc",
            title: "交易类型",
            align: "center"
        }, {
            field: "allBalance",
            title: "总额(可提+冻结)",
            align: "center"
        }, {
            field: "bailBalance",
            title: "保证金",
            align: "center"
        }, {
            field: "balance",
            title: "可提余额",
            align: "center",
        },{
            field: "freezeBalance",
            title: "冻结资金",
            align: "center",
        },{
            field: "optDesc",
            title: "操作备注",
            align: "center",
        }]
    }
});
;