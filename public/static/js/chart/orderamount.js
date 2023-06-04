$(function() {
    $("#btnSearch").initSearch(apiPath + "/amount", getColumns(), {
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
        field: "accountDate",
        title: "财务日期",
        align: "center"
    }, {
        field: "merchantNo",
        title: "商户号",
        align: "center"
    }, {
        field: "orderAmount",
        title: "金额",
        align: "center",
        formatter: function(b, c, a) {
            return common.fixAmount(b)
        }
    }]
}
function buildSummary(i) {
    if (i && i.success && i.rows.length > 0) {
        var b = 0;
        for (var g = 0, f = i.rows.length; g < f; g++) {
            b += parseFloat(i.rows[g].orderAmount)
        }
        var a = $("<div class='fixed-table-summary'></div>");
        var h = $("<table></table>");
        var d = $("<tbody></tbody>");
        var e = $("<tr></tr>");
        var c = $("<tr></tr>");
        e.append("<td class='title'>笔数统计</td>").append("<td>总笔数：" + i.rows.length + "</td>");
        c.append("<td class='title'>金额统计</td>").append("<td>总金额：" + b.toFixed(2) + "</td>");
        $("#tabMain").parent().parent().parent().append(a.append(h.append(d.append(e).append(c))))
    }
}
;