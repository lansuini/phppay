$(function() {
    $("#btnSearch").initSearch(apiPath + "dataReport/search", getColumns(), {
        // success_callback: buildSummary
    });
    $("#btnExport").bind('click',function () {
        $("#btnExport").initExport(apiPath + "dataReport/search", getColumns(), {})
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
        field: "accountDate",
        title: "日期",
        align: "center"
    }, {
        field: "agentName",
        title: "代理账号",
        align: "center"
    }, {
        field: "loginName",
        title: "新增下级商户",
        align: "center"
    }, {
        field: "nickName",
        title: "下发代理佣金笔数",
        align: "center"
    }, {
        field: "balance",
        title: "下发代理佣金金额",
        align: "center",
        formatter: function(b, c, a) {
            return common.fixAmount(b)
        }
    }, {
        field: "nickName",
        title: "代理提款笔数",
        align: "center"
    }, {
        field: "payAmount",
        title: "代理提款金额",
        align: "center",
        formatter: function(b, c, a) {
            return b == null ?common.fixAmount(0):common.fixAmount(b);
        }
    }, {
        field: "",
        title: "代理提款手续费",
        align: "center",
        formatter: function(b, c, a) {
            return b == null ?common.fixAmount(0):common.fixAmount(b);
        }
    }]
}
