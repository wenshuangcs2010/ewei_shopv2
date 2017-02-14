define(['core', 'tpl'], function (core, tpl, op) {
    var modal = {params: {}};
    modal.init = function () {
        $(function () {
            $("span.lynn_fightgroups_span strong.fl").each(function (index, element) {
                var residualtime = $("#residualtime" + index + "").attr("title");
                InterValObj = window.setInterval(function () {
                    if (residualtime > 0) {
                        residualtime = residualtime - 1;
                        var second = Math.floor(residualtime % 60);
                        var minite = Math.floor((residualtime / 60) % 60);
                        var hour = Math.floor(residualtime / 3600);
                        if(residualtime<=0){
                            clearInterval(InterValObj);
                        }
                        $("#residualtime" + index + "").html(hour + ":" + minite + ":" + second)
                    }
                }, 1000)
            })
        })
    };
    return modal
});