$(function() {

    common.getAjax(apiPath + "getquickDefined?requireItems=merchantNoticeType", function(a) {
        apiPath += "merchant/notice";
        $("#selType").initSelect(a.result.merchantNoticeType, "key", "value", "请选择对象类型");
        $("#btnSearch").initSearch(apiPath, getColumns());
    });
    common.initSection()

    $("#btnAdd").click(function() {
        addNotice();
    });

    $("#btnSubmit").click(submit);

    //给选择对象下拉框赋值
    common.getAjax(apiPath + "merchant/notice/getMerchantNo", function(c) {
        if (c.success) {
            arr=c.result;
            console.log(c.result);
            var  newArr={};
            for(var j = 0,len = arr.length; j < len; j++){
                var name=arr[j];
                newArr[name]={
                    val:arr[j]
                };
            }
            dropDwon({
                id: "drop",
                myData: newArr
            });
        }
    });


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
        title: "内容",
        align: "center"
    },{
        field: "type",
        title: "发送对象类型",
        align: "center",
        formatter: function(b,c,a){
            if(b == 'optional'){
                return '局部';
            }else{
                return '全部';
            }
        }
    },{
        field: "recipient",
        title: "接收对象",
        align: "center"
    },{
        field: "userName",
        title: "发布者",
        align: "center"
    },{
        field: "published_time",
        title: "发布时间",
        align: "center"
    },{
        field: "created_at",
        title: "创建时间",
        align: "center"
    }, {
        field: "status",
        title: "公告状态",
        align: "center",
        formatter: function(b, c, a) {
            if(b == 'published'){
                return '已发布';
            } else {
                return '未发布';
            }
        }
    }, {
        field: "-",
        title: "操作",
        align: "center",
        formatter: function(b, c, a) {
           str="<a onclick='showEditModal(" + JSON.stringify(c) +  ")'>详情</a>";
            str+="<a id='accountId' onclick='delmerchantNotice(" + c.id + ")'>删除</a>";
           if(c.status=='unpublished'){
               str+="<a id='accountId' onclick='publishNotice(" + c.id + ")'>发布</a>";
           }
            return str
        }
    }]
}

function addNotice(){
    var b = $("#editModal");
    $("#selRecipient").css('display','none');
    $("#selType").val('default');
    $("#selType").bind('input propertychange',function(e){
        if($(this).val() === 'optional'){
            $("#selRecipient").css('display','block')
        }else{
            $("#selRecipient").css('display','none')
        }
    })
    b.modal()
}

function showEditModal(a) {
    var b = $("#detailModal");
    $("#contentText").html(a.content);
    b.modal()
    // common.getAjax(common.perfectUrl(apiPath + "notice?id=" + a.id))
}

function submit() {
        if ($("#title").val() == "") {
            myAlert.warning("请填写标题");
            return
        }

        if ($("#content").val() == "") {
            myAlert.warning("请填写内容");
            return
        }

        if ($("#selType").val() == "") {
            myAlert.warning("请选择发送对象");
            return
        }

    common.submit(apiPath + '/create', "editModal", function(res) {
        window.location.href = window.location.href
    })
}

//删除公告
function delmerchantNotice(id){
    myConfirm.show({
        title: "确定删除？",
        sure_callback: function() {
            $.ajax({
                url: apiPath + "/delete?id=" + id,
                async: true,
                cache: false,
                type: "get",
                dataType: "json",
                success: function(result){
                    if(result){
                        location.href = location.href
                    }else{
                        myAlert.error("操作失败")
                    }
                }
            });
        }
    })
}


//发布公告
function publishNotice(id) {
    myConfirm.show({
        title: "是否需要发布该条公告？",
        sure_callback: function() {
            $.ajax({
                url: apiPath + "/publish",
                data: {
                    noticeId: id,
                },
                async: true,
                cache: false,
                type: "get",
                dataType: "json",
                success: function(result){
                    if (result && result.success) {
                        myAlert.success("操作成功" ,undefined, function() {
                            location.href = location.href
                        })
                    } else {
                        myAlert.error(result.result);
                    }
                }
            });
        }
    })
}



