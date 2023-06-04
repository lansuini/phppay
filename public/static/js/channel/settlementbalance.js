$(function() {
    common.getAjax(apiPath + "getbasedata?requireItems=channel,bankCode", function(a) {
        $("#divSearch select[data-field=channel]").initSelect(a.result.channel, "key", "value", "上游渠道");
        $("#withdrawModal select[data-field=bankCode]").initSelect(a.result.bankCode, "key", "value", "请选择");
        $("#btnSearch").initSearch(apiPath + "channel/settlementbalance/search", getColumns())
    });

    $("#btnSubmit").click(submit);
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
        field: "channel",
        title: "channel",
        align: "center"
    }, {
        field: "channelDesc",
        title: "上游渠道",
        align: "center"
    }, {
        field: "channelNo",
        title: "渠道号",
        align: "center"
    }, {
        field: "channelBalance",
        title: "账户余额",
        align: "center"
    },  {
        field: "merchantBalance",
        title: "商户余额",
        align: "center"
    },{
        field: "merchantCount",
        title: "商户数",
        align: "center"
    }, {
        field: "diffValue",
        title: "余额差值",
        align: "center"
    }, {
        field: "insTime",
        title: "最近更新",
        align: "center"
    }, {
        field: "-",
        title: "操作",
        align: "center",
        formatter: function(b, c, a) {
            // if(c.diffValue < 0 || !c.bId || userRole != 5){
            if(c.channelBalance <= 0 || !c.bId || userRole != 5){
                return "<a onclick='updateChannelBalance(\"" + c.channelId + "\")'>余额更新</a><a onclick='compareBalance(\"" + c.channelId + "\")'>余额对比</a>";
            }else{
                return "<a onclick='updateChannelBalance(\"" + c.channelId + "\")'>余额更新</a><a onclick='compareBalance(\"" + c.channelId + "\")'>余额对比</a><a onclick='withdraw(\"" + c.diffValue + "\",\"" + c.bId + "\",\"" + c.channelBalance + "\")'>取款</a>";
            }
        }
    }]
}

function myinitSearch(d, e, c) {
    var b = c.tabId || "tabMain";
    var a = {
        url: d,
        // queryParams: g,
        columns: e,
        pagination: true,
        sidePagination: "server",
        // pageList: [10, 20, 30, 50, 100],
        pageSize: 10,
        cache: false,
        striped: true,
        sortable: false,
        clickToSelect: false
    };
    $("#" + b).bootstrapTable($.extend({}, a, c));
}

function compareBalance(channelId) {
    var c = {};
    c.tabId = 'records';
    c.clickToSelect = false;
    $("#recordTable").html('<table id="records"></table>');
    myinitSearch(apiPath + "channel/settlementbalance/record/"+channelId, getRecordColumns(), c);
    $("#recordModal").modal();
}

function getRecordColumns() {
    return [{
        field: "-",
        title: "#",
        align: "center",
        formatter: function(b, c, a) {
            return a + 1
        }
    }, {
        field: "channelNo",
        title: "渠道号",
        align: "center"
    }, {
        field: "channelBalance",
        title: "账户余额",
        align: "center"
    },  {
        field: "merchantBalance",
        title: "商户余额",
        align: "center"
    },{
        field: "merchantCount",
        title: "商户数",
        align: "center"
    }, {
        field: "diffValue",
        title: "余额差值",
        align: "center"
    }, {
        field: "insTime",
        title: "最近更新",
        align: "center"
    }]
}

function updateChannelBalance(channelId){
    common.getAjax(apiPath + "channel/settlementbalance/update?channelId=" + channelId, function(c) {
        if (c.success) {
            myAlert.success(c.msg, undefined, function() {
                location.href = location.href;
            });
        } else {
            myAlert.error(c.msg);
        }
    });
}

function withdraw(diffValue, bId, channelBalance) {
    // if(diffValue < 0 || !bId){
    //     return false;
    // }
    var a = $("#withdrawModal");
    var diffMoney = diffValue * 2;
    var money = diffMoney > channelBalance ? channelBalance : diffMoney;
    // $('#highMoney').html('最高取款额度：' + money);
    $('#highMoney').html('渠道余额：' + channelBalance);
    $('#txtbId').val(bId);
    a.modal();
}

function submit() {
    var a = 'channel/settlementbalance/withdraw';
    if ($("#selBank").val() == "") {
        myAlert.warning('请选择取款银行');
        return
    }
    if ($("#txtCardNo").val() == "") {
        myAlert.warning($("#txtCardNo").attr("placeholder"));
        return
    }
    if ($("#txtUserName").val() == "") {
        myAlert.warning($("#txtUserName").attr("placeholder"));
        return
    }
    if ($("#txtMoney").val() == "") {
        myAlert.warning($("#txtMoney").attr("placeholder"));
        return
    }
    $("#btnSubmit").attr('disabled',"disabled");
    common.submit(apiPath + a, "withdrawModal", function() {
        location.href = location.href
    });
}