$(function() {
    $("#btnSearch").initSearch(apiPath + "manager/bank/search", getColumns());
    $("#btnSubmit").click(submit);
    // common.initSection(false);
    common.initDateTime('txtStartTime', false);
    common.initDateTime('txtEndTime', false);
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
        field: "name",
        title: "银行名称",
        align: "center"
    }, {
        field: "code",
        title: "银行编码",
        align: "center"
    }, {
        field: "start_time",
        title: "开始时间",
        align: "center"
    }, {
        field: "end_time",
        title: "结束时间",
        align: "center"
    }, {
        field: "statusDesc",
        title: "商户状态",
        align: "center"
    }, {
        field: "created_at",
        title: "创建时间",
        align: "center"
    }, {
        field: "updated_at",
        title: "更新时间",
        align: "center"
    },{
        field: "-",
        title: "操作",
        align: "center",
        formatter: function(b, c, a) {
            return "<a onclick='showEditModal(" + JSON.stringify(c) + ")'>修改</a>"
        }
    }]
}

function showEditModal(a) {
    var b = $("#editModal");
    b.find("div.modal-body").find("input,select").val("");
    $("#txtBankName").val(a.name).attr("disabled", "disabled");
    $("#txtCode").val(a.code).attr("disabled", "disabled");
    $("#selStatus").val(a.status);
    $("#txtStartTime").val(a.start_time);
    $("#txtEndTime").val(a.end_time);
    b.modal();
}

function submit() {
    if ($("#txtBankName").val() == "") {
        myAlert.warning($("#txtBankName").attr("placeholder"));
        return
    }
    if ($("#txtCode").val() == "") {
        myAlert.warning($("#txtCode").attr("placeholder"));
        return
    }
    if ($("#selStatus").val() == "") {
        myAlert.warning("请选择商户状态");
        return
    }
    a = "manager/bank/edit";
    common.submit(apiPath + a, "editModal", function() {
        location.href = location.href;
    });
};