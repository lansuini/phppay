$(function() {
    common.getAjax(apiPath + "getbasedata?requireItems=payOrderStatus,payType,channel", function(a) {
        $("#selOrderStatus").initSelect(a.result.payOrderStatus, "key", "value", "订单状态");
        $("#selPayType").initSelect(a.result.payType, "key", "value", "支付方式");
        $("#selChannel").initSelect(a.result.channel, "key", "value", "支付渠道");
        $("#btnSearch").initSearch(apiPath + "payorder/search", getColumns(), {
            success_callback: buildSummary
        })
    });
    $("#btnExport").bind('click',function () {
        $("#btnExport").initExport(apiPath + "payorder/search", getColumns(), {})
    })
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
        field: "merchantNo",
        title: "商户号",
        align: "center"
    }, {
        field: "shortName",
        title: "商户简称",
        align: "center"
    }, {
        field: "agentName",
        title: "所属代理",
        align: "center"
    }, {
        field: "platformOrderNo",
        title: "平台订单号",
        align: "center"
    }, {
        field: "merchantOrderNo",
        title: "商户订单号",
        align: "center"
    }, {
        field: "orderAmount",
        title: "订单金额",
        align: "center",
        formatter: function(b, c, a) {
            return common.fixAmount(b)
        }
    }, {
        field: "realOrderAmount",
        title: "订单真实金额",
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
        field: "channelServiceCharge",
        title: "上游手续费",
        align: "center",
        formatter: function(b, c, a) {
            return common.fixAmount(b)
        }
    }, {
        field: "agentFee",
        title: "代理手续费",
        align: "center",
        formatter: function(b, c, a) {
            return common.fixAmount(b)
        }
    }, {
        field: "channelDesc",
        title: "支付渠道",
        align: "center"
    }, {
        field: "channelMerchantNo",
        title: "渠道商户号",
        align: "center"
    }, {
        field: "payTypeDesc",
        title: "支付方式",
        align: "center"
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
        title: "订单支付时间",
        align: "center",
        formatter: function(b, c, a) {
            return common.toDateStr("yyyy-MM-dd HH:mm:ss", b)
        }
    }, {
        field: "callback",
        title: "回调",
        align: "center",
        formatter: function(b, c, a) {
            return  (c.callbackSuccess ? '成功' : '失败') + '(' + c.callbackLimit + ')'
        }
    }, {
        field: "-",
        title: "操作",
        align: "center",
        formatter: function(b, c, a) {
            return "<a target='_blank' href='" + contextPath + "payorder/detail?orderId=" + c.orderId + "'>详情</a>"
        }
    }]
}
/* function buildSummary(k) {
    if (k && k.success && k.rows.length > 0) {
        var b = 0;
        var f = {
            WaitPayment: {
                show: "待支付",
                num: 0,
                amount: 0
            },
            Success: {
                show: "成功",
                num: 0,
                amount: 0
            },
            Expired: {
                show: "已过期",
                num: 0,
                amount: 0
            }
        };
        for (var h = 0, g = k.rows.length; h < g; h++) {
            var i = k.rows[h];
            f[i.orderStatus].num++;
            f[i.orderStatus].amount += parseFloat(i.orderAmount);
            b += parseFloat(i.orderAmount)
        }
        var a = $("<div class='fixed-table-summary'></div>");
        var j = $("<table></table>");
        var e = $("<tbody></tbody>");
        var d = $("<tr></tr>");
        var c = $("<tr></tr>");
        d.append("<td class='title'>笔数统计</td>").append("<td>总笔数：" + k.rows.length + "</td>");
        c.append("<td class='title'>金额统计</td>").append("<td>总金额：" + b.toFixed(2) + "</td>");
        for (var i in f) {
            d.append("<td class='item'>" + f[i].show + "笔数：" + f[i].num + "</td>");
            c.append("<td class='item'>" + f[i].show + "金额：" + f[i].amount.toFixed(2) + "</td>")
        }
        $("#tabMain").parent().parent().parent().append(a.append(j.append(e.append(d).append(c))))
    }
} */


function buildSummary(k) {
    if (k && k.success && k.rows.length > 0) {
        var b = k.stat;
        /* var b = 0; */
        var f = {
            WaitPayment: {
                show: "待支付",
                num: 0,
                amount: 0
            },
            Success: {
                show: "成功",
                num: 0,
                amount: 0
            },
            Expired: {
                show: "已过期",
                num: 0,
                amount: 0
            }
        };
        var a = $("<div class='fixed-table-summary'></div>");
        var j = $("<table></table>");
        var e = $("<tbody></tbody>");
        var d = $("<tr></tr>");
        var c = $("<tr></tr>");
        d.append("<td class='title'>笔数统计</td>").append("<td>总笔数：" + b.number + "</td>");
        c.append("<td class='title'>金额统计</td>").append("<td>总金额：" + b.orderAmount + "</td>");
        /* $("#tabMain").parent().parent().parent().append(a.append(j.append(e.append(d).append(c)))); */
        d.append("<td class='item'>" + "待支付笔数：" + b.waitPaymentNumber + "</td>");
        c.append("<td class='item'>" + "待支付金额：" + b.waitPaymentAmount + "</td>")
        /* $("#tabMain").parent().parent().parent().append(a.append(j.append(e.append(d).append(c)))); */
        d.append("<td class='item'>" + "成功笔数：" + b.successNumber + "</td>");
        c.append("<td class='item'>" + "成功金额：" + b.successAmount + "</td>")
        /* $("#tabMain").parent().parent().parent().append(a.append(j.append(e.append(d).append(c)))); */
        d.append("<td class='item'>" + "已过期笔数：" + b.expiredNumber + "</td>");
        c.append("<td class='item'>" + "已过期金额：" + b.expiredAmount + "</td>")
        $("#tabMain").parent().parent().parent().append(a.append(j.append(e.append(d).append(c))));
    }
}
;