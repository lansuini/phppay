$(function() {
    common.getAjax("/api/" + "getbasedata?requireItems=payType", function(a) {
        $("#selPayType").initSelect(a.result.payType, "key", "value", "支付方式");

        $("#btnSearch").initSearch(apiPath + "/amount", getColumns(), {
            success_callback: buildSummary
        });
        $("#btnExport").bind('click',function () {
            $("#btnExport").initExport(apiPath + "/amount", getColumns(), {})
        })
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
        title: "商户号",
        align: "center"
    }, {
        field: "shortName",
        title: "商户简称",
        align: "center"
    }, {
        field: "payTypeDesc",
        title: "支付方式",
        align: "center"
    }, {
        field: "amount",
        title: "支付订单金额",
        align: "center",
        formatter: function(b, c, a) {
            return common.fixAmount(b)
        }
    }]
}
function buildSummary(i) {
    if (i && i.success && i.rows.length > 0) {
        var b = 0;
        /* for (var g = 0, f = i.rows.length; g < f; g++) {
            b += parseFloat(i.rows[g].amount)
        } */
        var a = $("<div class='fixed-table-summary'></div>");
        var h = $("<table></table>");
        var d = $("<tbody></tbody>");
        var e = $("<tr></tr>");
        var c = $("<tr></tr>");
        e.append("<td class='title'>笔数统计</td>").append("<td>总笔数：" + i.stat.num + "</td>");
        c.append("<td class='title'>金额统计</td>").append("<td>总支付金额：" + i.stat.amount + "</td>");
        $("#tabMain").parent().parent().parent().append(a.append(h.append(d.append(e).append(c))))
    }
}
;