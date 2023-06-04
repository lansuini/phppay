$(function() {
    // console.log(apiPath + "/amount");
    $("#btnSearch").initSearch(apiPath + "chart/getchanneldaily", getColumns(), {
        success_callback: buildSummary
    });
    $("#btnExport").bind('click',function () {
        $("#btnExport").initExport(apiPath + "chart/getchanneldaily", getColumns(), {})
    });
    common.initSection(true);
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
        field: "channelMerchantNo",
        title: "渠道号",
        align: "center"
    }, {
        field: "channelDesc",
        title: "渠道简称",
        align: "center"
    }, {
        field: "payAmount",
        title: "支付总金额",
        align: "center",
        formatter: function(b, c, a) {
            return b == null ? common.fixAmount(0) : common.fixAmount(b);
        }
    },  {
        field: "payServiceFees",
        title: "支付手续费",
        align: "center",
        formatter: function(b, c, a) {
            return b == null ? common.fixAmount(0) : common.fixAmount(b);
        }
    },  {
        field: "payChannelServiceFees",
        title: "支付上游手续费",
        align: "center",
        formatter: function(b, c, a) {
            return b == null ? common.fixAmount(0) : common.fixAmount(b);
        }
    }, {
        field: "agentPayFees",
        title: "支付代理费",
        align: "center",
        formatter: function(b, c, a) {
            return b == null ? 0 : b;
        }
    }, {
        field: "settlementCount",
        title: "代付笔数",
        align: "center",
        formatter: function(b, c, a) {
            return b == null ? 0 : b;
        }
    }, {
        field: "settlementAmount",
        title: "代付总金额",
        align: "center",
        formatter: function(b, c, a) {
            return b == null ? common.fixAmount(0) : common.fixAmount(b);
        }
    }, {
        field: "settlementServiceFees",
        title: "代付手续费",
        align: "center",
        formatter: function(b, c, a) {
            return b == null ? common.fixAmount(0) : common.fixAmount(b);
        }
    }, {
        field: "settlementChannelServiceFees",
        title: "代付上游手续费",
        align: "center",
        formatter: function(b, c, a) {
            return b == null ? common.fixAmount(0) : common.fixAmount(b);
        }
    }, {
        field: "agentsettlementFees",
        title: "代付代理费",
        align: "center",
        formatter: function(b, c, a) {
            return b == null ? common.fixAmount(0) : common.fixAmount(b);
        }
    }, {
        field: "chargeCount",
        title: "充值笔数",
        align: "center",
        formatter: function(b, c, a) {
            return b == null ? 0 : b;
        }
    }, {
        field: "chargeAmount",
        title: "充值总金额",
        align: "center",
        formatter: function(b, c, a) {
            return b == null ? common.fixAmount(0) : common.fixAmount(b);
        }
    }, {
        field: "chargeServiceFees",
        title: "充值手续费",
        align: "center",
        formatter: function(b, c, a) {
            return b == null ? common.fixAmount(0) : common.fixAmount(b);
        }
    }, {
        field: "chargeChannelServiceFees",
        title: "充值上游手续费",
        align: "center",
        formatter: function(b, c, a) {
            return b == null ? common.fixAmount(0) : common.fixAmount(b);
        }
    }, {
        field: "agentchargeFees",
        title: "充值代理费",
        align: "center",
        formatter: function(b, c, a) {
            return b == null ? common.fixAmount(0) : common.fixAmount(b);
        }
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
        var k = $("<tr></tr>");
        var m = $("<tr></tr>");
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
        c.append("<td class='item'>" + "代付订单金额：" + b.settlementAmount + "</td>");
        c.append("<td class='item'>" + "充值订单金额：" + b.chargeAmount + "</td>");
        c.append("<td class='item'></td><td class='item'></td><td class='item'></td>");

        k.append("<td class='title'>手续费统计</td>").append("<td>支付手续费：" + b.payServiceFees + "</td>");
        k.append("<td class='item'>" + "支付上游手续费：" + b.payChanServiceFees + "</td>");
        k.append("<td class='item'>" + "代付手续费：" + b.settlementServiceFees + "</td>");
        k.append("<td class='item'>" + "代付上游手续费：" + b.settlementChanServiceFees + "</td>");
        k.append("<td class='item'>" + "充值手续费：" + b.chargeServiceFees + "</td>");
        k.append("<td class='item'>" + "充值上游手续费：" + b.chargeChanServiceFees + "</td>");

        m.append("<td class='title'>代理统计</td>").append("<td>支付代理费：" + b.pAgentFees + "</td>");
        m.append("<td class='item'>" + "代付代理费：" + b.sAgentFees + "</td>");
        m.append("<td class='item'>" + "充值代理费：" + b.cAgentFees + "</td>");
        m.append("<td class='item'></td><td class='item'></td><td class='item'></td>");

        $("#tabMain").parent().parent().parent().append(a.append(h.append(d.append(e).append(c).append(k).append(m))));
    }
}
