$(function() {
    common.getAjax(apiPath + "getbasedata?requireItems=productType,rateType,payType,commonStatus", function(a) {
        $("#selProductType").initSelect(a.result.productType, "key", "value", "产品类型");
        $("#selRateType").initSelect(a.result.rateType, "key", "value", "费率类型");
        $("#selPayType").initSelect(a.result.payType, "key", "value", "支付方式");
        $("#selStatus").initSelect(a.result.commonStatus, "key", "value", "状态");
        $("#btnSearch").initSearch(option.apiPath + "search", getColumns())
    });
    $("#tabExport").initExportTable(getExportColumns(), exportTable);
    $("#btnImport").click(function() {
        showFileModal()
    });
    $("#btnSubmit").click(uploadFile)
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
        field: "loginName",
        title: "代理账号",
        align: "center"
    }, {
        field: "productTypeDesc",
        title: "产品类型",
        align: "center"
    }, {
        field: "payTypeDesc",
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
        formatter: function(b, c, a) {
            b = parseFloat(b)
            return b.toFixed(6)
        }
    }, {
        field: "fixed",
        title: "固定值",
        align: "center",
        formatter: function(b, c, a) {
            b = parseFloat(b)
            return b.toFixed(2)
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
        field: "beginTime",
        title: "生效时间",
        align: "center",
        formatter: function(b, c, a) {
            return common.toDateStr("yyyy-MM-dd", b)
        }
    }, {
        field: "endTime",
        title: "失效时间",
        align: "center",
        formatter: function(b, c, a) {
            return common.toDateStr("yyyy-MM-dd", b)
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
            return "<a onclick='exportTableInvoke(\"" + c.merchantNo + "\")'>下载配置</a><a onclick='showFileModal(\"" + c.merchantNo + "\")'>更改配置</a>"
        }
    }]
}
function getExportColumns() {
    return [{
        field: "merchantNo",
        title: option.merchantNoTitle
    }
    // , {
    //     field: "loginName",
    //     title: "代理账号"
    // }
        , {
        field: "productType",
        title: "产品类型"
    }, {
        field: "payType",
        title: "支付方式"
    }, {
        field: "bankCode",
        title: "银行代码",
        formatter: function(b, c, a) {
            return b != null ? b : ""
        }
    }, {
        field: "cardType",
        title: "卡种",
        formatter: function(b, c, a) {
            return b != null ? b : ""
        }
    }, {
        field: "minAmount",
        title: "最小金额",
            formatter: function(b, c, a) {
                b = parseFloat(b)
                return b.toFixed(2) ? b.toFixed(2) : ""
            }
    }, {
        field: "maxAmount",
        title: "最大金额",
            formatter: function(b, c, a) {
                b = parseFloat(b)
                return b.toFixed(2) ? b.toFixed(2) : ""
            }
    }, {
        field: "rateType",
        title: "费率类型"
    }, {
        field: "rate",
        title: "费率值",
        formatter: function(b, c, a) {
            b = parseFloat(b)
            return b.toFixed(6)
        }
    }, {
        field: "fixed",
        title: "费率固定值",
        formatter: function(b, c, a) {
            b = parseFloat(b)
            return b.toFixed(2)
        }
    }, {
        field: "minServiceCharge",
        title: "最小手续费",
        formatter: function(b, c, a) {
            b = parseFloat(b)
            return c.rateType == "Rate" ? b.toFixed(2) : ""
        }
    }, {
        field: "maxServiceCharge",
        title: "最大手续费",
        formatter: function(b, c, a) {
            b = parseFloat(b)
            return c.rateType == "Rate" ? b.toFixed(2) : ""
        }
    }, {
        field: "beginTime",
        title: "生效时间",
        formatter: function(b, c, a) {
            return common.toDateStr("yyyy-MM-dd", b)
        }
    }, {
        field: "endTime",
        title: "失效时间",
        formatter: function(b, c, a) {
            return common.toDateStr("yyyy-MM-dd", b)
        }
    }, {
        field: "status",
        title: "状态"
    }]
}
function exportTableInvoke(a) {
    $("#tabExport").bootstrapTable("refresh", {
        url: option.apiPath + "export?merchantNo=" + a
    })
}
function exportTable(c) {
    var b = $("#tabExport");
    var a = $("#divExport");
    if (b.find(">tbody >tr.no-records-found").length > 0) {
        myAlert.warning("没有记录可以导出");
        return
    }
    if (a) {
        a.show();
        b.tableExport({
            type: "csv",
            csvUseBOM: false,
            // csvEnclosure: '',
            fileName: option.merchantType + "费率配置" + b.find(">tbody >tr:first >td:first").html()
        });
        a.hide()
    }
}
function showFileModal(a) {
    if (a != undefined) {
        $("#txtMerchantNo").val(a).attr("disabled", "disabled")
    } else {
        $("#txtMerchantNo").val("").removeAttr("disabled")
    }
    $("#btnFile").val("");
    $("#fileModal").modal()
}
function uploadFile() {
    if ($("#txtMerchantNo").val() == "") {
        myAlert.warning($("#txtMerchantNo").attr("placeholder"));
        return
    }
    var a = $("#btnFile")[0].files;
    if (a.length == 0) {
        myAlert.warning("请选择文件");
        return
    }
    var b = new FormData();
    b.append("file", a[0]);
    b.append("merchantNo", $("#txtMerchantNo").val());
    // b.append("loginName", $("#txtLoginName").val());
    common.uploadFile(option.apiPath + "import", b, function(c) {
        if (c.success == 1) {
            myAlert.success("操作成功");
            $("#fileModal").modal("hide");
            $("#btnSearch").click()
        } else {
            myAlert.error(c.result.length > 0 ? c.result : "操作失败")
        }
    })
}
;