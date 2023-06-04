$(function() {
    $("#btnSearch").initSearch(apiPath + "report", getColumns(), {
        success_callback: buildSummary
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
        field: "account_date",
        title: "财务日期",
        align: "center"
    }, {
        field: "pay_amount",
        title: "支付总金额",
        align: "center",
        formatter: function(b, c, a) {
            return common.fixAmount(b)
        }
    }, {
        field: "pay_count",
        title: "支付笔数",
        align: "center"
    }, {
        field: "pay_fee",
        title: "支付手续费",
        align: "center",
        formatter: function(b, c, a) {
            return common.fixAmount(b)
        }
    },{
        field: "settlement_amount",
        title: "代付总金额",
        align: "center",
        formatter: function(b, c, a) {
            return common.fixAmount(b)
        }
    }, {
        field: "settlement_count",
        title: "代付笔数",
        align: "center"
    }, {
        field: "settlement_fee",
        title: "代付手续费",
        align: "center",
        formatter: function(b, c, a) {
            return common.fixAmount(b)
        }
    }, {
        field: "recharge_amount",
        title: "充值金额",
        align: "center",
        formatter: function(b, c, a) {
            return common.fixAmount(b)
        }
    }, {
        field: "recharge_count",
        title: "充值笔数",
        align: "center"
    }, {
        field: "recharge_fee",
        title: "充值手续费",
        align: "center",
        formatter: function(b, c, a) {
            return common.fixAmount(b)
        }
    }]
}

function buildSummary(i) {
    if (i && i.success && i.rows.length > 0) {
        var a = $("<div class='fixed-table-summary'></div>");
        var h = $("<table></table>");
        var d = $("<tbody></tbody>");
        var e = $("<tr></tr>");
        var c = $("<tr></tr>");
        var f = $("<tr></tr>");
        e.append("<td class='title'>笔数统计</td>").append("<td>支付笔数：" + i.stat.sum_payCount + "</td>").append("<td>代付笔数：" + i.stat.sum_settlementCount + "</td>").append("<td>充值笔数：" + i.stat.sum_chargeCount + "</td>");
        c.append("<td class='title'>金额统计</td>").append("<td>支付总金额：" + i.stat.sum_payAmount + "</td>").append("<td>代付总金额：" + i.stat.sum_settlementAmount + "</td>").append("<td>充值总金额：" + i.stat.sum_chargeAmount + "</td>");
        f.append("<td class='title'>手续费统计</td>").append("<td>支付手续费：" + i.stat.sum_payFees + "</td>").append("<td>代付手续费：" + i.stat.sum_settlementFees + "</td>").append("<td>充值手续费：" + i.stat.sum_chargeFees + "</td>");
        $("#tabMain").parent().parent().parent().append(a.append(h.append(d.append(e).append(c).append(f))))
    }
}