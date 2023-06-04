$(function () {
    apiPath += "manager";
    $("#btnSearch").initSearch(apiPath + "/messageInfo", getColumns());
    $("#btnSubmit").click(submit);

    $("#btnAdd").click(editModal);

    $("#btnEmailSubmit").click(submitEmail);
});

function getColumns() {
    return [{
        field: "-",
        title: "#",
        align: "center",
        formatter: function (b, c, a) {
            return a + 1
        }
    }, {
        field: "nickName",
        title: "姓名",
        align: "center"
    }, {
        field: "whatAPP",
        title: "whatAPP账号",
        align: "center"
    }, {
        field: "telegram",
        title: "telegram账号",
        align: "center"
    }, {
        field: "email",
        title: "电子邮箱",
        align: "center"
    }, {
        field: "skype",
        title: "skype账号",
        align: "center"
    }, {
        field: "message",
        title: "留言内容",
        align: "center"
    }, {
        field: "created_at",
        title: "留言时间",
        align: "center",
    }, {
        field: "remarks",
        title: "备注",
        align: "center"
    }, {
        field: "-",
        title: "操作",
        align: "center",
        formatter: function (b, c, a) {
            if (typeof c.remarks == "undefined" || c.remarks == null || c.remarks == "") {
                return "<a onclick='showEditModal(" + JSON.stringify(c) + ")'>备注</a>"
            }
        }
    }]
}

function showEditModal(a) {
    var b = $("#editModal");
    $("#textID").val(a.id);
    b.modal()
}
function editModal() {
    var b = $("#editEmailModal");
    common.getAjax(apiPath + "/getEmail", function(c) {
        if (c.success) {
            $("#email").val(c.result);
            b.modal()
        }
    })
}

function submit() {
    if($('#remarks').val()==''){
        myAlert.warning(($("#remarks").attr("placeholder")));
    }
    common.submit(apiPath +'/editRemarks', "editModal", function (res) {
        location.href = location.href
    })
}

function submitEmail() {
    if($('#email').val()==''){
        myAlert.warning(($("#email").attr("placeholder")));
    }
    common.submit(apiPath +'/editEmail', "editEmailModal", function (res) {
        location.href = location.href
    })
}
