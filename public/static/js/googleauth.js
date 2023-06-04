$(function() {
    $("#txtLoginName").focus();
    $("#btnSubmit").click(function() {
        submit()
    });
    $("#txtLoginName,#txtLoginPwd").keyup(function(a) {
        if (a.keyCode == 13) {
            submit()
        }
    })
});
function submit() {
    if ($("#txtLoginPwd").val() == "") {
        myAlert.warning($("#txtLoginPwd").attr("placeholder"));
        return
    }
    common.getAjax(common.perfectUrl(apiPath + "manager/googleauth", "divContainer"), function(a) {
        if (a.success == 1) {
            location.href = contextPath + "payorder"
        } else {
            myAlert.error(a.result)
        }
    })
}
;