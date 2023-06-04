$(function() {
    common.getAjax(apiPath + "getbasedata?requireItems=channel", function(a) {
        apiPath += "merchant/rechargechannel/";
        $("#selChannel").initSelect(a.result.channel, "key", "value", "充值渠道");
        $("#btnSearch").initSearch(apiPath + "search", getColumns())
    });
    $("#tabExport").initExportTable(getExportColumns(), exportTable);
    $("#btnImport").click(function() {
        showFileModal()
    });
    $("#btnSubmit").click(uploadFile)
    $("#batchUdate").click(submit)
});
function getColumns() {
    return [
    {
        "checkbox":true
    },
    {
    field: "-",
    title: "#",
    align: "center",
    formatter: function(b, c, a) {
        return a + 1
        }
    }, {
        field: "channelDesc",
        title: "充值渠道",
        align: "center"
    }, {
        field: "channelMerchantNo",
        title: "渠道商户号",
        align: "center"
    }, {
        field: "merchantNo",
        title: "商户号",
        align: "center"
    }, {
        field: "shortName",
        title: "商户简称",
        align: "center"
    }, {
        field: "payTypeDescs",
        title: "支付方式",
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
    return [
    {
        field: "merchantNo",
        title: "下游商户号"
    }, {
        field: "channel",
        title: "渠道名称"
    }, {
        field: "channelMerchantNo",
        title: "渠道商户号"
    }, {
        field: "payChannelStatus",
        title: "支付渠道状态"
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
        title: "银行卡类型",
        formatter: function(b, c, a) {
            return b != null ? b : ""
        }
    }, {
        field: "openOneAmountLimit",
        title: "是否开启单笔金额控制",
        formatter: function(b, c, a) {
            return b ? 1 : 0
        }
    }, {
        field: "oneMinAmount",
        title: "单笔最小金额",
        formatter: function(b, c, a) {
            b = parseFloat(b)
            return b.toFixed(2)
        }
    }, {
        field: "oneMaxAmount",
        title: "单笔最大金额",
        formatter: function(b, c, a) {
            b = parseFloat(b)
            return b.toFixed(2)
        }
    }, {
        field: "openDayAmountLimit",
        title: "是否开启单日累计金额控制",
        formatter: function(b, c, a) {
            return b ? 1 : 0
        }
    }, {
        field: "dayAmountLimit",
        title: "单日累计金额上限"
    }, {
        field: "openDayNumLimit",
        title: "是否开启单日累计笔数控制",
        formatter: function(b, c, a) {
            return b ? 1 : 0
        }
    }, {
        field: "dayNumLimit",
        title: "单日累计笔数上限"
    }, {
        field: "openTimeLimit",
        title: "是否开启交易时间控制",
        formatter: function(b, c, a) {
            return b ? 1 : 0
        }
    }, {
        field: "beginTime",
        title: "开始时间"
    }, {
        field: "endTime",
        title: "结束时间"
    }, {
        field: "status",
        title: "配置状态"
    }]
}
function exportTableInvoke(a) {
    $("#tabExport").bootstrapTable("refresh", {
        url: apiPath + "export?merchantNo=" + a
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
            fileName: "商户充值渠道配置" + b.find(">tbody >tr:first >td:first").html()
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
        myAlert.warning("请输入商户号");
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
    common.uploadFile(apiPath + "import", b, function(c) {
        if (c.success == 1) {
            myAlert.success("操作成功");
            $("#fileModal").modal("hide");
            $("#btnSearch").click()
        } else {
            myAlert.error(c.result.length > 0 ? c.result : "操作失败")
        }
    })
}

function showEditModal() {
    $("#batchFile").val("");
    var rows = $("#tabMain").bootstrapTable('getSelections');
    if(rows.length == 0){
        myAlert.warning("请选择要修改的配置");
        return false;
    }
    // console.log(rows);
    var ids = new Array();
    $(rows).each(function() {
        ids.push(this.setId);
    });
    $("#setIds").val(ids)
    console.log(ids);
    $("#editModal").modal()


}

function submit() {
    var rows = $("#tabMain").bootstrapTable('getSelections');
    if(rows.length == 0){
        myAlert.warning("请选择要修改的配置");
        return false;
    }
    var a = $("#batchFile")[0].files;
    if (a.length == 0) {
        myAlert.warning("请上传文件");
        return
    }
    var merchantNoes = new Array();
    $(rows).each(function() {
        merchantNoes.push(this.merchantNo);
    });
    var b = new FormData();
    b.append("file", a[0]);
    b.append("merchantNoes", merchantNoes);
    common.uploadFile(apiPath + "batchUpdate", b, function(c) {
        if (c.success == 1) {
            myAlert.success("批量更改成功");
            $("#editModal").modal("hide");
            $("#btnSearch").click()
        } else {
            myAlert.error(c.result.length > 0 ? c.result : "批量更改失败")
        }
    })
}
;