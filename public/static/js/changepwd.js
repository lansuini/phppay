$(function() {
    var a = $("div.v-breadcrumb >ol >li:last");
    apiPath += a.data("nav");
    a.html(a.data("nav") == "changeloginpwd" ? "修改登陆密码" : "修改支付密码");
    $("title").html(a.data("nav") == "changeloginpwd" ? "修改登陆密码" : "修改支付密码");
    $("#btnSubmit").click(submit)
});
function submit() {
    if ($("#txtOldPwd").val() == "") {
        myAlert.warning($("#txtOldPwd").attr("placeholder"));
        return
    }
    if ($("#txtNewPwd").val() == "") {
        myAlert.warning($("#txtNewPwd").attr("placeholder"));
        return
    }
    if ($("#txtNewPwd2").val() == "") {
        myAlert.warning($("#txtNewPwd2").attr("placeholder"));
        return
    }
    if ($("#txtNewPwd").val() != $("#txtNewPwd2").val()) {
        myAlert.warning("两次输入的新密码不一样");
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