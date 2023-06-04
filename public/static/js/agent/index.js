
$(function() {
    feeCon = {}
    // 基于准备好的dom，初始化echarts实例
    common.getAjax(apiPath + "getbasedata?requireItems=bankCode", function(a) {
        $("#bankCode").initSelect(a.result.bankCode, "key", "value", "请选择银行");
        $("#bankCard").initSearch(apiPath + "bankCard/search", getColumns())
        $("#withdrawList").initSearch(apiPath + "withdraw/search", getWithdrawColumns(),{"tabId":"tabWithDrawMain"})
        $("#unsettledAmount").initSearch(apiPath + "finance/unsettledAmount", getUnsettledColumns(),{"tabId":"tabUnsettledAmount"})
    });
    common.getAjax(apiPath + "index/searchChart", function(a) {
        feeCon = a.feeCon
        var myChart = echarts.init(document.getElementById('myCharts'));
        // 指定图表的配置项和数据
        var option = {
            title: {
                text: '商户七日流水统计',
                left: 'center',
                top: '0',
                textStyle: {
                    height: 50
                }
            },
            tooltip: {
                trigger: 'axis'
            },
            legend: {
                data:['支付','代付'],
                left: 0
            },
            grid: {
                left: '3%',
                right: '4%',
                bottom: '3%',
                containLabel: true
            },
            toolbox: {

            },
            xAxis: {
                type: 'category',
                boundaryGap: false,
                data: a.result.day
            },
            yAxis: {
                type: 'value'
            },
            series: [
                {
                    name:'支付',
                    type:'line',
                    stack: '总量',
                    data: a.result.pay
                },
                {
                    name:'代付',
                    type:'line',
                    stack: '总量',
                    data: a.result.set
                }
            ]
        };

        // 使用刚指定的配置项和数据显示图表。
        myChart.setOption(option);
    });

    $("#bankCard").click(function() {
        $("#editModal").modal('hide');
        var b = $("#listModal");
        var title = "我的银行卡";
        b.find("h4.modal-title").html(title);
        b.modal()
    });
    $("#withdrawList").click(function() {
        var b = $("#listWithDrawModal");
        var title = "提现订单";
        b.find("h4.modal-title").html(title);
        b.modal()
    });
    $("#unsettledAmount").click(function() {
        var b = $("#listUnsettledAmount");
        var title = "未结算列表";
        b.find("h4.modal-title").html(title);
        b.modal()
    });
    $("#btnAdd").click(function() {
        showEditModal()
    });
    $("#withdrawBtn").click(function() {
        $("#withdrawBankId").val('');
        $("#accountNo").val('');
        $("#withdrawPwd").val('');
        $("#withdrawDesc").val('');
        common.getAjax(apiPath + "bankCard/search", function(a) {
            $("#withdrawBankId").html("")
            $("#withdrawBankId").initSelect(a.rows, "cardId", "account", "请选择收款银行信息");
        });
        var b = $("#withdrawModal");
        b.find("h4.modal-title").html("提现");
        b.modal()
    });
    $("#withdrawMoney").change(function () {
        switch (feeCon.WAY) {
            case 'FixedValue':$("#withdrawFee").val(feeCon.VALUE);break;
            case 'Rate':$("#withdrawFee").val(Number($("#withdrawMoney").val()) * Number(feeCon.VALUE));break;
            default:$("#withdrawFee").val(Number(feeCon.VALUE) + Number($("#withdrawMoney").val()) * Number(feeCon.VALUE2));break;
        }
    })
    $("#btnSubmit").click(submit);
    $("#btnWithdrawSubmit").click(function () {
        if ($("#withdrawBankId").val() == "") {
            myAlert.warning($("#withdrawBankId").attr("placeholder"));
            return
        }
        if ($("#withdrawMoney").val() == "") {
            myAlert.warning($("#accountNo").attr("placeholder"));
            return
        }
        if ($("#withdrawPwd").val() == "") {
            myAlert.warning($("#withdrawPwd").attr("placeholder"));
            return
        }
        common.submit(apiPath + 'withdraw/apply', "withdrawModal", function(e) {
            console.log(e)
            $("#withdrawModal").modal('hide')
            location.href = location.href
            // myAlert.success($("#accountNo").attr("placeholder"));
        })
    });
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
        field: "bankName",
        title: "银行名称",
        align: "center"
    }, {
        field: "accountName",
        title: "开户名",
        align: "center"
    }, {
        field: "accountNo",
        title: "开户帐户",
        align: "center"
    }, {
        field: "province",
        title: "所在省",
        align: "center"
    }, {
        field: "city",
        title: "所在市",
        align: "center"
    }, {
        field: "district",
        title: "所在区/县",
        align: "center",
    },{
        field: "updated_at",
        title: "修改时间",
        align: "center",
    },{
        field: "created_at",
        title: "创建时间",
        align: "center",
    }, {
        field: "-",
        title: "操作",
        align: "center",
        formatter: function(b, c, a) {
            return "<a onclick='showEditModal(" + JSON.stringify(c) + ")'>修改</a><a onclick='deleteCard(\"" + c.cardId+ "\")'>删除</a>"
        }
    }]
}
function getWithdrawColumns() {
    return [{
        field: "-",
        title: "#",
        align: "center",
        formatter: function(b, c, a) {
            return a + 1
        }
    }, {
        field: "appDate",
        title: "申请时间",
        align: "center"
    }, {
        field: "dealMoney",
        title: "提现金额",
        align: "center"
    }, {
        field: "accountNo",
        title: "银行卡号",
        align: "center"
    }, {
        field: "status",
        title: "状态",
        align: "center",
        formatter: function(b, c, a) {
            switch (b) {
                case 'Apply' : return '审核中';
                case 'Adopt' : return '审核通过';
                case 'Refute' : return '驳回';
                case 'Complete' : return '已完成';
            }
        }
    }, {
        field: "prosDate",
        title: "处理时间",
        align: "center"
    }, {
        field: "realMoney",
        title: "到账金额",
        align: "center",
    },{
        field: "appDesc",
        title: "申请备注",
        align: "center",
    },{
        field: "optDesc",
        title: "操作备注",
        align: "center",
    }]
}
function getUnsettledColumns() {
    return [{
        field: "-",
        title: "#",
        align: "center",
        formatter: function(b, c, a) {
            return a + 1
        }
    }, {
        field: "platformOrderNo",
        title: "订单号",
        align: "center"
    }, {
        field: "orderMoney",
        title: "订单金额",
        align: "center"
    }, {
        field: "fee",
        title: "获取金额",
        align: "center"
    }, {
        field: "type",
        title: "订单类型",
        align: "center",
        formatter: function(b, c, a) {
            switch (b) {
                case 'pay' : return '支付订单';
                case 'recharge' : return '充值订单';
                case 'settlement' : return '代付订单';
            }
        }
    }, {
        field: "updated_at",
        title: "订单时间",
        align: "center"
    }]
}


