$(function() {
    common.getAjax(apiPath + "getbasedata?requireItems=channel,commonStatus,switchType,settlementType", function(a) {
        apiPath += "channel/merchant/";
        $("#divSearch select[data-field=channel]").initSelect(a.result.channel, "key", "value", "上游渠道");
        $("#divSearch select[data-field=status]").initSelect(a.result.commonStatus, "key", "value", "状态");
        $("#selChannel").initSelect(a.result.channel, "key", "value", "请选择上游渠道");
        $("#selStatus").initSelect(a.result.commonStatus, "key", "value", "请选择状态");
        $(".v-form-open").initSelect(a.result.switchType, "key", "value", "请选择");
        $(".v-form-type").initSelect(a.result.settlementType, "key", "value", "剩余结算类型");
        $("#btnSearch").initSearch(apiPath + "search", getColumns())
    });
    $("#btnAdd").click(function() {
        showEditModal()
    });
    $("#btnSubmit").click(submit);

    $("#btnBalanceSubmit").click(balanceSubmit);

    $("#btnCheckSubmit").click(checkSubmit);
    // 批量设置正常
    $("#btnNormal").click(function () {
        batchStatus('btnNormal', 'Normal');
    });
    // 批量设置关闭
    $("#btnClose").click(function () {
        batchStatus('btnClose', 'Close');
    });
        
    $("#selChannel").change(function(b){
        var channelVal = $("#selChannel").find("option:selected").val();
        $('#merchant_param').html("");
        common.getAjax(apiPath+'getChannelParameter?name='+channelVal, function(a) {
            var res = a.result;
            var desc = a.desc;
            spellHtml(res,desc);
        });

    });

    common.initSection(true)
});
function getColumns() {
    return [
    {
        field: 'checked',
        checkbox: true,
        align: 'center',
        valign: 'middle'
    },
    {
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
        field: "merchantNo",
        title: "渠道商户号",
        align: "center"
    }, {
        field: "channelAccount",
        title: "上游账号",
        align: "center"
    },  {
        field: "balance",
        title: "账户余额",
        align: "center",
        formatter: function (b, c, a) {
            var e = '<label id="txtBalance'+ c.merchantId +'">'+ c.balance + '</label>'+ '<br/>' + '<label type="hidden" data-toggle="tooltip" data-placement="top" id="balance'+ c.merchantId + '"></label>'+ '<a href="javascript:;" onclick="queryBalance(\'' + c.merchantId + '\')">刷新</a> ';
            return e ;
        }
    },{
        field: "openPay",
        title: "是否开通支付",
        align: "center",
        formatter: function(b, c, a) {
            return b ? "开通" : "关闭"
        }
    }, {
        field: "openQuery",
        title: "是否开通查询",
        align: "center",
        formatter: function(b, c, a) {
            return b ? "开通" : "关闭"
        }
    }, {
        field: "openSettlement",
        title: "是否开通结算",
        align: "center",
        formatter: function(b, c, a) {
            return b ? "开通" : "关闭"
        }
    }, {
        field: "statusDesc",
        title: "状态",
        align: "center",
        cellStyle:function(b, c, a){
             return c.statusDesc=='异常'?{css:{"color":"red"}}:'';
        }
    },{
        field: "-",
        title: "操作",
        align: "center",
        formatter: function(b, c, a) {
            return "<a onclick='showEditModal(\"" + c.merchantNo + "\")'>修改</a>" +
                "<a onclick='addBalance(\"" + c.merchantNo +"\",\""+c.balance+ "\")'>调整余额</a>" +
                "<a onclick='uploadCheckFile(\"" + c.merchantNo + "\")'>对账单下载</a>"
        }
    }]
}

//批量调整状态
function batchStatus(btnid, status) {
    var data = $('#tabMain').bootstrapTable('getSelections');
    if(data.length < 1){
        myAlert.warning("请选择操作数据");
        return;
    }
    var ids = "";
    $.each(data,function (k,v) {
        ids += v.channelMerchantId+",";
    });
    ids = ids.substr(0,ids.length-1);
    document.getElementById(btnid).disabled = true;
    common.getAjax(apiPath + "batchUpdate?status=" + status + "&ids=" + ids, function(a) {
        if (a.success){
            location.href = location.href;
        }else{
            document.getElementById(btnid).disabled = false;
            myAlert.warning(c.result);
        }
    });
}

