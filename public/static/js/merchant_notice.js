$(function() {
    common.getAjax(apiPath + "getbasedata?requireItems=channel,commonStatus,switchType", function(a) {
        $("#btnSearch").initSearch(apiPath + "notice/search", getColumns())
    });
    common.initSection();

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
        field: "title",
        title: "公告标题",
        align: "center"
    }, {
        field: "content",
        title: "公告内容",
        align: "center"
    }, {
        field: "published_time",
        title: "发布时间",
        align: "center",
    }, {
        field: "-",
        title: "操作",
        align: "center",
        formatter: function(b, c, a) {
            return "<a onclick='showEditModal(" + JSON.stringify(c)+ ")'>查看</a>"
        }
    }]
};

function showEditModal(c) {
    var b = $("#detailModal");
    $("#content").html(c.content);
    b.modal();
}
