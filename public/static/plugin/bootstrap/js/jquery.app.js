var resizefunc = [], w, h, dw, dh;

$(function () {
    !function ($) {
        "use strict";

        var Sidemenu = function () {
            this.$body = $("body"),
            this.$menuItem = $("#sidebar-menu a")
        };

        //menu item click
        Sidemenu.prototype.menuItemClick = function (e) {
            if (!$("#wrapper").hasClass("enlarged")) {
                if ($(this).parent().hasClass("has_sub"))
                    e.preventDefault();

                if (!$(this).hasClass("subdrop")) {
                    // hide any open menus and remove all other classes
                    $("ul", $(this).parents("ul:first")).slideUp(350);
                    $("a", $(this).parents("ul:first")).removeClass("subdrop");
                    $("#sidebar-menu .pull-right i").removeClass("md-remove").addClass("md-add");

                    // open our new menu and add the open class
                    $(this).next("ul").slideDown(350);
                    $(this).addClass("subdrop");
                    $(".pull-right i", $(this).parents(".has_sub:last")).removeClass("md-add").addClass("md-remove");
                    $(".pull-right i", $(this).siblings("ul")).removeClass("md-remove").addClass("md-add");
                } else if ($(this).hasClass("subdrop")) {
                    $(this).removeClass("subdrop");
                    $(this).next("ul").slideUp(350);
                    $(".pull-right i", $(this).parent()).removeClass("md-remove").addClass("md-add");
                }
            }
        },

        //init sidemenu
        Sidemenu.prototype.init = function () {
            var $this = this;
            // LEFT SIDE MAIN NAVIGATION
            $this.$menuItem.on('click', $this.menuItemClick);

            // NAVIGATION HIGHLIGHT & OPEN PARENT
            $("#sidebar-menu ul li.has_sub a.active").parents("li:last").children("a:first").addClass("active").trigger("click");
        },

        //init Sidemenu
        $.Sidemenu = new Sidemenu, $.Sidemenu.Constructor = Sidemenu

    }(window.jQuery),

    //portlets
    function ($) {
        "use strict";

        /**
        Portlet Widget
        */
        var Portlet = function () {
            this.$body = $("body"),
            this.$portletIdentifier = ".portlet",
            this.$portletCloser = '.portlet a[data-toggle="remove"]',
            this.$portletRefresher = '.portlet a[data-toggle="reload"]'
        };

        //on init
        Portlet.prototype.init = function () {
            // Panel closest
            var $this = this;
            $(document).on("click", this.$portletCloser, function (ev) {
                ev.preventDefault();
                var $portlet = $(this).closest($this.$portletIdentifier);
                var $portlet_parent = $portlet.parent();
                $portlet.remove();

                if ($portlet_parent.children().length == 0)
                    $portlet_parent.remove();
            });

            // Panel Reload
            $(document).on("click", this.$portletRefresher, function (ev) {
                ev.preventDefault();
                var $portlet = $(this).closest($this.$portletIdentifier);
                // This is just a simulation, nothing is going to be reloaded
                $portlet.append('<div class="panel-disabled"><div class="loader-1"></div></div>');
                var $pd = $portlet.find('.panel-disabled');
                setTimeout(function () {
                    $pd.fadeOut('fast', function () {
                        $pd.remove();
                    });
                }, 500 + 300 * (Math.random() * 5));
            });
        },

        $.Portlet = new Portlet, $.Portlet.Constructor = Portlet

    }(window.jQuery),

    //main app module
     function ($) {
         "use strict";

         var MoltranApp = function () {
             this.VERSION = "1.1.0",
             this.AUTHOR = "",
             this.SUPPORT = "",
             this.pageScrollElement = "html, body",
             this.$body = $("body")
         };

         //initializing tooltip
         MoltranApp.prototype.initTooltipPlugin = function () {
             $.fn.tooltip && $('[data-toggle="tooltip"]').tooltip()
         },

         //initializing popover
         MoltranApp.prototype.initPopoverPlugin = function () {
             $.fn.popover && $('[data-toggle="popover"]').popover()
         },

         //initializing nicescroll
         MoltranApp.prototype.initNiceScrollPlugin = function () {
             //You can change the color of scroll bar here
             $.fn.niceScroll && $(".nicescroll").niceScroll({ cursorcolor: '#9d9ea5', cursorborderradius: '0px' });
         },

         //on doc load
         MoltranApp.prototype.onDocReady = function (e) {
             FastClick.attach(document.body);
             resizefunc.push("initscrolls");
             resizefunc.push("changeptype");

             $('.animate-number').each(function () {
                 $(this).animateNumbers($(this).attr("data-value"), true, parseInt($(this).attr("data-duration")));
             });

             //RUN RESIZE ITEMS
             $(window).resize(debounce(resizeitems, 100));
             $("body").trigger("resize");

             // right side-bar toggle
             $('.right-bar-toggle').on('click', function (e) {
                 e.preventDefault();
                 $('#wrapper').toggleClass('right-bar-enabled');
             });
         },

         //initilizing 
         MoltranApp.prototype.init = function () {
             var $this = this;
             this.initTooltipPlugin(),
             this.initPopoverPlugin(),
             this.initNiceScrollPlugin(),
             //document load initialization
             $(document).ready($this.onDocReady);
             //creating portles
             $.Portlet.init();
             //init side bar - left
             $.Sidemenu.init();
         },

         $.MoltranApp = new MoltranApp, $.MoltranApp.Constructor = MoltranApp
     }(window.jQuery),

    //initializing main application module
    function ($) {
        "use strict";
        $.MoltranApp.init();
    }(window.jQuery);

    var wow = new WOW({
        boxClass: 'wow',            // animated element css class (default is wow)
        animateClass: 'animated',   // animation css class (default is animated)
        offset: 50,                 // distance to the element when triggering the animation (default is 0)
        mobile: false               // trigger animations on mobile devices (true is default)
    });

    wow.init();
});

