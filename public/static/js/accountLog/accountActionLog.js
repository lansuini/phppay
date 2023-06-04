$(function() {
    $("#btnSearch").initSearch(apiPath + "manager/getAccountActionLog", getColumns())
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
        title: "操作IP",
        align: "center",
    }, {
        field: "ipDesc",
        title: "ip描述",
        align: "center"
    }, {
        field: "status",
        title: "操作状态",
        align: "center"
    }, {
        field: "created_at",
        title: "操作时间",
        align: "center"
    }, {
        field: "action",
        title: "操作类型",
        align: "center"
    }]
}