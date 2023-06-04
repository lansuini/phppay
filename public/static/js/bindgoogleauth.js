$(function() {
    $("#btnSubmit").click(submit)
});
function submit() {
    if ($("#txtLoginPwd").val() == "") {
        myAlert.warning($("#txtLoginPwd").attr("placeholder"));
        return
    }
    myConfirm.show({
        title: "确定修改？",
        sure_callback: function() {
            common.submit(apiPath + "manager/bindgoogleauth", "divContainer", function() {
                location.href = location.href
            })
        }
    })
}
;