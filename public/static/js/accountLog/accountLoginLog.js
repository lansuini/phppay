$(function() {
    $("#btnSearch").initSearch(apiPath + "manager/getAccountLoginLog", getColumns())
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
        field: "loginName",
        title: "登录账号",
        align: "center"
    }, {
        field: "userName",
        title: "用户名称",
        align: "center"
    }, {
        field: "ip",
        title: "登录IP",
        align: "center",
    }, {
        field: "ipDesc",
        title: "ip描述",
        align: "center"
    }, {
        field: "status",
        title: "登录状态",
        align: "center"
    }, {
        field: "created_at",
        title: "登录时间",
        align: "center"
    }, {
        field: "remark",
        title: "备注",
        align: "center"
    }]
}
