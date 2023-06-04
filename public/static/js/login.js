$(function() {
    $("#txtLoginName").focus();
    $("#btnSubmit").click(function() {
        // $('#audioPlay').play();
        document.getElementById('audioPlay').play()
        submit()
    });
    $("#txtLoginName,#txtLoginPwd").keyup(function(a) {
        if (a.keyCode == 13) {
            submit()
        }
    })
});
function submit() {
    if ($("#txtLoginName").val() == "") {
        myAlert.warning($("#txtLoginName").attr("placeholder"));
        return
    }
    if ($("#txtLoginPwd").val() == "") {
        myAlert.warning($("#txtLoginName").attr("txtLoginPwd"));
        return
    }
    common.getAjax(common.perfectUrl(apiPath + "manager/login", "divContainer"), function(a) {
        if (a.success == 1) {
            path = !$("#jumpUrl").val() ? "head" : $("#jumpUrl").val()
            location.href = contextPath + path
        } else {
            myAlert.error(a.result)
        }
    })
}
;