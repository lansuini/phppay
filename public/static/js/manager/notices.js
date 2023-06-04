$(function() {

    apiPath += "manager/";
    $("#btnSearch").initSearch(apiPath + "notices", getColumns());

    // common.initSection(true)
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
        title: "标题",
        align: "center"
    }, {
        field: "created_at",
        title: "日期",
        align: "center"
    }, {
        field: "status",
        title: "状态",
        align: "center",
        formatter: function(b, c, a) {
            if(b == 'READED'){
                return '已读';
            } else {
                return '未读';
            }
        }
    }, {
        field: "-",
        title: "操作",
        align: "center",
        formatter: function(b, c, a) {
            return "<a onclick='showEditModal(" + JSON.stringify(c) +  ")'>详情</a>"
        }
    }]
}

function showEditModal(a) {
    var b = $("#detailModal");
    b.find("div.modal-body").find("input,select").val("").end().find("div.form-add").hide().end().find("div.form-edit").hide();
    b.find("h4.modal-title").html("消息详情").end().find("div.form-edit").show();

    $("#title").html(a.title);
    $("#content").html(a.content);

    b.modal()
    if(a.status == 'UNREAD'){
        common.getAjax(common.perfectUrl(apiPath + "notice?id=" + a.id));
    }
}
function reloadNotices(){
   window.location.href = contextPath + "manager/notices";
}

