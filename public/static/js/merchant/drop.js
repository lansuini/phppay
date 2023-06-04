$(function() {

});

function dropDwon(option) {
  function drop(def) {
    this.name = def.id;
    this.init();
  }
  drop.prototype = {
    init: function() {
      this.list();
      this.myClick();
      this.callBack();
    },
    //添加下拉框到页面中
    list: function() {
      var id = "#" + this.name;
      var input = '<input type="hidden" class="drop-input" id="recipient" data-field="recipient" value>';
      var header = '<div class="drop-header" type="' + def.type + '"></div>';
      //默认下拉框选项
      $(id).append(input);
      $(id).append(header);
      if (def.type == "single") {
        for (var key in def.myData) {
          if (def.myData[key].selected == true) {
            var headerTxt = def.myData[key].val;
            $(id)
              .find(".drop-header")
              .text(headerTxt);
          }
        }
      }
      var newPo = $(id).offset();
      var newW = parseInt($(id).css("width"));
      var newH = parseInt($(id).css("height"));
      $(".drop-down").css({
        width: newW + "px",
        top: newPo.top + newH + "px",
        left: newPo.left + "px"
      });
    },
    //下拉框点击事件
    myClick: function() {
      var self = this;
      var name = this.name;
      var id = "#" + this.name;
      $(id).click(function(e) {
        e.stopPropagation();
        var input = $(".drop-input");
        var oldVal = input.attr("value");
        var oldValList = oldVal.split(",");
        $(".drop-down").remove();
        var _this = $(this);
        var lists, drop;
        lists = "";
        for (var key in def.myData) {
          var active = oldValList.includes(key);
          var sel = active ? "true" : "";
          var clazz = active ? "active" : "";
          lists +=
            '<li value="' +
            key +
            '" sel="' +
            sel +
            '" class="' +
            clazz +
            '">' +
            def.myData[key].val +
            "</li>";
        }
        var drop =
          '<ul class="drop-down" id="' + name + 'con">' + lists + "</ul>";
        $("body").append(drop);
        self.position(".drop-down");
        //改变屏幕宽度的时候，重新计算下拉框内容的位置
        window.onresize = function() {
          self.position(".drop-down");
        };
        if (def.type == "sigle") {
          self.sinClick();
        } else if (def.type == "multi") {
          self.mltClick();
        }
      });
    },
    //计算下拉框内容的位置
    position: function(obj) {
      var id = "#" + this.name;
      var myPo = $(id).offset();
      var myW = parseInt($(id).css("width"));
      var myH = parseInt($(id).css("height")) - 1;
      $(obj).css({
        top: myPo.top + myH + "px",
        left: myPo.left + "px",
        width: myW + "px"
      });
    },
    sinClick: function() {
      var name = this.name;
      var id = "#" + name + "con";
      $(id).on("click", "li", function(e) {
        e.stopPropagation();
        $("#" + name)
          .find(".drop-header")
          .text($(this).text());
      });
      $(document).click(function() {
        $(id).remove();
      });
    },
    mltClick: function() {
      var self = this;
      var name = this.name;
      var id = "#" + name + "con";
      var input = $("#" + name).find(".drop-input");
      var header = $("#" + name).find(".drop-header");
      $(id).on("click", "li", function(e) {
        e.stopPropagation();
        var sel = $(this).attr("sel");
        var oldVal = input.attr("value");
        if (sel == "true") {
          $(this).removeClass("active");
          $(this).attr("sel", false);
          var rem = $(this).attr("value");
          header.find("span").each(function() {
            if ($(this).attr("vel") == rem) {
              $(this).remove();
            }
          });
          var newVal = oldVal
            .split(",")
            .filter(o => o !== rem)
            .join(",");
          input.attr("value", newVal);
        } else {
          $(this).addClass("active");
          $(this).attr("sel", true);
          var txt =
            '<span vel="' +
            $(this).attr("value") +
            '">' +
            $(this).text() +
            " <i class='close'>✖</i> </span>";
          header.append(txt);
          var rem = $(this).attr("value");
          var newVal = oldVal ? oldVal + "," + rem : rem;
          input.attr("value", newVal);
        }
        self.position(".drop-down");
        self.mtlRemove();
      });
      $(document).click(function() {
        $(id).remove();
      });
    },
    mtlRemove: function() {
      var self = this;
      var name = this.name;
      var id = "#" + name;
      $(id + " " + "span").click(function(e) {
        e.stopPropagation();
        var _this = $(this);
        var vle = _this.attr("vel");
        _this.remove();
        $(id + "con li").each(function() {
          var vleLi = $(this).attr("value");
          if (vleLi == vle) {
            $(this).removeClass("active");
            $(this).attr("sel", false);
          }
        });
        self.position(".drop-down");
      });
    },
    callBack: function() {
      def.callBack();
    }
  };
  var def = {
    type: "multi", //multi:多选；sigle：单选
    myData: {
      name1: {
        val: "默认选中",
        selected: true
      },
      name2: {
        val: "下拉1"
      },
      name3: {
        val: "下拉2"
      },
      name4: {
        val: "下拉3"
      }
    },
    callBack: function() {}
  };
  def = $.extend(def, option);
  new drop(def);
}
