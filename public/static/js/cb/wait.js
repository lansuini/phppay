//定义创建订单界面时间倒计时进度条属性-默认定义支付宝
var knobObj = {
	'width': "200",
	'height': "200",
	'bgColor': "#dfdfdf",
	'fgColor': "#0097e5",
	'inputColor': "#0097e5",
	'readonly': "readonly",
	'thickness': "0.17",
	'skin': "tron"
}

// 浏览器版本
var browser = {
	versions: function() {
		var u = navigator.userAgent,
			app = navigator.appVersion;
		return { // 移动终端浏览器版本信息
			trident: u.indexOf('Trident') > -1, // IE内核
			presto: u.indexOf('Presto') > -1, // opera内核
			webKit: u.indexOf('AppleWebKit') > -1, // 苹果、谷歌内核
			gecko: u.indexOf('Gecko') > -1 && u.indexOf('KHTML') == -1, // 火狐内核
			mobile: !!u.match(/AppleWebKit.*Mobile.*/), // 是否为移动终端
			ios: !!u.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/), // ios终端
			android: u.indexOf('Android') > -1 || u.indexOf('Linux') > -1 || u.indexOf('Adr') > -1, // android终端或uc浏览器
			iPhone: u.indexOf('iPhone') > -1, // 是否为iPhone或者QQHD浏览器
			iPad: u.indexOf('iPad') > -1, // 是否iPad
			webApp: u.indexOf('Safari') == -1,
			weixin: u.indexOf('MicroMessenger') > -1, // 是否支付宝 （2015-01-22新增）
			alipay: u.indexOf('Alipay') > -1,
			qq: u.indexOf('QQ/') > -1, // 是否QQ
			qqie: u.indexOf('MQQBrowser/') > -1, // 是否QQIE
			eleme: u.indexOf('Eleme') > -1, // 是否eleme
			// 是否web应该程序，没有头部与底部
		};
	}(),
	language: (navigator.browserLanguage || navigator.language)
		.toLowerCase()
}

// 根据支付类型切换皮肤样式 0：支付宝 1：微信
function tplSkinStyle(num) {
	var strPayLogo = "";
	var tip="";
	if (num == 1) {
		// 微信-扫码页面logo与标题模块内容定义
		strPayLogo = '<img name="logoImg" src="/pay/img/wxpay.png" />' +
			'<div class="name">' +
			'<p class="ch">微信支付</p>' +
			'<p class="zh">WeChatPay</p>' +
			'</div>';
		// $(".c-container").removeClass("c-zfb");
		// 微信-添加c-wx样式
		$(".c-container").addClass("c-wx");
		// 微信-定义创建订单界面时间倒计时进度条颜色
		knobObj["fgColor"] = "#11aa38";
		// 修改页面title
		$(document).attr("title", "微信支付");
		tip='<p class="tip">禁止修改付款金额，否则不到账、不退款</p><p class="tip">如遇不能跳转，请保存二维码，用微信扫描</p>';
		$('#tipDiv').html(tip);
	} else {
		// 支付宝-定义扫码页面logo与标题模块内容定义
		strPayLogo = '<img name="logoImg" src="/pay/img/alipay.png" />' +
			'<div class="name">' +
			'<p class="ch">支付宝</p>' +
			'<p class="zh">ALIPAY</p>' +
			'</div>';

		// $(".c-container").removeClass("c-wx");
		// 微信-添加c-zfb样式
		$(".c-container").addClass("c-zfb");
		// 修改页面title
		$(document).attr("title", "支付宝支付");
		tip='<p class="tip">禁止修改付款金额，否则不到账、不退款</p><p class="tip">如遇不能跳转，请保存二维码，用支付宝扫描</p>';
		$('#tipDiv').html(tip);
	}
	// 查找对象-添加倒计时进度条
	if ($("#progress .knob").length > 0) {
		$("#progress .knob").knob(knobObj);
	}
	// 查找对象-添加logo与标题
	if ($('.c-scanCode .head').length > 0) {
		$('.c-scanCode .head').html(strPayLogo);
	}
}

// 顶部流程模板打印
function tplHeadStep(num) {
	var strHeadStep = "";
	for (var i = 1; i <= num; i++) {
		if (i == num) {
			strHeadStep += '<div class="item radius active">' + i + '</div>'
		} else {
			strHeadStep += '<div class="item radius active">' + i + '</div>' +
				'<div class="item spot active"><span></span><span></span><span></span></div>'
		}
	}
	$('.c-step').html(strHeadStep);
}

// 当前步数
function activeHeadStep(num) {
	num = num * 2 - 1;
	$('.c-step .item').removeClass("active");
	$('.c-step .item').each(function(i) {
		if (i < num) {
			$(this).addClass("active");
		}
	});

}

