$(function() {
    common.getAjax(apiPath + "getrechargeOrderdata?requireItems=rechargeOrderStatus", function(a) {
        $("#selOrderStatus").initSelect(a.result.rechargeOrderStatus, "key", "value", "订单状态");
        $("#btnSearch").initSearch(apiPath + "rechargeorder/search", getColumns(), {
            success_callback: buildSummary
        })
    });
    common.initSection()
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
        field: "platformOrderNo",
        title: "平台订单号",
        align: "center"
    }, {
        field: "orderAmount",
        title: "订单金额",
        align: "center",
        formatter: function(b, c, a) {
            return common.fixAmount(b)
        }
    }, {
        field: "serviceCharge",
        title: "平台手续费",
        align: "center",
        formatter: function(b, c, a) {
            return common.fixAmount(b)
        }
    }, {
        field: "orderStatusDesc",
        title: "订单状态",
        align: "center"
    }, {
        field: "createTime",
        title: "订单生成时间",
        align: "center",
        formatter: function(b, c, a) {
            return common.toDateStr("yyyy-MM-dd HH:mm:ss", b)
        }
    }, {
        field: "channelNoticeTime",
        title: "处理时间",
        align: "center",
        formatter: function(b, c, a) {
            return common.toDateStr("yyyy-MM-dd HH:mm:ss", b)
        }
    }
    // , {
    //     field: "-",
    //     title: "操作",
    //     align: "center",
    //     formatter: function(b, c, a) {
    //         return "<a target='_blank' href='" + contextPath + "settlementorder/detail?orderId=" + c.orderId + "'>详情</a>"
    //     }
    // }
    ]
}
function buildSummary(k) {
    if (k && k.success && k.rows.length > 0) {
        var b = k.stat;
        /* var b = 0; */
        var f = {
            Exception: {
                show: "订单异常",
                num: 0,
                amount: 0
            },
            Transfered: {
                show: "待支付",
                num: 0,
                amount: 0
            },
            Success: {
                show: "充值成功",
                num: 0,
                amount: 0
            },
            Fail: {
                show: "充值失败",
                num: 0,
                amount: 0
            }
        };
        /* for (var h = 0, g = k.rows.length; h < g; h++) {
            var i = k.rows[h];
            f[i.orderStatus].num++;
            f[i.orderStatus].amount += parseFloat(i.orderAmount);
            b += parseFloat(i.orderAmount)
        } */
        var a = $("<div class='fixed-table-summary'></div>");
        var j = $("<table></table>");
        var e = $("<tbody></tbody>");
        var d = $("<tr></tr>");
        var c = $("<tr></tr>");
        d.append("<td class='title'>笔数统计</td>").append("<td>总笔数：" + b.number + "</td>");
        c.append("<td class='title'>金额统计</td>").append("<td>总金额：" + b.orderAmount + "</td>");

        d.append("<td class='item'>" + "订单异常笔数：" + b.exceptionNumber + "</td>");
        c.append("<td class='item'>" + "订单异常金额：" + b.exceptionAmount + "</td>")
        
        d.append("<td class='item'>" + "已充值笔数：" + b.transferedNumber + "</td>");
        c.append("<td class='item'>" + "已充值金额：" + b.transferedAmount + "</td>")

        d.append("<td class='item'>" + "充值成功笔数：" + b.successNumber + "</td>");
        c.append("<td class='item'>" + "充值成功金额：" + b.successAmount + "</td>")
        
        d.append("<td class='item'>" + "充值失败笔数：" + b.failNumber + "</td>");
        c.append("<td class='item'>" + "充值失败金额：" + b.failAmount + "</td>")
        $("#tabMain").parent().parent().parent().append(a.append(j.append(e.append(d).append(c))));
        /* for (var i in f) {
            d.append("<td class='item'>" + f[i].show + "笔数：" + f[i].num + "</td>");
            c.append("<td class='item'>" + f[i].show + "金额：" + f[i].amount.toFixed(2) + "</td>")
        }
        $("#tabMain").parent().parent().parent().append(a.append(j.append(e.append(d).append(c)))) */
    }
}
;