function queryBalance(mid){
    $.ajax({
        type: 'GET',
        url: apiPath + 'queryBalance?merchantId=' + mid,
        async: false,

        success: function (d) {

            if(d.status == 'Success'){
                $("#txtBalance"+mid).html(d.balance);
                showPopover($("#balance"+mid),d.balance);
            }else{
                showPopover($("#balance"+mid),d.failReason);
            }

            // if (d.success) {
            //
            // }
        }
    })
}

function showPopover(target, msg) {
    target.attr("data-original-title", msg);
    $('[data-toggle="tooltip"]').tooltip();
    target.tooltip('show');
    target.focus();
    //2秒后消失提示框
    var id = setTimeout(
        function () {
            target.attr("data-original-title", "");
            target.tooltip('hide');
        }, 3000
    );
}

function addBalance(b,c) {
    $('#balance_param').html("");
    var a = $("#addBalance");
    $("#merchNo").val(b);
    $("#balance").val(c);
    // a.find("div.modal-body").find("input,select,textarea").val("");
    a.find("h4.modal-title").html("手动充值余额：");
    a.modal()
}

//手动充值余额提交方法
function balanceSubmit(b) {
    var txtNoBalance=$("#txtNoBalance").val();
    var txtNoBalanceTrue=$("#txtNoBalanceTrue").val();
    var merchNo=$("#merchNo").val();
    var account=$("#channelAccount").val();
    var notifyOrderNumber=$("#notifyOrderNumber").val();

    if (txtNoBalance == "") {
        myAlert.warning($("#txtNoBalance").attr("placeholder")); return
    }
    if (txtNoBalanceTrue== "") {
        myAlert.warning($("#txtNoBalanceTrue").attr("placeholder")); return
    }
    if (merchNo == "") {
        myAlert.warning($("#merchNo").attr("placeholder")); return
    }
    if (notifyOrderNumber == "") {
        myAlert.warning($("#notifyOrderNumber").attr("placeholder"));  return
    }


    if (txtNoBalance != txtNoBalanceTrue) {
        myAlert.warning('两次输入金额不一致！');
        return
    }



    common.submit(apiPath + "addBalance" ,"addBalance", function() {
        location.href = location.href
    })
}

function showEditModal(b) {
    $('#merchant_param').html("");
    var a = $("#editModal");
    a.find("div.modal-body").find("input,select,textarea").val("");
    if (b == undefined) {
        a.find("h4.modal-title").html("增加上游渠道商户信息");
        $("#selStatus").parent().hide();
        $("#txtMerchantNo4Edit,#selChannel").removeAttr("disabled");
        a.modal()
    } else {
        common.getAjax(apiPath + "detail?merchantNo=" + b, function(c) {
            if (c.success) {
                a.find("h4.modal-title").html("修改上游渠道商户信息");
                $("#txtMerchantId4Edit").val(c.result.merchantId);
                $("#txtMerchantNo4Edit").val(b).attr("disabled", "disabled");
                $("#selChannel").val(c.result.channel).attr("disabled", "disabled");
                $("#txtDelegateDomain").val(c.result.delegateDomain);
                /* $("#txtParam").val(c.result.param); */
                $("#selStatus").val(c.result.status).parent().show();
                if(c.result.param != ''){
                    spellHtml(JSON.parse(c.result.param), c.desc);
                }
                a.modal()
            } else {
                myAlert.warning("上游渠道商户信息获取失败")
            }
        })
    }
}

function uploadCheckFile(b) {
    $('#txtEndDate').val("");
    $('#txtMerchandId').val(b);
    var a = $("#selectTime");
    a.find("h4.modal-title").html("对账单下载");
    a.modal()
}

