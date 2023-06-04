function getQueryVariable(variable)
{
    var query = window.location.search.substring(1);
    var vars = query.split("&");
    for (var i=0;i<vars.length;i++) {
        var pair = vars[i].split("=");
        if(pair[0] == variable){return pair[1];}
    }
    return(false);
}

$(function() {
    feeCon = {}
    // 基于准备好的dom，初始化echarts实例
    common.getAjax(apiPath + "getbasedata?requireItems=payType,productType,rateType", function(a) {
        $("#selProType").initSelect(a.result.productType, "key", "value", "产品类型");
        $("#selPayType").initSelect(a.result.payType, "key", "value", "支付方式");
        $("#selRateType").initSelect(a.result.rateType, "key", "value", "费率类型");
        merchantNo = getQueryVariable("merchantNo")
        if(merchantNo) {
            $("#selectMerchantNo").val(merchantNo)
        }
        $("#btnSearch").initSearch(apiPath + "merchantRate/search", getColumns())
    });
    common.initSection()
    $("#btnSubmit").click(submit);
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
        },{
            field: "payTypeDesc",
            title: "产品类型",
            align: "center"
        }, {
            field: "productTypeDesc",
            title: "支付方式",
            align: "center"
        }, {
            field: "bankCodeDesc",
            title: "银行",
            align: "center"
        }, {
            field: "cardTypeDesc",
            title: "卡种",
            align: "center"
        }, {
            field: "rateTypeDesc",
            title: "费率类型",
            align: "center"
        }, {
            field: "rate",
            title: "费率值",
            align: "center",
        }, {
            field: "fixed",
            title: "固定收取",
            align: "center",
        },{
            field: "minServiceCharge",
            title: "最小手续费",
            align: "center",
        },{
            field: "maxServiceCharge",
            title: "最大手续费",
            align: "center",
        },{
            field: "beginTime",
            title: "生效时间",
            align: "center",
        },{
            field: "endTime",
            title: "失效时间",
            align: "center",
        }, {
            field: "-",
            title: "操作",
            align: "center",
            formatter: function(b, c, a) {
                return "<a onclick='showEditModal(" + JSON.stringify(c) + ")'>更改费率</a>"
            }
        }]
    }
});
function showEditModal(a) {
    var b = $("#editModal");
    var title = "更改费率";
    $("#payTypeDesc").html(a.payTypeDesc);
    $("#payType").val(a.payType);
    $("#productTypeDesc").html(a.productTypeDesc);
    $("#productType").val(a.productType);
    $("#bankCodeDesc").html(a.bankCodeDesc);
    $("#cardTypeDesc").html(a.cardTypeDesc);
    $("#rateTypeDesc").html(a.rateTypeDesc);
    $("#rate").val(a.rate);
    $("#minServiceCharge").val(a.minServiceCharge);
    $("#maxServiceCharge").val(a.maxServiceCharge);
    $("#fixed").val(a.fixed);
    $("#rateType").val(a.rateType);
    $("#rateId").val(a.rateId);
    b.find("h4.modal-title").html(title);
    b.modal()
}
function submit() {
    rateType = $("#rateType").val();
    switch (rateType) {
        case 'Rate' :
            if ($("#rate").val() == "") {
                myAlert.warning($("#rate").attr("placeholder"));
                return
            };break;
        case 'FixedValue' :
            if ($("#fixed").val() == "") {
                myAlert.warning($("#fixed").attr("placeholder"));
                return
            };break;
        default:
            if ($("#fixed").val() == "") {
                myAlert.warning($("#fixed").attr("placeholder"));
                return
            };
            if ($("#rate").val() == "") {
                myAlert.warning($("#rate").attr("placeholder"));
                return
            };
    }
    if ($("#minServiceCharge").val() == "") {
        myAlert.warning($("#minServiceCharge").attr("placeholder"));
        return
    }
    if ($("#maxServiceCharge").val() == "") {
        myAlert.warning($("#maxServiceCharge").attr("placeholder"));
        return
    }

    common.submit(apiPath + 'merchantRate/change', "editModal", function() {
        window.location.reload()
    })
}
;