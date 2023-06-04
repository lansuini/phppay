$(function() {
    common.getAjax(apiPath + "getbasedata?requireItems=rateType,commonStatus", function(a) {
        $("#selRateType").initSelect(a.result.rateType, "key", "value", "费率类型");
        $("#selStatus").initSelect(a.result.commonStatus, "key", "value", "状态");
        $("#btnSearch").initSearch(option.apiPath + "search", getColumns())
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
        title: option.merchantNoTitle,
        align: "center"
    }, {
        field: option.merchantDescField,
        title: option.merchantDescTitle,
        align: "center"
    }, {
        field: "rateTypeDesc",
        title: "费率类型",
        align: "center"
    }, {
        field: "rate",
        title: "费率值",
        align: "center",
        formatter: function(b, c, a) {
            return b ? parseFloat(b).toFixed(6) : ""
        }
    }, {
        field: "afixed",
        title: "固定值",
        align: "center",
        formatter: function(b, c, a) {
            return b ? parseFloat(b).toFixed(2) : ""
        }
    },{
        field: "minServiceCharge",
        title: "最小手续费",
        align: "center",
        formatter: function(b, c, a) {
            return c.rateType == "Rate" ? common.fixAmount(b) : ""
        }
    }, {
        field: "maxServiceCharge",
        title: "最大手续费",
        align: "center",
        formatter: function(b, c, a) {
            return c.rateType == "Rate" ? common.fixAmount(b) : ""
        }
    }, {
        field: "statusDesc",
        title: "状态",
        align: "center"
    }, {
        field: "-",
        title: "操作",
        align: "center",
        formatter: function(b, c, a) {
            var temp = encodeURI(JSON.stringify(c));
            return '<a onclick="showRateModal(\'' + temp + '\')">更改配置</a>';
        }
    }]
}

function showRateModal(a) {
    a = JSON.parse(decodeURI(a));
    txtShow(a.rateType ? a.rateType : 'Mixed');
    if (a.merchantNo != undefined) {
        $("#txtMerchantNo").val(a.merchantNo).attr("disabled", "disabled")
    } else {
        $("#txtMerchantNo").val("").removeAttr("disabled")
    }
    $("#txtRate").val(a.rate);
    $("#txtFixed").val(a.afixed);
    $("#txtMin").val(a.minServiceCharge);
    $("#txtMax").val(a.maxServiceCharge);
    $("#rateModal").modal()
}

function txtShow(a) {
    $("#txtRateType").val(a);
    if(a == 'Rate'){
        $("#divRate").show();
        $("#divFixed").hide();
        $("#divMin").show();
        $("#divMax").show();
    }else if(a == 'Mixed'){
        $("#divRate").show();
        $("#divFixed").show();
        $("#divMin").hide();
        $("#divMax").hide();
    }else if(a == 'FixedValue'){
        $("#divRate").hide();
        $("#divFixed").show();
        $("#divMin").hide();
        $("#divMax").hide();
    }
}

function submit() {
    var rateType = $("#txtRateType").val();
    if (rateType == "") {
        myAlert.warning('请选择费率类型');
        return
    }
    //混合收取
    if(rateType == 'Mixed'){
        //每笔费率
        if ($("#txtRate").val() == "") {
            myAlert.warning($("#txtRate").attr("placeholder"));
            return
        }
        //固定费率
        if ($("#txtFixed").val() == "") {
            myAlert.warning($("#txtFixed").attr("placeholder"));
            return
        }
    }else if(rateType == 'Rate'){
        //每笔费率
        if ($("#txtRate").val() == "") {
            myAlert.warning($("#txtRate").attr("placeholder"));
            return
        }
        var min = $("#txtMin").val();
        var max = $("#txtMax").val();
        if (min != '' && max != '' && min > max){
            myAlert.warning('最大手续费不能小于最小手续费');
            return
        }
    }else if(rateType == 'FixedValue'){
        //固定费率
        if ($("#txtFixed").val() == "") {
            myAlert.warning($("#txtFixed").attr("placeholder"));
            return
        }
    }
    $("#btnSubmit").attr('disabled',"disabled");
    common.submit(option.apiPath + 'change', "rateModal", function() {
        location.href = location.href
    });
    $("#btnSubmit").removeAttr('disabled');
}
