$(function() {

    common.getAjax(apiPath + "getbasedata?requireItems=dealType", function(a) {
        // $("#selAccountType").initSelect(a.result.accountType, "key", "value", "账户类型");

        $("#btnSearch").initSearch(apiPath + "agent/agentFinance/search", getColumns(), {
            // success_callback: buildSummary
        })

        $("#selDealType").initSelect(a.result.dealType, "key", "value", "交易类型");

        $("#btnExport").bind('click',function () {
            $("#btnExport").initExport(apiPath + "agent/agentFinance/search", getColumns(), {})
        })
    });

    common.initSection(false,true)
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
        field: "agentName",
        title: "代理账号",
        align: "center"
    }, {
        field: "dealTypeDesc",
        title: "交易类型",
        align: "center"
    }, {
        field: "platformOrderNo",
        title: "平台订单号",
        align: "center"
    }, {
        field: "created_at",
        title: "交易时间",
        align: "center"
    }, {
        field: "dealMoney",
        title: "操作金额",
        align: "center",
        formatter: function(b, c, a) {
            return common.fixAmount(b)
        }
    }, {
        field: "takeBalance",
        title: "余额",
        align: "center",
        formatter: function(b, c, a) {
            return common.fixAmount(b)
        }
    }, {
        field: "balance",
        title: "可提余额",
        align: "center",
        formatter: function(b, c, a) {
            return common.fixAmount(b)
        }
    }, {
        field: "bailBalance",
        title: "保证金",
        align: "center",
        formatter: function(b, c, a) {
            return common.fixAmount(b)
        }
    }, {
        field: "freezeBalance",
        title: "冻结金额",
        align: "center",
        formatter: function(b, c, a) {
            return common.fixAmount(b)
        }
    }, {
        field: "optAdmin",
        title: "操作者",
        align: "center"
    }, {
        field: "optDesc",
        title: "操作备注",
        align: "center"
    }, {
        field: "updated_at",
        title: "数据更新时间",
        align: "center"
    }]
}

function buildSummary(i) {
    if (i && i.success && i.rows.length > 0) {
        var b = i.stat;
        /* for (var g = 0, f = i.rows.length; g < f; g++) {
            b += parseFloat(i.rows[g].amount)
        } */
        var a = $("<div class='fixed-table-summary'></div>");
        var h = $("<table></table>");
        var d = $("<tbody></tbody>");
        var e = $("<tr></tr>");
        var c = $("<tr></tr>");
        e.append("<td class='title'>笔数统计</td>").append("<td>总记录数：" + b.num + "</td>");
        if(b.settlementCount == null){
            e.append("<td class='item'>" + "代付笔数：" + 0 + "</td>");
        } else {
            e.append("<td class='item'>" + "代付笔数：" + b.settlementCount + "</td>");
        }
        if(b.chargeCount == null){
            e.append("<td class='item'>" + "充值笔数：" + 0 + "</td>");
        } else {
            e.append("<td class='item'>" + "充值笔数：" + b.chargeCount + "</td>");
        }
        e.append("<td class='item'></td><td class='item'></td><td class='item'></td>");

        c.append("<td class='title'>金额统计</td>").append("<td>支付订单金额：" + b.payAmount + "</td>");
        c.append("<td class='item'>" + "支付手续费：" + b.payServiceFees + "</td>");
        c.append("<td class='item'>" + "代付订单金额：" + b.settlementAmount + "</td>");
        c.append("<td class='item'>" + "代付手续费：" + b.settlementServiceFees + "</td>");
        c.append("<td class='item'>" + "充值订单金额：" + b.chargeAmount + "</td>");
        c.append("<td class='item'>" + "充值手续费：" + b.chargeServiceFees + "</td>");

        $("#tabMain").parent().parent().parent().append(a.append(h.append(d.append(e).append(c))))
    }
}
;