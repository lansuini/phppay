//兼容ie6的fixed代码
//jQuery(function($j){
//  $j('#pop').positionFixed()
//})

// var speed=30
// marquePic2.innerHTML=marquePic1.innerHTML
// function Marquee(){
//     if(demo.scrollLeft>=marquePic1.scrollWidth){
//         demo.scrollLeft=0
//     }else{
//         demo.scrollLeft++
//     }
// }
// var MyMar=setInterval(Marquee,speed)
// demo.onmouseover=function() {clearInterval(MyMar)}
// demo.onmouseout=function() {MyMar=setInterval(Marquee,speed)}

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
});(jQuery)

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
            $('#pop').slideDown(this.apearTime).delay(this.delay).fadeOut(400);
        } else{//调用jquery.fixed.js,解决ie6不能用fixed
            $('#pop').show();
            jQuery(function($j){
                $j('#pop').positionFixed();
            });
        }
        document.getElementById('chatAudio').play();
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




var timerInterval = null; //定时器返回值，主要用于关闭定时器
var aTmp = 1;
if(window.location.host.slice(0, 8) != 'merchant'){
    //打开定时器
    timerInterval = setInterval(function(){
        common.getAjax(common.perfectUrl(contextPath + "api/notify?cacheKey=cacheSettlementKey", "divContainer"), function(a) {
            aTmp = 0;
            if(a.attributes.total > 0) {
                document.getElementById('chatAudioBalance').play();
                var p = new Pop('站内消息提醒：' + '未读消息' + a.attributes.total + '条', '/manager/notices', '<span style="font-weight: bolder;color:#0000ff">最新提醒消息：</span>' + a.data.title);
                p.addInfo();
                p.showDiv();
                sleep(2);
                p.closeDiv();
            }
        });
        if(aTmp){
            // clearInterval(timerInterval);
        }
    }, 17*1000); //1000（1秒）为轮播的时间
}

