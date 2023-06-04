$(function() {

    $("#btnSubmit").click(uploadFile)
});

function uploadFile() {
    var a = $("#btnFile")[0].files;
    if (a.length == 0) {
        myAlert.warning("请选择文件");
        return
    }
    $('#btnForm').submit();
    // var b = new FormData();
    // b.append("file", a[0]);
    // common.uploadFile(apiPath + "decrypt/file", b, function(c) {
    //     if (c.success == 1) {
    //         myAlert.success("操作成功");
    //         $("#fileModal").modal("hide");
    //         $("#btnSearch").click()
    //     } else {
    //         myAlert.error(c.result.length > 0 ? c.result : "操作失败")
    //     }
    // })
}
;