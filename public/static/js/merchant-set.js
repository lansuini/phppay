$(function() {
    $("#btnSubmitSet").click(submitSet);
    $("#txtOneSettlementMaxAmount,#txtWorkdaySettlementRate,#txtWorkdaySettlementMaxAmount,#txtHolidaySettlementRate,#txtHolidaySettlementMaxAmount").bind("input propertychange", function() {
        if (!common.isDecimal($(this).val(), 6)) {
            $(this).val("")
        }
    });
    $("#txtSettlementTime").bind("input propertychange", function() {
        if (!common.isInt($(this).val(), true)) {
            $(this).val("")
        }
    })
});
function showSetModal(a) {
    common.getAjax(apiPath + "detail?merchantNo=" + a, function(b) {
        $("#txtMerchantNo4Set").val(a);
        showOpenControl("selOpenPay", b.result.openPay);
        showOpenControl("selOpenSettlement", b.result.openSettlement);
        showOpenControl("selOpenAliSettlement", b.result.openAliSettlement);
        showOpenControl("selOpenAutoSettlement", b.result.openAutoSettlement);
        // showOpenControl("selOpenEntrustSettlement", b.result.openEntrustSettlement);
        // showOpenControl("selOpenWorkdaySettlement", b.result.openWorkdaySettlement);
        // showOpenControl("selOpenHolidaySettlement", b.result.openHolidaySettlement);
        $("#txtOneSettlementMaxAmount").val(b.result.oneSettlementMaxAmount);
        // $("#selWorkdaySettlementType").val(b.result.workdaySettlementType);
        // $("#txtWorkdaySettlementRate").val(b.result.workdaySettlementRate);
        // $("#txtWorkdaySettlementMaxAmount").val(b.result.workdaySettlementMaxAmount);
        // $("#selHolidaySettlementType").val(b.result.holidaySettlementType);
        // $("#txtHolidaySettlementRate").val(b.result.holidaySettlementRate);
        // $("#txtHolidaySettlementMaxAmount").val(b.result.holidaySettlementMaxAmount);
        $("#txtD0SettlementRate").val(b.result.D0SettlementRate);
        $("#txtSettlementTime").val(b.result.settlementTime);
        $("#selSetSettlementType").val(b.result.settlementType);
        $("#setModal").modal();
    })
}
function showOpenControl(a, b) {
    $("#" + a).val(b ? "1" : "0")
}
function submitSet() {
    if ($("#selOpenPay").val() == "") {
        myAlert.warning("请选择支付开关");
        return
    }
    if ($("#selOpenSettlement").val() == "") {
        myAlert.warning("请选择结算开关");
        return
    }

    if ($("#selOpenAutoSettlement").val() == "") {
        myAlert.warning("请选择自动代付开关");
        return
    }

    if ($("#selOpenSettlement").val() == "1" && $("#txtOneSettlementMaxAmount").val() == "") {
        myAlert.warning("请输入单卡单日最大结算金额");
        return
    }
    if ($("#selOpenEntrustSettlement").val() == "") {
        myAlert.warning("请选择直连委托结算开关");
        return
    }
    if (!checkSettlement("WorkdaySettlement", "工作日垫资结算")) {
        return
    }
    if (!checkSettlement("HolidaySettlement", "节假日垫资结算")) {
        return
    }
    if ($("#txtSettlementTime").val() == "") {
        myAlert.warning("请输入结算时间");
        return
    }
    var a = parseInt($("#txtSettlementTime").val(), 10);
    if (a < 0 || a > 2359) {
        myAlert.warning("请正确输入结算时间<br>值范围：0-2359");
        return
    }

    if ($("#selSetSettlementType").val() == "") {
        myAlert.warning($("#selSetSettlementType").find("option:first").html());
        return
    }

    common.submit(apiPath + "resetset", "setModal", function() {
        $("#setModal").modal("hide");
        $("#btnSearch").click()
    })
}
function checkSettlement(c, a) {
    if ($("#selOpen" + c).val() == "") {
        myAlert.warning("请选择" + a + "开关");
        return false
    }
    if ($("#selOpen" + c).val() == "1") {
        if ($("#sel" + c + "Type").val() == "") {
            myAlert.warning("请选择" + a + "的剩余结算类型");
            return false
        }
        if ($("#txt" + c + "Rate").val() == "") {
            myAlert.warning("请输入" + a + "的垫资比例");
            return false
        }
        if ($("#txt" + c + "MaxAmount").val() == "") {
            myAlert.warning("请输入" + a + "的最大垫资金额");
            return false
        }
    }
    if ($("#txt" + c + "Rate").val() != "") {
        var b = parseFloat($("#txt" + c + "Rate").val());
        if (b < 0 || b > 1) {
            myAlert.warning("请正确输入" + a + "的垫资比例<br>值范围：0-1");
            return false
        }
    }
    return true
}
;