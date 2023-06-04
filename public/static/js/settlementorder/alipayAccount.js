$(function() {
    common.getAjax(apiPath + "getbasedata?requireItems=channel,commonStatus,switchType", function(a) {
        $("#selStatus").initSelect(a.result.commonStatus, "key", "value");
        $("#status").initSelect(a.result.commonStatus, "key", "value", "支付宝账号状态");
        $("#btnSearch").initSearch(apiPath + "settlementorder/getaccount", getColumns())
    });
    common.initSection();

    $("#btnAdd").click(function() {
        showEditModal()
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
        field: "merchantNo",
        title: "商户号",
        align: "center"
    }, {
        field: "channelAccount",
        title: "支付宝账号",
        align: "center"
    }, {
        field: "statusDesc",
        title: "状态",
        align: "center",
        cellStyle:function(b, c, a){
            return c.statusDesc=='异常'?{css:{"color":"red"}}:'';
        }
    }, {
        field: "updated_at",
        title: "修改时间",
        align: "center",
        formatter: function(b, c, a) {
            return common.toDateStr("yyyy-MM-dd HH:mm:ss", b)
        }
    }, {
        field: "-",
        title: "操作",
        align: "center",
        formatter: function(b, c, a) {
            return "<a onclick='showEditModal(\"" + c.channelMerchantNo + "\")'>修改</a>"
        }
    }]
};

function showEditModal(b) {
    $('#merchant_param').html("");
    var a = $("#editModal");
    a.find("div.modal-body").find("input,select,textarea").val("");
    if (b == undefined) {
        common.getAjax(apiPath + "settlementorder/getChannelParameter", function(c) {
            if (c.success) {
                a.find("h4.modal-title").html("增加支付宝账号");
                $("#selStatus").parent().hide();
                var res = c.result;
                var desc = c.desc;
                spellHtml(res,desc,'insert');
            } else {
                myAlert.warning("获取失败！")
            }
        });
        a.modal()
    } else {
        common.getAjax(apiPath + "settlementorder/accountdetail?channelMerchantNo=" + b, function(c) {
            if (c.success) {
                a.find("h4.modal-title").html("修改支付宝账号信息");
                $("#appAccount").val(c.result.appAccount);
                $("#channelMerchantNo").val(b).attr("disabled", "disabled");
                $("#channelMerchantId").val(c.result.channelMerchantId).attr("disabled", "disabled");
                /* $("#txtParam").val(c.result.param); */
                $("#txtDelegateDomain").val(c.result.delegateDomain);
                $("#selStatus").val(c.result.status).parent().show();
                if(c.result.param != ''){
                    spellHtml(JSON.parse(c.result.param), c.desc);
                }
                a.modal()
            } else {
                myAlert.warning("获取支付宝账号信息失败！")
            }
        })
    }
};

function submit() {
    var a;
    if ($("#channelMerchantNo").val() == "") {
        a = "insertAlipayAccount";
    } else {
        a = "update";
    }
    common.submit(apiPath+'settlementorder/' + a, "editModal", function() {
        location.href = location.href
    })
}

function spellHtml(res,desc,type){
    var html = '';

    if(type=='insert'){
        for (var v in res) {

            if(v != 'gateway' && v != '接口网关'){
                var param = v
                if(desc[v] != undefined){
                    param = desc[v]
                }
                html += '<div class="form-group clearfix">';
                html += '<label class="col-lg-2 control-label" for="txtDelegateDomain">'+ param +'：</label>';
                html += '<input type="text" class="form-control" id="'+ v +'" value="" data-field="param['+ v +']" />';
                html += '</div>';
            }
        }
    }else{
        for (var v in res) {

            if(v != 'gateway' && v != '接口网关'){
                var param = v
                if(desc[v] != undefined){
                    param = desc[v]
                }
                html += '<div class="form-group clearfix">';
                html += '<label class="col-lg-2 control-label" for="txtDelegateDomain">'+ param +'：</label>';
                html += '<input type="text" class="form-control" id="'+ v +'" value="'+ res[v] +'" data-field="param['+ v +']" />';
                html += '</div>';
            }
        }
    }

    $("#merchant_param").append(html);
}