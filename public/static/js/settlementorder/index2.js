$(function() {
    common.getAjax(apiPath + "getbasedata?requireItems=settlementOrderStatus,bankCode", function(a) {
        $("#selOrderStatus").initSelect(a.result.settlementOrderStatus, "key", "value", "订单状态");
        $("#selBankCode").initSelect(a.result.bankCode, "key", "value", "收款银行");
        $("#btnSearch").initSearch(apiPath + "settlementorder/search", getColumns(), {
            success_callback: buildSummary
        })
        $("#btnExport").bind('click',function () {
            $("#btnExport").initExport(apiPath + "settlementorder/search", getColumns(), {})
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
        field: "serviceCharge",
        title: "手续费",
        align: "center",
        formatter: function(b, c, a) {
            return common.fixAmount(b)
        }
    }, {
        field: "bankCodeDesc",
        title: "收款银行",
        align: "center"
    }, {
        field: "bankAccountNo",
        title: "收款卡号",
        align: "center"
    }, {
        field: "bankAccountName",
        title: "收款人姓名",
        align: "center"
    }, {
        field: "orderReason",
        title: "用途",
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
        title: "处理时间",
        align: "center",
        formatter: function(b, c, a) {
            return common.toDateStr("yyyy-MM-dd HH:mm:ss", b)
        }
    }, {
        field: "failReason",
        title: "失败原因",
        align: "center",
    }]
}
function buildSummary(k) {
    if (k && k.success && k.rows.length > 0) {
        var b = k.stat;
        var rate = k.rateArr;
        var f = {
            Exception: {
                show: "订单异常",
                num: 0,
                amount: 0
            },
            Transfered: {
                show: "已划款",
                num: 0,
                amount: 0
            },
            Success: {
                show: "划款成功",
                num: 0,
                amount: 0
            },
            Fail: {
                show: "划款失败",
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
        var h = $("<tr></tr>");
        d.append("<td class='title'>笔数统计</td>").append("<td>总笔数：" + b.number + "</td>");
        c.append("<td class='title'>金额统计</td>").append("<td>总金额：" + b.orderAmount + "</td>");

        d.append("<td class='item'>" + "订单异常笔数：" + b.exceptionNumber + "</td>");
        c.append("<td class='item'>" + "订单异常金额：" + b.exceptionAmount + "</td>")
        
        d.append("<td class='item'>" + "已划款笔数：" + b.transferedNumber + "</td>");
        c.append("<td class='item'>" + "已划款金额：" + b.transferedAmount + "</td>")

        d.append("<td class='item'>" + "划款成功笔数：" + b.successNumber + "</td>");
        c.append("<td class='item'>" + "划款成功金额：" + b.successAmount + "</td>")
        
        d.append("<td class='item'>" + "划款失败笔数：" + b.failNumber + "</td>");
        c.append("<td class='item'>" + "划款失败金额：" + b.failAmount + "</td>")
        c.append("<td class='item'>" + "手续费：" + b.serviceCharge + "</td>")

        h.append("<td class='title'>费率</td>");
        h.append("<td class='item'>"+  "(" + rate[0].rate + ")</td>");
        $("#tabMain").parent().parent().parent().append(a.append(j.append(e.append(d).append(c).append(h))));
        // for (var i in rate) {
        //     h.append("<td class='item'>"+  rate[i].payType + "(" + rate[i].rate + ")</td>");
        // }

        /* d.append("<td class='title'>笔数统计</td>").append("<td>总笔数：" + k.rows.length + "</td>");
        c.append("<td class='title'>金额统计</td>").append("<td>总金额：" + b.toFixed(2) + "</td>");
        for (var i in f) {
            d.append("<td class='item'>" + f[i].show + "笔数：" + f[i].num + "</td>");
            c.append("<td class='item'>" + f[i].show + "金额：" + f[i].amount.toFixed(2) + "</td>")
        }
        $("#tabMain").parent().parent().parent().append(a.append(j.append(e.append(d).append(c)))) */
    }
}
;