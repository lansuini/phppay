//兼容ie6的fixed代码
//jQuery(function($j){
//  $j('#pop').positionFixed()
//})
(function($j){
    $j.positionFixed = function(el){
        $j(el).each(function(){
            new fixed(this)
        })
        return el;
    }
    $j.fn.positionFixed = function(){
        return $j.positionFixed(this)
    }
    var fixed = $j.positionFixed.impl = function(el){
        var o=this;
        o.sts={
            target : $j(el).css('position','fixed'),
            container : $j(window)
        }
        o.sts.currentCss = {
            top : o.sts.target.css('top'),
            right : o.sts.target.css('right'),
            bottom : o.sts.target.css('bottom'),
            left : o.sts.target.css('left')
        }
        if(!o.ie6)return;
        o.bindEvent();
    }
    $j.extend(fixed.prototype,{
        ie6 : $.browser.msie && $.browser.version < 7.0,
        bindEvent : function(){
            var o=this;
            o.sts.target.css('position','absolute')
            o.overRelative().initBasePos();
            o.sts.target.css(o.sts.basePos)
            o.sts.container.scroll(o.scrollEvent()).resize(o.resizeEvent());
            o.setPos();
        },
        overRelative : function(){
            var o=this;
            var relative = o.sts.target.parents().filter(function(){
                if($j(this).css('position')=='relative')return this;
            })
            if(relative.size()>0)relative.after(o.sts.target)
            return o;
        },
        initBasePos : function(){
            var o=this;
            o.sts.basePos = {
                top: o.sts.target.offset().top - (o.sts.currentCss.top=='auto'?o.sts.container.scrollTop():0),
                left: o.sts.target.offset().left - (o.sts.currentCss.left=='auto'?o.sts.container.scrollLeft():0)
            }
            return o;
        },
        setPos : function(){
            var o=this;
            o.sts.target.css({
                top: o.sts.container.scrollTop() + o.sts.basePos.top,
                left: o.sts.container.scrollLeft() + o.sts.basePos.left
            })
        },
        scrollEvent : function(){
            var o=this;
            return function(){
                o.setPos();
            }
        },
        resizeEvent : function(){
            var o=this;
            return function(){
                setTimeout(function(){
                    o.sts.target.css(o.sts.currentCss)
                    o.initBasePos();
                    o.setPos()
                },1)
            }
        }
    })
})(jQuery)

function Pop(title,url,intro){
    this.title=title;
    this.url=url;
    this.intro=intro;
    this.apearTime=1000;
    this.hideTime=500;
    this.delay=10000;
    // //添加信息
    this.addInfo = function(){
        $("#popTitle a").attr('href',this.url).html(this.title);
        $("#popIntro").html(this.intro);
        $("#popMore a").attr('href',this.url);
    };
    //显示
    this.showDiv = function(){
        if (!($.browser.msie && ($.browser.version == "6.0") && !$.support.style)) {
            $('#pop').slideDown(this.apearTime).delay(this.delay).fadeOut(400);;
        } else{//调用jquery.fixed.js,解决ie6不能用fixed
            $('#pop').show();
            jQuery(function($j){
                $j('#pop').positionFixed()
            })
        }
        document.getElementById('chatAudio').play()
        // $('#chatAudio').play(); //播放声音
    };
    //关闭
    this.closeDiv = function(){
        $("#popClose").click(function(){
                $('#pop').hide();
            }
        );
    };
}


function sleep(delay) {
    var start = (new Date()).getTime();
    while ((new Date()).getTime() - start < delay) {
        continue;
    }
}

var timer=null; //定时器返回值，主要用于关闭定时器
var aTmp = 1;
timer=setInterval(function(){ //打开定时器
    common.getAjax(common.perfectUrl(contextPath + "api/settlementorder/timeInterval?cacheKey=cacheSettlementKey,cacheAlipayBalance", "divContainer"), function(a) {
        aTmp = 0;
        if(a.cacheSettlementKey != undefined && a.cacheSettlementKey > 0) {
            var p = new Pop('代付提醒','/settlementorder', '待手动处理代付订单' + a.cacheSettlementKey + '条')
            p.addInfo()
            p.showDiv()
            sleep(2)
            p.closeDiv()
        }
        // if(a.cacheAlipayBalance != undefined && a.cacheAlipayBalance > 0) {
            // var p = new Pop('支付宝提醒',window.location.href, '支付宝中最低余额为' + a.cacheAlipayBalance + '元')
            // document.getElementById('chatAudioBalance').play()
        // }
    })
    if(aTmp){
        clearInterval(timer);
    }
},8000); //2000为轮播的时间

