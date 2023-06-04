$(function() {
    common.setNav();
    jQuery.fn.extend({
        initSearch: function(d, e, c) {
            if (c == undefined) {
                c = {}
            }
            var b = c.tabId || "tabMain";
            var f = c.searchContainerId || "divSearch";
            var sort = '';
            var sortorder = '';
            var a = {
                url: d,
                queryParams: g,
                columns: e,
                pagination: true,
                sidePagination: "server",
                pageList: [10, 20, 30, 50, 100],
                pageSize: 20,
                cache: false,
                striped: true,
                sortable: true,
                clickToSelect: true,
                onResetView: function() {
                    $("#" + b).parent().parent().parent().find(">div.fixed-table-summary").remove()
                },
                onLoadSuccess: function(h) {
                    if (c.success_callback && c.success_callback instanceof Function) {
                        c.success_callback(h)
                    }
                },
                onSort:function(name,order)
                {
                    sort = name;
                    sortorder = order;
                    $("#" + b).bootstrapTable('refreshOptions', {
                        sortName:name,
                        sortOrder:order
                    });
                }
            };
            $("#" + b).bootstrapTable($.extend({}, a, c));
            $(this).click(function() {
                $("#" + b).bootstrapTable('refreshOptions', {pageNumber: 1});
                $("#" + b).bootstrapTable("selectPage", 1)
            });
            function g(i) {
                var h = common.getFields(f);
                h.limit = i.limit;
                h.offset = i.offset;
                h.sort = sort;
                h.order = sortorder;
                return h
            }
        },
        initExport: function(d, e, c) {
            if (c == undefined) {
                c = {}
            }
            var b = c.tabId || "tabMain";
            var f = c.searchContainerId || "divSearch";
            url = d + '?' + myFunction.parseParams(g())
            window.location.href = url
            function g() {
                var h = common.getFields(f);
                h.export = 1;
                return h
            }
        },
        initSelect: function(h, d, a, e, i, g) {
            var b = $(this);
            if (e) {
                b.append("<option value=''>" + e + "</option>")
            }
            for (var f = 0, c = h.length; f < c; f++) {
                b.append("<option value='" + h[f][d] + "'>" + h[f][a] + "</option>")
            }
            b.find("option" + (i ? "[value='" + i + "']" : ":first")).attr("selected", true);
            if (g && g instanceof Function) {
                g()
            }
        },
        initExportTable: function(a, b) {
            $(this).bootstrapTable({
                columns: a,
                sidePagination: "server",
                pagination: true,
                cache: false,
                sortable: false,
                onLoadSuccess: function(c) {
                    b()
                }
            })
        },
        initSelectToggle: function(a, b) {
            $(this).change(function() {
                var c = $("#" + a);
                c.val("").attr("disabled", "disabled");
                if ($(this).val() == b) {
                    c.removeAttr("disabled")
                }
            });
            $("#" + a).attr("disabled", "disabled")
        }
    })
});
var myAlert = {
    success: function(b, a, c) {
        this.show("success", b, a, c)
    },
    warning: function(b, a, c) {
        this.show("warning", b, a, c)
    },
    error: function(b, a, c) {
        this.show("error", b, a, c)
    },
    show: function(a, c, b, d) {
        swal({
            title: c,
            type: a ? a : "success",
            confirmButtonText: b ? b : "确定",
            allowOutsideClick: false
        }).then(function() {
            if (d && d instanceof Function) {
                d()
            }
        })
    }
};
var myConfirm = {
    show: function(a) {
        swal({
            title: a.title,
            text: a.text,
            type: "warning",
            allowOutsideClick: false,
            showCancelButton: true,
            confirmButtonText: a.confirmButtonText ? a.confirmButtonText : "确定",
            cancelButtonText: a.cancelButtonText ? a.cancelButtonText : "取消"
        }).then(function(b) {
            if (a.sure_callback && a.sure_callback instanceof Function && b.value) {
                a.sure_callback()
            } else {
                if (a.cancel_callback && a.cancel_callback instanceof Function && b.dismiss == "cancel") {
                    a.cancel_callback()
                }
            }
        })
    }
};
var common = {
    getAjax: function(b, c, a) {
        $.ajax({
            url: b,
            cache: false,
            type: "get",
            dataType: "json",
            contentType: "application/json",
            success: function(d) {
                if (d) {
                    if (d.success == -1) {
                        location.href = "/logout";
                        return
                    }
                    if (c && c instanceof Function) {
                        c(d)
                    }
                } else {
                    myAlert.error(d.result.length > 0 ? d.result : "操作异常")
                }
            },
            error: function(d) {
                if (a && a instanceof Function) {
                    a(d)
                }
            }
        })
    },
    postAjax: function(b, c, a) {
        $.ajax({
            url: b,
            cache: false,
            type: "post",
            dataType: "json",
            contentType: "application/json",
            success: function(d) {
                if (d) {
                    if (d.success == -1) {
                        location.href = "/logout";
                        return
                    }
                    if (c && c instanceof Function) {
                        c(d)
                    }
                } else {
                    myAlert.error(d.result.length > 0 ? d.result : "操作异常")
                }
            },
            error: function(d) {
                if (a && a instanceof Function) {
                    a(d)
                }
            }
        })
    },
    submit: function(b, a, c, f) {
        this.getAjax(this.perfectUrl(b, a), function(d) {
            if (d.success) {
                myAlert.success("操作成功！", undefined, function() {
                    if (c && c instanceof Function) {
                        c()
                    }
                })
            } else {
                if(f) {
                    f()
                }
                myAlert.error(d.result.length > 0 ? d.result : "操作异常")
            }
        }, function(d) {
            if(f) {
                f()
            }
            myAlert.error("操作失败")
        })
    },
    uploadFile: function(b, c, d, a) {
        $.ajax({
            url: b,
            type: "post",
            data: c,
            processData: false,
            contentType: false,
            success: function(e) {
                if (e) {
                    try {
                        var success = e.success;
                     }
                     catch(err){
                        e = JSON.parse(e);
                        var success = e.success;
                     }
                    // e = JSON.parse(e);
                    if (success == -1) {
                        location.href = "/logout";
                        return
                    }
                    if (d && d instanceof Function) {
                        d(e)
                    }

                    /* location.href = location.href; */
                } else {
                    myAlert.error("操作异常")
                }
            },
            error: function(f) {
                if (a && a instanceof Function) {
                    a(f)
                }
            }
        })
    },
    getQuery: function(d) {
        var c = location.search;
        if (c.length > 0) {
            c = decodeURI(c);
            var a = c.substring(1).split("&");
            d += "=";
            for (var b = 0; b < a.length; b++) {
                if (a[b].indexOf(d) == 0) {
                    return a[b].substring(d.length)
                }
            }
        }
        return undefined
    },
    perfectUrl: function(c, b) {
        var d = this.getFields(b);
        var a = new Array();
        for (var e in d) {
            a.push(e + "=" + encodeURIComponent(d[e]))
        }
        if (a.length > 0) {
            c += (c.indexOf("?") == -1 ? "?" : "&") + a.join("&")
        }
        return c
    },
    getFields: function(c) {
        var b = {};
        if (c) {
            var a = $("#" + c + " *[data-field]");
            for (var d = 0; d < a.length; d++) {
                var e = $(a[d]);
                if (e.attr("type") == "checkbox") {
                    if (e.is(":checked")) {
                        b[e.attr("data-field")] = "";
                    }
                } else {
                    if (e.val() != "") {
                        b[e.attr("data-field")] = $.trim(e.val());
                    }
                }
            }
        }
        return b
    },
    toDateStr: function(f, d) {
        try {
            if (d == null) {
                return ""
            }
            var b = new Date(d.time);
            var a = {
                yyyy: b.getFullYear(),
                MM: b.getMonth() + 1,
                dd: b.getDate(),
                HH: b.getHours(),
                mm: b.getMinutes(),
                ss: b.getSeconds()
            };
            for (var g in a) {
                if (new RegExp("(" + g + ")").test(f)) {
                    f = f.replace(RegExp.$1, (a[g] < 10 ? "0" : "") + a[g])
                }
            }
            return f
        } catch (c) {
            return ""
        }
    },
    fixAmount: function(a) {
        if (a == undefined) {
            return ""
        }
        a = parseFloat(a);
        a = a.toFixed(6).toString();
        return a.substring(0, a.indexOf(".") + 3)
    },
    isInt: function(c, a) {
        var b = a ? /(^0$)|(^[1-9]{1}\d*$)/ : /^[1-9]{1}\d*$/;
        return c != undefined && b.test(c)
    },
    isDecimal: function(c, a) {
        var b = a == undefined ? /^\d{1,}(.\d{0,2})?$/ : /^\d{1,}(.\d{0,6})?$/;
        if (c != undefined) {
            if (c.toString().indexOf(".") == -1) {
                return this.isInt(c, true) && b.test(c)
            } else {
                return b.test(c)
            }
        }
        return false
    },
    logout: function() {
        myConfirm.show({
            title: "您确定要退出系统？",
            sure_callback: function() {
                location.href = "/logout";
            }
        })
    },
    modifyPwd: function(action) {
        $("#modifyPwdAction").val(action)
        switch(action){
            case 'modifyLoginPwd':
                ll = '您确定要修改支付密码';
                tt = '修改支付密码';
                break;
            case 'modifyPayPwd':
                ll = '您确定要修改登陆密码';
                tt = '修改登陆密码';
                break;
            case 'modifyCheckPwd':
                ll = '您确定要修改审核密码';
                tt = '修改审核密码';
                break;
            default:
                myAlert.warning("非法类型出错！");
                return
        };
        myConfirm.show({
            title: ll,
            sure_callback: function() {
                var b = $("#modifyPwdModal");
                var b = $("#modifyPwdModal").attr('tabindex',999999);
                var title = tt;
                b.find("h4.modal-title").html(title);
                b.modal()
            }
        })
    },
    btnModifyPwd:function(url) {
        action = $("#modifyPwdAction").val()
        oldPwd = $("#modifyOldPwd").val()
        newPwd = $("#modifyNewPwd").val()
        newPwd2 = $("#modifyRepNewPwd").val()
        if (action == "" || oldPwd == "") {
            myAlert.warning($("#modifyOldPwd").attr("placeholder"));
            return
        }
        if (newPwd == "") {
            myAlert.warning($("#modifyNewPwd").attr("placeholder"));
            return
        }
        if (newPwd2 == "") {
            myAlert.warning($("#modifyRepNewPwd").attr("placeholder"));
            return
        }
        if(oldPwd == newPwd){
            myAlert.warning("新旧密码必须不一样");
            return
        }
        if(newPwd != newPwd2){
            myAlert.warning("两次新密码输入不一致");
            return
        }
        str = 'oldPwd=' + oldPwd + '&' + 'newPwd=' + newPwd
        this.getAjax(url + action + '? ' + str, function(d) {
            if(d.success) {
                $("#modifyPwdModal").modal('hide')
                $("#modifyOldPwd").val('')
                $("#modifyNewPwd").val('')
                $("#modifyRepNewPwd").val('')
                myAlert.success(d.result)
                if (action == 'modifyLoginPwd') {
                    location.href = "/logout";
                }
            }else {
                myAlert.warning(d.result);
            }
        });
    },
    openNewPage: function(a) {
        var b = window.open();
        b.location = a;
    },
    setNav: function() {
        var d = $("div.v-breadcrumb >ol >li:last").data("nav");
        if (d) {
            var b = $("#sidebar-menu a[data-nav='" + d + "']");
            var c = b.parent().parent();
            if (b.hasClass("waves-effect")) {
                b.addClass("active");
            } else {
                b.parent().addClass("active");
                c.show().prev().addClass("active subdrop").find(">span >i").removeClass("md-add").addClass("md-remove");
            }
        }
    },
    initSection: function(c,floag) {
        var b = "txtBeginTime";
        var e = "txtEndTime";
        var d = {
            format: "yyyy-mm-dd hh:ii:ss",
            language: "zh-CN",
            autoclose: true,
            startView: 2,
            weekStart: 1,
            todayBtn: "linked",
            todayBtn: true,
            minuteStep: 1,
        };
        if (c) {
            b = "txtBeginDate";
            e = "txtEndDate";
            d.format = "yyyy-mm-dd";
            d.minView = 2
        }
        a(b, e, true);
        a(e, b, false);
        function a(h, g, f) {
            if(floag){
                var day2 = new Date();
                if(f){
                    if(c){
                        $("#" + h).val(day2.format("yyyy-MM-dd"));
                        $("#" + g).val(day2.format("yyyy-MM-dd"));
                    }else{
                        $("#" + h).val(day2.format("yyyy-MM-dd 00:00:00"));
                        $("#" + g).val(day2.format("yyyy-MM-dd hh:mm:ss"));
                    }
                }else{
                    if(c){
                        $("#" + h).val(day2.format("yyyy-MM-dd"));
                        $("#" + g).val(day2.format("yyyy-MM-dd"));
                    }else{
                        $("#" + h).val(day2.format("yyyy-MM-dd hh:mm:ss"));
                        $("#" + g).val(day2.format("yyyy-MM-dd 00:00:00"));
                    }
                }
            }

            $("#" + h).datetimepicker("remove").datetimepicker(d).on("changeDate", function(i) {
                console.log(i.date);
                $("#" + g).datetimepicker(f ? "setStartDate" : "setEndDate", i.date)
            })
        }
    },
    initDateTime: function(a,floag,type) {
        var b = {
            format: "yyyy-mm-dd hh:ii:ss",
            language: "zh-CN",
            autoclose: true,
            startView: 2,
            weekStart: 1,
            todayBtn: "linked",
            todayBtn: true,
            minuteStep: 1,
        };
        if(floag){
            var day2 = new Date();
            if(type=='begin'){
                $("#" + a).val(day2.format("yyyy-MM-dd 00:00:00"));
            }else{
                $("#" + a).val(day2.format("yyyy-MM-dd 23:59:59"));
            }

        }
        $("#" + a).datetimepicker("remove").datetimepicker(b).on("changeDate", function(c) {});
    },
    buildTabNav: function(a) {
        return $("<div class='tab-nav'><label>" + a + "</label></div>")
    },
    buildTabPanel: function(d) {
        function e(h) {
            return h == null || h == "" ? "-" : h
        }
        var g = $("<div class='tab-panel'></div>");
        for (var c = 0; c < d.length; c++) {
            var f = $("<div class='form-group'></div>");
            var b = d[c].key;
            var a = d[c].holder != undefined ? " data-holder='" + d[c].holder + "'" : "";
            if (d[c].key.length > 0) {
                b += "："
            }
            f.append($("<label class='control-label'>" + b + "</label>"));
            f.append($("<span" + a + ">" + e(d[c].value) + "</span>"));
            g.append(f)
        }
        return g
    }
};
var myFunction = {
    //json 转URL 传参格式
    parseParams: function(data) {
        try {
            var tempArr = [];
            for (var i in data) {
                var key = encodeURIComponent(i);
                var value = encodeURIComponent(data[i]);
                tempArr.push(key + '=' + value);
            }
            var urlParamsStr = tempArr.join('&');
            return urlParamsStr;
        } catch (err) {
            return '';
        }
    },
    //URL 传参格式  转json
    getParams:function(url) {
        try {
            var index = url.indexOf('?');
            url = url.match(/\?([^#]+)/)[1];
            var obj = {}, arr = url.split('&');
            for (var i = 0; i < arr.length; i++) {
                var subArr = arr[i].split('=');
                var key = decodeURIComponent(subArr[0]);
                var value = decodeURIComponent(subArr[1]);
                obj[key] = value;
            }
            return obj;

        } catch (err) {
            return null;
        }
    }
};
/**
 *对Date的扩展，将 Date 转化为指定格式的String
 *月(M)、日(d)、小时(h)、分(m)、秒(s)、季度(q) 可以用 1-2 个占位符，
 *年(y)可以用 1-4 个占位符，毫秒(S)只能用 1 个占位符(是 1-3 位的数字)
 *例子：
 *(new Date()).Format("yyyy-MM-dd hh:mm:ss.S") ==> 2006-07-02 08:09:04.423
 *(new Date()).Format("yyyy-M-d h:m:s.S")      ==> 2006-7-2 8:9:4.18
 */
Date.prototype.format = function (fmt) {
    var o = {
        "M+": this.getMonth() + 1, //月份
        "d+": this.getDate(), //日
        "h+": this.getHours(), //小时
        "m+": this.getMinutes(), //分
        "s+": this.getSeconds(), //秒
        "q+": Math.floor((this.getMonth() + 3) / 3), //季度
        "S": this.getMilliseconds() //毫秒
    };
    if (/(y+)/.test(fmt)) fmt = fmt.replace(RegExp.$1, (this.getFullYear() + "").substr(4 - RegExp.$1.length));
    for (var k in o)
        if (new RegExp("(" + k + ")").test(fmt)) fmt = fmt.replace(RegExp.$1, (RegExp.$1.length == 1) ? (o[k]) : (("00" + o[k]).substr(("" + o[k]).length)));
    return fmt;
}