function submit() {
    var a;
    if ($("#txtMerchantId4Edit").val() == "") {
        a = "insert";
        if ($("#txtMerchantNo4Edit").val() == "") {
            myAlert.warning($("#txtMerchantNo4Edit").attr("placeholder"));
            return
        }
        if ($("#selChannel").val() == "") {
            myAlert.warning($("#selChannel").find("option:first").html());
            return
        }
    } else {
        a = "update";
        if ($("#selStatus").val() == "") {
            myAlert.warning($("#selStatus").find("option:first").html());
            return
        }
    }
    // console.log(apiPath + a);
    // console.log(common.perfectUrl(apiPath + a));
    // common.postAjax(apiPath + a , "editModal")

    $.ajax({
        url:apiPath + a,
        dataType:'json',
        type:"post",
        data:common.getFields("editModal"),
        success:function (d) {
            if (d) {
                if (d.success == -1) {
                    location.href = "/logout";
                    return
                }else if(d.success == 1){
                    myAlert.success(d.result)
                }else if(d.success == 0){
                    myAlert.error(d.result)
                }
                location.reload();
            } else {
                myAlert.error(d.result.length > 0 ? d.result : "操作异常")
            }
        }
    });
}

function checkSubmit(){
    if ($("#txtEndDate").val() == "") {
        myAlert.warning($("#txtEndDate").attr("placeholder"));
        return
    }
    common.getAjax(apiPath + "uploadCheckFile?merchantNo=" + $("#txtMerchandId").val() + "&date=" + $("#txtEndDate").val(), function(c) {
        if (c.success) {
            // console.log(c);
            window.location.href = c.result;
        } else {
            myAlert.warning("该上游不支付对账单下载")
        }
    })
}

function configHtml(res,desc){
    var html = '';
    for (var v in res) {

        var param = v

        if(res[v].constructor === Object){
            if(desc[v] != undefined){
                param = desc[v]
            }
            html += '<div class="form-group clearfix">';
            html += '<label class="col-lg-2 control-label" for="txtDelegateDomain">'+ param +'：</label>';
            html += '</div>';
            var kk = v + 'Desc'
            indesc = desc[kk];
            for(var val in res[v]){
                // alert(val);
                var inparam = val
                if(desc[v] != undefined){
                    inparam = indesc[val]
                }
                html += '<div class="form-group clearfix">';
                html += '<label class="col-lg-2 control-label" for="txtDelegateDomain">'+ inparam +'：</label>';
                html += '<input type="text" class="form-control" id="'+ val +'" value="'+ res[v][val] +'" data-field="config['+ v +']['+ val +']" />';
                html += '</div>';
            }
        }else{

            html += '<div class="form-group clearfix">';
            html += '<label class="col-lg-2 control-label" for="txtDelegateDomain">'+ param +'：</label>';
            html += '<input type="text" class="form-control" id="'+ v +'" value="'+ res[v] +'" data-field="config['+ v +']" />';
            html += '</div>';

        }

        }

        $("#merchant_config").append(html);
}

function spellHtml(res,desc){
    var html = '';
    // for (var v in res) {
    //
    //     if(v != 'gateway' && v != '接口网关'){
    //         var param = v
    //         if(desc[v] != undefined){
    //             param = desc[v]
    //         }
    //         html += '<div class="form-group clearfix">';
    //         html += '<label class="col-lg-2 control-label" for="txtDelegateDomain">'+ param +'：</label>';
    //         html += '<input type="text" class="form-control" id="'+ v +'" value="'+ res[v] +'" data-field="param['+ v +']" />';
    //         html += '</div>';
    //     }
    // }
    for (var v in desc) {
        if(v != 'gateway' && v != '接口网关'){
            var param = v
            if(desc[v] != undefined){
                param = desc[v]
            }
            var val = res[v] != undefined ? res[v] : '';
            html += '<div class="form-group clearfix">';
            html += '<label class="col-lg-2 control-label" for="txtDelegateDomain">'+ param +'：</label>';
            html += '<input type="text" class="form-control" id="'+ v +'" value="'+ val +'" data-field="param['+ v +']" />';
            html += '</div>';
        }
    }
    $("#merchant_param").append(html);
}
