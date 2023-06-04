
$(function() {
    // 基于准备好的dom，初始化echarts实例
    $("#btnSearch").initSearch(apiPath + "merchant/search", getColumns())
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
            field: "fullName",
            title: "商户全称",
            align: "center"
        }, {
            field: "statusDesc",
            title: "状态",
            align: "center"
        }, {
            field: "created_at",
            title: "开户时间",
            align: "center"
        }, {
            field: "relation_created",
            title: "成功下级时间",
            align: "center",
        }, {
            field: "-",
            title: "商户费率",
            align: "center",
            formatter: function(b, c, a) {
                return "<a href=" + contextPath + "merchantRate?merchantNo=" + c.merchantNo + ">费率查看</a>"
            }
        }]
    }
});
;