define(['core', 'tpl'], function (core, tpl) {
    var modal = {location: {lat: '0', lng: '0'}};
    modal.init = function (params) {
        modal.initNotice();
        modal.initSwiper();
        modal.initLocation();
        modal.initAudio();
        modal.initFax();
        modal.initseach();
    };
    modal.initNotice = function () {
        if ($(".fui-notice").length > 0) {
            $(".fui-notice").each(function () {
                var _this = $(this);
                var speed = _this.data('speed') * 1000;
                setInterval(function () {
                    var length = _this.find("li").length;
                    if (length > 1) {
                        _this.find("ul").animate({marginTop: "-1rem"}, 500, function () {
                            $(this).css({marginTop: "0px"}).find("li:first").appendTo(this)
                        })
                    }
                }, speed)
            })
        }
    };
    modal.initseach=function(){
        $(".search").on("focus",function(){
             location.href = core.getUrl('goods/search');
        })
    /*
        $(".search").on("focus",function(){
            $(".searchhtml").addClass("searchtable");
            $(".searchhtml").css('z-index',99);
            $(".searchbar").addClass('searchtab');
            $(".searchbar").css('margin-left',"0.5rem");
            $(".fui-header-left").show();
            $(".search_conten").show();
            core.json('diypage/index/searchKeyword',{}, function (json) {
                var html="";
                var result = json.result.list;
                 
                if(JSON.stringify(result) === '[]'){
                   $(".search_conten .content-empty").show(); 
                    return ;
                }
                $(".search_conten .content-empty").hide(); 
                $(".search_conten .tempsearch_conten ul").html("");
                $(result).each(function(index, result) {
                    var url=core.getUrl('goods',{'keywords':result.keywords});
                    html+="<a href="+url+"><li>"+result.keywords+"</li></a>";
                });
                $(".search_conten .tempsearch_conten ul").append(html);
                $(".search_conten .tempsearch_conten").show();
            });
        });
        $(".diypagesearch").on("click",function(){
            $(".searchhtml").removeClass('searchtable');
            $(".searchbar").removeClass('search2');
            $(".searchbar").css('margin-left',"-0.5rem");
            $(".fui-header-left").hide();
            $(".tempsearch_conten").hide();
            $(".search_conten").hide();
        })
        $('.search').bind('input propertychange', function () {
           
            if ($.trim($(this).val()) == '') {
                $(".searchhtml .search_conten .sort_search_list_div").hide();
            }else{
                var keyword=$(this).val()
                 core.json('goods/serachtitle', {keyword:keyword}, function (ret) {
                    var html="";
                    var ss=$(".searchhtml .search_conten .sort_search_list_div .searchlist_li").html("");
                    $.each(ret, function(index, val) {
                        //console.log(val.title);
                        html+="<a href="+val.url+"><li>"+val.title+"</li></a>";
                    });
               
                    $(".searchhtml .search_conten .sort_search_list_div .searchlist_li").html(html);
                    $(".searchhtml .search_conten .sort_search_list_div").show();
                 })
            }
        })*/
    }
    modal.initFax=function (){
          require(['swiper'], function (modal) {
            var loadsize=$(".swiper-container3 a").size();
            var show_num=$(".swiper-container3").data("num");
            if(typeof(show_num)=="undefined" ||show_num==""){ 
                show_num=6;
            }
           
            var swiper = new Swiper('.swiper-container3', {
            paginationClickable: true,
            slidesPerView: show_num,
            loopedSlides:loadsize,
            spaceBetween: 50,
            preventClicks:true,
             });
          })
        
    }
    modal.initSwiper = function () {

        if($('[data-toggle="timer"]').length>0){
            require(['../addons/ewei_shopv2/plugin/seckill/static/js/timer.js'],function(timerUtil){
                timerUtil.initTimers();
            });
        }

        if ($(".swiper").length > 0) {



            require(['swiper'], function (modal) {
                $(".swiper").each(function () {
                    var obj = $(this);
                    var ele = $(this).data('element');
                    var container = ele + " .swiper-container";
                    var view = $(this).data('view');
                    var btn = $(this).data('btn');
                    var free = $(this).data('free');
                    var space = $(this).data('space');
                    var callback = $(this).data('callback');
                    var options = {
                        pagination: container + ' .swiper-pagination',
                        slidesPerView: view,
                        paginationClickable: true,
                        autoHeight: true,
                        nextButton: container + ' .swiper-button-next',
                        prevButton: container + ' .swiper-button-prev',
                        spaceBetween: space > 0 ? space : 0,
                        onTouchEnd: function (swiper) {
                            if (swiper.isEnd && callback) {
                                if (callback == 'seckill') {
                                     location.href = core.getUrl('seckill');
                                }
                            }
                        }
                    };
                    if (!btn) {
                        delete options.nextButton;
                        delete options.prevButton;
                        $(container).find(".swiper-button-next").remove();
                        $(container).find(".swiper-button-prev").remove()
                    }
                    if (free) {
                        options.freeMode = true
                    }
                    var swiper = new Swiper(container, options)
                })
            })
        }
    };
    modal.initLocation = function () {
        if ($(".merchgroup[data-openlocation='1']").length > 0) {
            var mapApi = 'http://api.map.baidu.com/getscript?v=2.0&ak=ZQiFErjQB7inrGpx27M1GR5w3TxZ64k7';
            require([mapApi], function () {
                var geoLocation = new BMap.Geolocation();
                window.modal = modal;
                geoLocation.getCurrentPosition(function (result) {
                    if (this.getStatus() == BMAP_STATUS_SUCCESS) {
                        modal.location.lat = result.point.lat;
                        modal.location.lng = result.point.lng;
                        modal.initMerch()
                    } else {
                        FoxUI.toast.show("位置获取失败!");
                        return
                    }
                }, {enableHighAccuracy: true})
            })
        }
    };
    modal.initMerch = function () {
        $(".merchgroup").each(function () {
            var _this = $(this);
            var item = _this.data('itemdata');
            if (!item || !item.params.openlocation) {
                return
            }
            core.json('diypage/getmerch', {
                lat: modal.location.lat,
                lng: modal.location.lng,
                item: item
            }, function (result) {
                if (result.status == 1) {
                    var list = result.result.list;
                    if (list) {
                        _this.empty();
                        $.each(list, function (id, merch) {
                            var thumb = merch.thumb ? merch.thumb : '../addons/ewei_shopv2/static/images/merch.jpg';
                            var html = '';
                            html = '<div class="fui-list jump">';
                            html += '<a class="fui-list-media" href="' + core.getUrl("merch", {merchid: merch.id}) + '" data-nocache="true"><img src="' + thumb + '"/></a>';
                            html += '<a class="fui-list-inner" href="' + core.getUrl("merch", {merchid: merch.id}) + '" data-nocache="true">';
                            html += '<div class="title" style="color: ' + item.style.titlecolor + ';">' + merch.name + '</div>';
                            if (merch.desc) {
                                html += '<div class="subtitle" style="color: ' + item.style.textcolor + ';">' + merch.desc + '</div>'
                            }
                            if (merch.distance && item.params.openlocation) {
                                html += '<div class="subtitle" style="color: ' + item.style.rangecolor + '; font-size: 0.6rem">距离您: ' + merch.distance + 'km</div>'
                            }
                            html += '</a>';
                            html += '<a class="fui-remark jump" style="padding-right: 0.2rem; height: 2rem; width: 2rem; text-align: center; line-height: 2rem;" href="' + core.getUrl("merch/map", {merchid: merch.id}) + '" data-nocache="true">';
                            html += '<i class="icon icon-location" style="color: ' + item.style.locationcolor + '; font-size: 1rem;"></i>';
                            html += '</a>';
                            html += '</div>';
                            _this.append(html)
                        });
                        _this.show()
                    }
                }
            }, true, true)
        })
    };
    modal.initAudio = function () {
        if ($(".play-audio").length > 0) {
            $(".play-audio").each(function () {
                var _this = $(this);
                var autoplay = _this.data('autoplay');
                var audio = _this.find("audio")[0];
                var duration = audio.duration;
                var time = modal.formatSeconds(duration);
                _this.find(".time").text(time);
                if (autoplay) {
                    modal.playAudio(_this)
                }
                $(_this).click(function () {
                    if (!audio.paused) {
                        modal.stopAudio(_this)
                    } else {
                        modal.playAudio(_this)
                    }
                })
            })
        }
    };
    modal.playAudio = function (_this) {
        _this.siblings().find("audio").each(function () {
            var __this = $(this).closest(".play-audio");
            modal.stopAudio(__this)
        });
        var audio = _this.find("audio")[0];
        var duration = audio.duration;
        audio.play();
        _this.find(".horn").addClass('playing');
        if (audio.paused) {
            _this.find(".speed").css({width: '0px'})
        }
        var timer = setInterval(function () {
            var currentTime = audio.currentTime;
            if (currentTime >= duration) {
                modal.stopAudio(_this);
                clearInterval(timer)
            }
            var _thiswidth = _this.outerWidth();
            var _width = (currentTime / duration) * _thiswidth;
            _this.find(".speed").css({width: _width + 'px'})
        }, 1000)
    };
    modal.stopAudio = function (_this) {
        var audio = _this.find("audio")[0];
        if (audio) {
            var stop = _this.data('pausestop');
            if (stop) {
                audio.currentTime = 0
            }
            audio.pause();
            _this.find(".horn").removeClass('playing')
        }
    };
    modal.formatSeconds = function (value) {
        var theTime = parseInt(value);
        var theTime1 = 0;
        var theTime2 = 0;
        if (theTime > 60) {
            theTime1 = parseInt(theTime / 60);
            theTime = parseInt(theTime % 60);
            if (theTime1 > 60) {
                theTime2 = parseInt(theTime1 / 60);
                theTime1 = parseInt(theTime1 % 60)
            }
        }
        var result = "" + parseInt(theTime) + "''";
        result = "" + parseInt(theTime1) + "'" + result;
        if (theTime2 > 0) {
            result = "" + parseInt(theTime2) + "'" + result
        }
        return result
    };
    return modal
});