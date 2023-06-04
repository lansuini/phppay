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
    common.initSection();

    common.initDateTime('txtCreateBeginTime','true','begin');
    common.initDateTime('txtCreateEndTime','true');
    $("#btnFreshExport").click(btnFreshAuto);
    $("#offlineSettleSubmit").click(offlineSettleSubmit);

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
        align: "center",
        formatter: function(b, c, a) {
            return b + '\n' + '('+ c.shortName +')'
        }
    }, {
        field: "channelMerchantNo",
        title: "渠道号",
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
            return common.fixAmount(b) + '<button class="copyTextBut" data-clipboard-text="'+ b +'">复制</button>'
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
        field: "bankCodeDesc",
        title: "收款银行",
        align: "center",
        formatter: function(b, c, a) {
            return b + '<button class="copyTextBut" data-clipboard-text="'+ b +'">复制</button>'
        }
    }, {
        field: "bankAccountNo",
        title: "收款卡号",
        align: "center",
        formatter: function(b, c, a) {
            return b + '<button class="copyTextBut" data-clipboard-text="'+ b +'">复制</button>'
        }
    }, {
        field: "bankAccountName",
        title: "收款人姓名",
        align: "center",
        formatter: function(b, c, a) {
            return b + '<button class="copyTextBut" data-clipboard-text="'+ b +'">复制</button>'
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
    }, {
        field: "-",
        title: "操作",
        align: "center",
        fixed: "right",
        formatter: function(b, c, a) {
            // return "<a href='javascript:;' class='btn btn-xs blue' onclick=\"EditViewById('" + c.orderId + "')\" >线下代付</a>"
            if(c.orderStatus == 'Transfered' && c.orderType == 'manualSettlement'){
                if(c.isLock == 1){
                    return "<button class=\"btn btn-danger \" onclick='unlock(\"" + c.orderId +"\")'>解除锁定</button>"+
                        "<a onclick='offlineSettlement(\"" + c.orderId +"\",\""+c.orderId+ "\")'>线下代付</a>" +
                        "<a target='_blank' href='" + contextPath + "settlementorder/detail?orderId=" + c.orderId + "'>详情</a>"+
                        "<a onclick='systemSettlement(\"" + c.orderId +"\" ,\""+c.orderAmount+ "\")'>系统代付</a>"
                }else{
                    return "<button class=\"btn btn-success \" onclick='lock(\"" + c.orderId +"\")'>锁定订单</button>"+
                        "<a onclick='offlineSettlement(\"" + c.orderId +"\",\""+c.orderId+ "\")'>线下代付</a>" +
                        "<a target='_blank' href='" + contextPath + "settlementorder/detail?orderId=" + c.orderId + "'>详情</a>"+
                        "<a onclick='systemSettlement(\"" + c.orderId +"\" ,\""+c.orderAmount+ "\")'>系统代付</a>"
                }

            }else{
                return "<a target='_blank' href='" + contextPath + "settlementorder/detail?orderId=" + c.orderId + "'>详情</a>";
            }

        }
    }]
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
        $("#tabMain").parent().parent().parent().append(a.append(j.append(e.append(d).append(c))));
        /* for (var i in f) {
            d.append("<td class='item'>" + f[i].show + "笔数：" + f[i].num + "</td>");
            c.append("<td class='item'>" + f[i].show + "金额：" + f[i].amount.toFixed(2) + "</td>")
        }
        $("#tabMain").parent().parent().parent().append(a.append(j.append(e.append(d).append(c)))) */
    }
    var clipboard = new ClipboardJS('.copyTextBut');

    clipboard.on('success', function(e) {
        // console.info('Action:', e.action);
        // console.info('Text:', e.text);
        // console.info('Trigger:', e.trigger);
        e.clearSelection();
    });

    clipboard.on('error', function(e) {
        console.error('Action:', e.action);
        console.error('Trigger:', e.trigger);
    });
}

var timerAuto=null; //定时器返回值，主要用于关闭定时器

function btnFreshAuto(){
    console.log($("#btnFreshExport").html() == '自动刷新')
    if($("#btnFreshExport").html() == '自动刷新'){
        $("#btnFreshExport").html("暂停")
        timerAuto=setInterval(function(){ //打开定时器
            $("#btnSearch").click();
        },12000); //2000为轮播的时间
    }else {
        $("#btnFreshExport").html("自动刷新")
        clearInterval(timerAuto)
    }
}