// 验证码等待
function codeWait(time,state) {
	var codeBtnEl = $('#getCodeBtn');
	var state = state||0;
	codeBtnEl.attr("disabled", "disabled");
	codeBtnEl.addClass("codeWait");
	if(state==0){
		codeBtnEl.val(time + "s后重新获取");
	}else{
		codeBtnEl.val(time + "s");
	}
	timerInterval = setInterval(function() {
		time--;
		if(state==0){
			codeBtnEl.val(time + "s后重新获取");
		}else{
			codeBtnEl.val(time + "s");
		}
		if (time < 0) {
			clearInterval(timerInterval);
			codeBtnEl.removeAttr("disabled");
			codeBtnEl.removeClass("codeWait");
			codeBtnEl.val("获取验证码");
		}
	}, 1000);
}

//请求等待

var myWait = {
	timer:null,
	requestWait:function (el,time,text) {
		var that = this;
		var codeBtnEl = el;
		var state = state||0;
		el.attr("disabled", "disabled");
		el.addClass("disabled");
		this.timer = setInterval(function() {
			time--;
			el.val(text+time+"s");
			if (time < 0) {
				that.error(el);
			}
		}, 1000);
	},
	clearWait:function(el){
		clearInterval(this.timer);
		el.removeAttr("disabled");
		el.removeClass("disabled");
		el.val("下一步");
	},
	error:function(el){
		alert("请求超时,请重新发送请求");
		clearInterval(this.timer);
		el.removeAttr("disabled");
		el.removeClass("disabled");
		el.val("下一步");
	}
}


// 倒计时
var dowTimeObj = {
	timer: 0,
	downTime: function(id, millisecond, callBack) {
		clearInterval(dowTimeObj.timer);
		var totalSeconds = millisecond / 1000;
		dowTimeObj.timer = setInterval(function() {
			if (totalSeconds <= 0) {
				clearInterval(dowTimeObj.timer);
				callBack();
			} else {
				totalSeconds--;
				var days = Math.floor(totalSeconds / (60 * 60 * 24));
				// 取模（余数）
				var modulo = totalSeconds % (60 * 60 * 24);
				// 小时数
				var hours = Math.floor(modulo / (60 * 60));
				modulo = modulo % (60 * 60);
				// 分钟
				var minutes = Math.floor(modulo / 60);
				if (minutes < 10) {
					minutes = "0" + minutes;
				}
				// 秒
				var seconds = modulo % 60;
				if (seconds < 10) {
					seconds = "0" + seconds;
				}
				document.getElementById(id).innerHTML = minutes + ":" + seconds;
			}
		}, 1000);
	}
}

// 提示框
var showTip = {
	timer: null,
	fall: function(value) {
		// 清楚定时器
		clearTimeout(showTip.timer);
		// 移除提示框
		$('[name="checkInputTip"]').remove();
		// 设置提示框内容
		var tip = '<div name="checkInputTip" class="checkInputFallTip">' +
			'<span>' + value + '</span>' +
			'</div>';
		// 添加提示框
		$('body').append(tip);
		// 淡入提示框并震动
		$('[name="checkInputTip"]').fadeIn(20, function() {
			$('[name="checkInputTip"]').addClass('checkInputTipFallHover');
		});;
		showTip.timer = setTimeout(showTip.cleanTip, 2000);
	},
	success: function(value, css) {
		// 清楚定时器
		clearTimeout(showTip.timer);
		// 移除提示框
		$('[name="checkInputTip"]').remove();
		// 设置提示框内容
		var tip = "";
		if (css != null && css != "") {
			tip = '<div name="checkInputTip" class="checkInputSuccessTip">' +
				'<span class="' + css + '">' + value + '</span>' +
				'</div>';
		} else {
			tip = '<div name="checkInputTip" class="checkInputSuccessTip">' +
				'<span>' + value + '</span>' +
				'</div>';
		}

		// 添加提示框(提示框默认状态为隐藏)
		$('body').append(tip);
		// 淡入提示框并震动
		$('[name="checkInputTip"]').fadeIn(20, function() {
			$('[name="checkInputTip"]').addClass('checkInputSuccessTip');
		});
		showTip.timer = setTimeout(showTip.cleanTip, 2000);
	},
	cleanTip: function() {
		$('[name="checkInputTip"]').fadeOut(500, function() {
			// 移除提示框
			$('[name="checkInputTip"]').remove();
		});
	}
}

function verifyInput(){
	var countInput = $('input[verify="required"]').length;
	var count = 0;
	$('input[verify="required"]').each(function(){
		var value = $(this).val();
		var desc = $(this).attr("desc");
		var length = $(this).attr("length");
		var minlength = $(this).attr("minlength");
		var maxlength = $(this).attr("maxlength");
		if (value == null || value == "") {
			alert(desc+"不能为空");
			return false;
		}
		if (length != null && length != "" && value.length != length) {
			alert(desc+"长度必须等于"+length+"位");
			return false;
		}
		if (minlength != null && minlength != "" && value.length < minlength) {
			alert(desc+"长度不能小于"+minlength+"位");
			return false;
		}
		if (maxlength != null && maxlength != "" && value.length > maxlength) {
			alert(desc+"长度不能大于"+maxlength+"位");
			return false;
		}
		count++;
	})
	if(countInput == count){
		return true;
	}
	return false;
}