function executeFunctionByName(functionName, context) {
    var args = [].slice.call(arguments).splice(2);
    var namespaces = functionName.split(".");
    var func = namespaces.pop();

    for (var i = 0; i < namespaces.length; i++)
        context = context[namespaces[i]];

    return context[func].apply(this, args);
}

function changeptype() {
    w = $(window).width();
    h = $(window).height();
    dw = $(document).width();
    dh = $(document).height();

    if (jQuery.browser.mobile === true)
        $("body").addClass("mobile").removeClass("fixed-left");

    if (!$("#wrapper").hasClass("forced")) {
        if (w > 990) {
            $("body").removeClass("smallscreen").addClass("widescreen");
            $("#wrapper").removeClass("enlarged");
        } else {
            $("body").removeClass("widescreen").addClass("smallscreen");
            $("#wrapper").addClass("enlarged");
            $(".left ul").removeAttr("style");
        }

        if ($("#wrapper").hasClass("enlarged") && $("body").hasClass("fixed-left"))
            $("body").removeClass("fixed-left").addClass("fixed-left-void");
        else if (!$("#wrapper").hasClass("enlarged") && $("body").hasClass("fixed-left-void"))
            $("body").removeClass("fixed-left-void").addClass("fixed-left");
    }

    toggle_slimscroll(".slimscrollleft");
}

function debounce(func, wait, immediate) {
    var timeout, result;
    return function () {
        var context = this, args = arguments;
        var later = function () {
            timeout = null;
            if (!immediate) result = func.apply(context, args);
        };
        var callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) result = func.apply(context, args);
        return result;
    };
}

function resizeitems() {
    if ($.isArray(resizefunc)) {
        for (i = 0; i < resizefunc.length; i++)
            window[resizefunc[i]]();
    }
}

function initscrolls() {
    if (jQuery.browser.mobile !== true) {
        //SLIM SCROLL
        $('.slimscroller').slimscroll({
            height: 'auto',
            size: "5px"
        });

        $('.slimscrollleft').slimScroll({
            height: 'auto',
            position: 'right',
            size: "5px",
            color: '#7A868F',
            wheelStep: 5
        });
    }
}

function toggle_slimscroll(item) {
    if ($("#wrapper").hasClass("enlarged")) {
        $(item).css("overflow", "inherit").parent().css("overflow", "inherit");
        $(item).siblings(".slimScrollBar").css("visibility", "hidden");
    } else {
        $(item).css("overflow", "hidden").parent().css("overflow", "hidden");
        $(item).siblings(".slimScrollBar").css("visibility", "visible");
    }
}