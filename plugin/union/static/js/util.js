define(['core'], function (core) {
    var modal = {};

    modal.init_data=function(params){
        var intDiff=modal.lastitme=params.lasttime;


        modal.timer(intDiff);
    };
    modal.timer=function (intDiff) {

        if($('.right').data('start')==1){
            return ;
        }
        $('.right').attr('data-start',1);

        window.setInterval(function(){
            var day=0,
                hour=0,
                minute=0,
                second=0;//时间默认值
            if(intDiff > 0){
                day = Math.floor(intDiff / (60 * 60 * 24));
                hour = Math.floor(intDiff / (60 * 60)) - (day * 24);
                minute = Math.floor(intDiff / 60) - (day * 24 * 60) - (hour * 60);
                second = Math.floor(intDiff) - (day * 24 * 60 * 60) - (hour * 60 * 60) - (minute * 60);
            }

            if (minute <= 9) minute = '0' + minute;
            if (second <= 9) second = '0' + second;
            $('.day').html(day);
            $('.hour').html(hour);
            $('.minute').html(minute);
            $('.second').html(second);

            intDiff--;
        }, 1000);
    }
    return modal;
})