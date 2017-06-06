define(['jquery', 'jquery.gcjs', 'foxui'], function ($, gc, FoxUI) {
    var modal = {};
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
        return {
            'hour': theTime2 < 10 ? '0' + theTime2 : theTime2,
            'min': theTime1 < 10 ? '0' + theTime1 : theTime1,
            'sec': theTime < 10 ? '0' + theTime : theTime
        }
    };
    modal.setTimer = function (obj) {
        var lasttime = obj.attr('data-timer-lasttime') || 0;
        lasttime--;
        obj.attr('data-timer-lasttime', lasttime);
        var $datas = obj.data('timer') || '', $datas = $datas.split('|');
        var hourcss = $datas[1], mincss = $datas[2], seccss = $datas[3];
        var callback = $datas[4];
        var times = modal.formatSeconds(lasttime);
        obj.find(hourcss).html(times.hour);
        obj.find(mincss).html(times.min);
        obj.find(seccss).html(times.sec);
        if (lasttime <= 0) {
            if (callback) {
                eval("(" + callback + ")")(obj)
            }else{
                location.reload();
            }
        }
    };
    modal.setTimerInterval = function (obj) {
        $(this).attr('data-timer-interval', setInterval(function () {
            modal.setTimer(obj)
        }, 1000))
    };
    modal.initTimers = function (obj) {
        if (typeof(obj) === 'undefined') {
            obj = '[data-toggle="timer"]'
        }
        $.ajax({
            url: '../addons/ewei_shopv2/map.json', complete: function (x) {
                var currenttime = +new Date(x.getResponseHeader("Date")) / 1000;
                $(obj).each(function () {
                    var obj = $(this);
                    var datas = $(this).data('timer') || '';
                    if (datas == '') {
                        return false
                    }
                    datas = datas.split('|');
                    if (datas.length != 5) {
                        return false
                    }
                    $(this).attr('data-timer-interval', 0);
                    var status = $(obj).data('status') || 0;
                    if(status==0) {
                        $(this).attr('data-timer-lasttime', datas[0] - currenttime);
                    }else{
                        $(this).attr('data-timer-lasttime', datas[4] - currenttime);
                    }
                    modal.setTimer(obj);
                    modal.setTimerInterval(obj)
                })
            }
        })
    };
    return modal
});