function deleteCard(id) {
    myConfirm.show({
        title: "您确定要删除此银行卡",
        sure_callback: function() {
            common.getAjax(apiPath + "bankCard/delete" + "?cardId=" + id, function(e) {
                if (e && e.success) {
                    myAlert.success(e.result, undefined, function() {
                        $("#bankCard").click()
                    })
                }
            })
        }
    })
}
function showEditModal(a) {
    var b = $("#editModal");
    $("#listModal").modal('hide');
    if (a == undefined) {
        var title = "新增银行卡";
        $("#bankCode").val('');
        $("#accountNo").val('');
        $("#accountName").val('');
        $("#province").val('');
        $("#city").val('');
        $("#district").val('');
    } else {
        var title = "修改银行卡";
        $("#bankCode").val(a.bankCode);
        $("#accountNo").val(a.accountNo);
        $("#accountName").val(a.accountName);
        $("#province").val(a.province);
        $("#city").val(a.city);
        $("#district").val(a.district);
        $("#cardId").val(a.cardId);
    }
    b.find("h4.modal-title").html(title);
    b.modal()
}
function submit() {
    if ($("#bankCode").val() == "") {
        myAlert.warning($("#bankCode").attr("placeholder"));
        return
    }
    if ($("#accountNo").val() == "") {
        myAlert.warning($("#accountNo").attr("placeholder"));
        return
    }
    if ($("#accountName").val() == "") {
        myAlert.warning($("#accountName").attr("placeholder"));
        return
    }
    if ($("#province").val() == "") {
        myAlert.warning($("#province").attr("placeholder"));
        return
    }
    if ($("#city").val() == "") {
        myAlert.warning($("#city").attr("placeholder"));
        return
    }

    if ($("#district").val() == "") {
        myAlert.warning($("#district").attr("placeholder"));
        return
    }

    common.submit(apiPath + 'bankCard/setBank', "editModal", function() {
        $("#bankCard").click()
    })
}
;