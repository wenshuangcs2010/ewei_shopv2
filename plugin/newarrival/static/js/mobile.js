define(['core', 'tpl', 'swiper','./timer.js'], function (core, tpl,s,timerUtil) {
    var model = {};
    model.init = function (params) {
        model.taskid = params.taskid;
        model.roomid = params.roomid;
       model.timeSwiper = null;
        if (params.timecount > 5) {
            model.timeSwiper = new Swiper('.time-container', {slidesPerView: 5, initialSlide: params.timeindex, autoHeight: true})
        }
        if (params.advcount > 3) {
            new Swiper('.adv-container', {slidesPerView: 3, autoHeight: true, noSwiping: false})
        }
        if (params.roomcount > 5) {
            new Swiper('.room-container', {slidesPerView: 5, autoHeight: true, noSwiping: false})
        }
        var onSlideChangeEnd = function (swiper) {
            var index = swiper.activeIndex;
            var obj = $('.goods-slide:eq(' + index + ")");
            var timeid = obj.data('timeid');
            model.timeid = timeid;
            if (obj.find('.infinite-loading').length > 0) {
                model.getGoods(timeid)
            } else {
                model.initTimer(timeid)
            }
            $('.time-slide.current').removeClass('current');
            $('.time-slide-' + timeid).addClass('current')
        };

        model.goodsSwiper = new Swiper('.goods-container', {
            slidesPerView: 1,
            spaceBetween: 10,
            initialSlide: params.timeindex,
            onSlideChangeEnd: onSlideChangeEnd
        });
        $('.time-slide').click(function () {
            var index = $(this).data('index');
            model.goodsSwiper.slideTo(index)
        });

        //if (params.timeindex === 0) {
            model.getGoods(params.timeid)
       // }
    };

    model.getCurrentTime = function () {
        return +new Date($.ajax({url: '../addons/ewei_shopv2/map.json', async: false}).getResponseHeader("Date")) / 1000
    };
    model.getGoods = function (timeid) {
        clearInterval(model.timer);
        model.timeid = timeid;

        core.json('seckill/get_goods', {taskid: model.taskid, roomid:model.roomid, timeid: timeid}, function (ret) {
            if (ret.status == 0) {
                FoxUI.toast.show(ret.result.message);
                return
            }
            var result = ret.result;
            model.time = result.time;
            var html = tpl("tpl_seckill", result);
            core.tpl(".goods-slide[data-timeid='" + timeid + "']", 'tpl_seckill', result, false);
            setTimeout(function () {
                model.goodsSwiper.update()
            }, 500);
            model.initTimer(ret.result.time.id)
        }, false, false)
    };
    model.initTimer = function (timeid) {
        var slide = $(".goods-slide[data-timeid='" + timeid + "']");
        var status = parseInt(slide.data('status')), starttime = slide.data('starttime'), endtime = slide.data('endtime');
        var obj = $(".time-group-" + timeid);
        obj.find('.time-hour').html('-');
        obj.find('.time-min').html('-');
        obj.find('.time-sec').html('-');
        clearInterval(model.timer);
        if (status != -1) {
            $.ajax({
                url: '../addons/ewei_shopv2/map.json',
                complete: function (x) {
                    currenttime = +new Date(x.getResponseHeader("Date")) / 1000;

                    if (status == 0) {
                        model.lasttime = endtime
                    } else {
                        model.lasttime = starttime
                    }
                    model.setTimer();
                    model.timer = model.setTimerInterval()
                }
            })
        }
    };
    model.setTimer = function () {
        model.lasttime -= 1;
        var times = timerUtil.formatSeconds(model.lasttime);
        var obj = $(".time-group-" + model.timeid);
        obj.find('.time-hour').html(times.hour);
        obj.find('.time-min').html(times.min);
        obj.find('.time-sec').html(times.sec);
        if (model.lasttime <= 0) {
            location.reload()
        }
    };
    model.setTimerInterval = function () {
        return setInterval(function () {
            model.setTimer()
        }, 1000)
    };
    return model
});