function lock(b) {
    myConfirm.show({
        title: "确定锁定订单？",
        sure_callback: function() {
            $.ajax({
                url: apiPath + "settlementorder/lock?orderId=" + b,
                async: false,
                cache: false,
                type: "post",
                dataType: "json",
                success: function(result){
                    if(result){
                        if (result.success == -1) {
                            location.href = "/logout";
                            return
                        }else if(result.success == 1){
                            myAlert.success(result.result,'确定',function() {
                                location.href = location.href
                            })
                        }else if(result.success == 0){
                            myAlert.error(result.result,'确定',function() {
                                location.href = location.href
                            })
                        }
                    }else{
                        myAlert.error("操作失败",'确定',function() {
                            location.href = location.href
                        })
                    }
                }
            });
        }
    })

}

function unlock(b) {
    myConfirm.show({
        title: "确定解锁订单？",
        sure_callback: function() {
            $.ajax({
                url: apiPath + "settlementorder/unlock?orderId=" + b,
                async: false,
                cache: false,
                type: "post",
                dataType: "json",
                success: function(result){
                    if(result){
                        if (result.success == -1) {
                            location.href = "/logout";
                            return
                        }else if(result.success == 1){
                            myAlert.success(result.result,'确定',function() {
                                location.href = location.href
                            })
                        }else if(result.success == 0){
                            myAlert.error(result.result,'确定',function() {
                                location.href = location.href
                            })
                        }
                        // location.href = location.href
                    }else{
                        myAlert.error("操作失败",'确定',function() {
                            location.href = location.href
                        })
                    }
                }
            });
        }
    })
}

function offlineSettlement(b,d) {
    var a = $("#offlineSettlement");
    common.getAjax(apiPath + "settlementorder/detail?orderId=" + b, function(c) {
        if (c.success) {
            a.find("h4.modal-title").html("确认线下代付：");
            $("#orderId").val(b);
            $("#platformOrderNo").val(c.result.platformOrderNo);
            $("#merchantOrderNo").val(c.result.merchantOrderNo);
            $("#orderAmount").val(c.result.orderAmount);
            // $("#txtMerchantNo4Edit").val(b).attr("disabled", "disabled");
            // $("#selChannel").val(c.result.channel).attr("disabled", "disabled");
            $("#channelServiceCharge").val(c.result.channelServiceCharge);
            /* $("#txtParam").val(c.result.param); */
            // $("#selStatus").val(c.result.status).parent().show();

            a.modal()
        } else {
            myAlert.warning("获取订单信息失败")
        }
    });
    // a.find("div.modal-body").find("input,select,textarea").val("");

}

function offlineSettleSubmit(){
    if ($("#orderId").val() == "") {
        myAlert.warning("请重新选择订单");
        return
    }
    if ($("#channelServiceCharge").val() == "") {
        myAlert.warning("请填写手游手续费");
        return
    }
    console.log($("#selectOrderStatus").val());
    if ($("#selectOrderStatus").val() == "") {
        myAlert.warning("请选择代付状态");
        return
    }

    common.submit(apiPath + "settlementorder/offlineSettlement" ,"offlineSettlement", function() {
        location.href = location.href
    })

    // common.submit(apiPath + "resetset", "setModal", function() {
    //     $("#setModal").modal("hide");
    //     $("#btnSearch").click()
    // })
}

function systemSettlement(orderId,orderAmount){
    myConfirm.show({
        title: "确定把该订单推送到系统代付？",
        text:"金额：" + orderAmount,
        sure_callback: function() {
            $.ajax({
                url:apiPath +  "settlementorder/systemSettlement/" + orderId,
                dataType:'json',
                type:"post",
                async: false,
                success:function (d) {
                    if (d) {
                        if (d.success == -1) {
                            location.href = "/logout";
                            return
                        }else if(d.success == 1){
                            myAlert.success(d.result,'确定',function() {
                                location.href = location.href
                            })
                        }else if(d.success == 0){
                            myAlert.error(d.result,'确定',function() {
                                location.href = location.href
                            })
                        }
                    } else {
                        myAlert.error(d.result.length > 0 ? d.result : "操作异常",'确定',function() {
                            location.href = location.href
                        })
                    }
                }
            });
        }

    })
}



