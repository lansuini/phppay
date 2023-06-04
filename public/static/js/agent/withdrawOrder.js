$(function() {
    $("#btnSearch").initSearch(apiPath + "agent/withdrawOrder/search", getColumns(),{
        success_callback: buildSummary
    });

    common.getAjax(apiPath + "getbasedata?requireItems=withdrawOrderType", function(a) {
        $("#selStatus").initSelect(a.result.withdrawOrderType, "key", "value", "请选择状态");
    });
    common.getAjax(apiPath + "agent/withdrewChannel", function(a) {
        $("#wChannelName").initSelect(a.result, "channelMerchantId", "channelName", "请选择代付渠道");
    });
    $("#btnSubmit").click(submit);
    $("#btnWithdrewSubmit").click(submitWithDraw);
    common.initDateTime('txtBeginDate');

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
        field: "nickName",
        title: "代理昵称",
        align: "center"
    }, {
        field: "platformOrderNo",
        title: "平台订单号",
        align: "center"
    },{
        field: "bankName",
        title: "提现银行",
        align: "center"
    }, {
        field: "accountName",
        title: "开户姓名",
        align: "center"
    }, {
        field: "accountNo",
        title: "银行账号",
        align: "center"
    }, {
        field: "dealMoney",
        title: "提现金额",
        align: "center",
        formatter: function(b, c, a) {
            return common.fixAmount(b)
        }
    }, {
        field: "realMoney",
        title: "实际到账金额",
        align: "center",
        formatter: function(b, c, a) {
            return common.fixAmount(b)
        }
    }, {
        field: "fee",
        title: "手续费",
        align: "center",
        formatter: function(b, c, a) {
            return common.fixAmount(b)
        }
    }, {
        field: "statusDesc",
        title: "状态",
        align: "center",
        cellStyle:function(b, c, a){
            return c.statusDesc=='拒绝'?{css:{"color":"red"}}:'';
        }
    }, {
        field: "optAdmin",
        title: "操作者",
        align: "center"
    }, {
        field: "optIP",
        title: "操作者IP",
        align: "center"
    }, {
        field: "created_at",
        title: "申请时间",
        align: "center"
    }, {
        field: "updated_at",
        title: "处理时间",
        align: "center"
    }, {
        field: "appDesc",
        title: "备注",
        align: "center"
    }, {
        field: "-",
        title: "操作",
        align: "center",
        formatter: function(b, c, a) {
           var str='';
            if(c.statusDesc=='申请中'){
                str = "<a onclick='showWithdrewEditModal(" + JSON.stringify(c) + ")'>代付</a>" +
                    "<a onclick='orderPass(\"" + c.id + "\", true)'>打款成功</a>" +
                    "<a onclick='showEditModal(" + JSON.stringify(c) + ")'>驳回</a>"
            }
            return str

        }
    }]
}

function showWithdrewEditModal(data) {
    if(data){
        $("#wAgentName").val(data.agentName);
        $("#wAccountName").val(data.accountName);
        $("#wBankName").val(data.bankName);
        $("#wId").val(data.id);
        $("#wAccountNo").val(data.accountNo);
        $("#wDealMoney").val(data.dealMoney);
        $("#wDealMoneyFee").val(data.fee);
    }

    $("#editWithdrewModal").modal()
}

function submitWithDraw() {
    $("#btnWithdrewSubmit").attr('disabled',"disabled");
    common.submit(apiPath + "agent/submitWithdrewByChannel", "editWithdrewModal", function() {
        location.href = location.href
    })
}

function showEditModal(data) {
    if(data){
        $("#txtAgentName").val(data.agentName);
        $("#txtAccountName").val(data.accountName);
        $("#txtBankName").val(data.bankName);
        $("#txtId").val(data.id);
        $("#txtAccountNo").val(data.accountNo);
        $("#txtDealMoney").val(data.dealMoney);
        $("#txtDealMoneyFee").val(data.fee);
    }

    $("#editModal").modal()
}

function submit() {
    common.submit(apiPath + "agent/orderStatus?type=nopass", "editModal", function() {
        location.href = location.href
    })
}

function orderPass(b, a) {
    myConfirm.show({
        title: "确定已打款？" ,
        sure_callback: function() {
            common.getAjax(apiPath + "agent/orderStatus?id=" + b+"&type="+'pass', function(e) {
                if (e && e.success) {
                    myAlert.success("打款成功！" , undefined, function() {
                        location.href = location.href
                    })

                } else {
                    myAlert.error(e.result);
                }
            })
        }
    })
}
function buildSummary(i) {
    if (i && i.success && i.rows.length > 0) {
        var a = $("<div class='fixed-table-summary'></div>");
        var h = $("<table></table>");
        var d = $("<tbody></tbody>");
        var e = $("<tr></tr>");
        var c = $("<tr></tr>");
        e.append("<td class='title'>笔数统计</td>").append("<td>总笔数：" + i.rows.length + "</td>");
        c.append("<td class='title'>金额统计</td>").append("<td>总操作金额：" + i.stat.dealMoneySum + "</td>");

        c.append("<td>总实际到账金额：" + i.stat.realMoneySum + "</td>");
        c.append("<td>总手续费：" + i.stat.feeSum + "</td>");
        $("#tabMain").parent().parent().parent().append(a.append(h.append(d.append(e).append(c))))
    }
}