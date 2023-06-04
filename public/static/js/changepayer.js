$(function() {
    var a = $("div.v-breadcrumb >ol >li:last");
    apiPath += a.data("nav");
    a.html("修改付款人信息");
    $("title").html("修改付款人信息");
    $("#btnSubmit").click(submit)
});
function submit() {
    if ($("#payername").val() == "") {
        myAlert.warning($("#payername").attr("placeholder"));
        return
    }
    myConfirm.show({
        title: "确定修改？",
        sure_callback: function() {
            common.submit(apiPath, "divContainer", function() {
                window.location.href = window.location.href
            })
        }
    })
}
;