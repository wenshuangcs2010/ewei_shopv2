define(['core','jquery.gcjs'], function (core) {
    var modal={};

    modal.init=function(){
        $(".member_activity .follow_click").on("click",function (e) {
            e.preventDefault();
            e.stopPropagation();
            var chick_count=$(this).data("clickcount");
            var activityid=$(this).data("activityid");
            if(chick_count==1){
                chick_count=-1;
                $(this).data("clickcount",0);
                $(this).children("img").attr('src',"../addons/ewei_shopv2//plugin/union/template/mobile/default/static/images/ic_love.png");
            }
            if(chick_count==0){
                chick_count=1;
                $(this).data("clickcount",1);
                $(this).children("img").attr('src',"../addons/ewei_shopv2//plugin/union/template/mobile/default/static/images/ic_love_selected.png");
            }
            var allcountobj=$(this).next(".right").children().find(".chick_count");
            $allchickcount=core.getNumber(allcountobj.html());
            $allchickcount+=chick_count;
            allcountobj.html($allchickcount);
            core.json('union/memberactivity/click_status', {id:activityid}, function (ret) {

                return;
            })
        });

        $(".dynamic-info .info-detail .data-state-box .follow").on("click",function(e){
            e.preventDefault();
            e.stopPropagation();
            var followid=$(this).data("followid");
            var status=$(this).data("status");
            var amount=core.getNumber($(this).children(".amount").html());
            if(status==0){
                $(this).data("status",1);
                $(this).children(".amount").html(amount+1);
                $(this).children("img").attr("src","../addons/ewei_shopv2//plugin/union/template/mobile/default/static/images/ic_love_selected.png");
            }
            if(status==1){
                $(this).data("status",0);
                $(this).children(".amount").html(amount-1);
                $(this).children("img").attr("src","../addons/ewei_shopv2//plugin/union/template/mobile/default/static/images/ic_love.png");
            }
            core.json('union/dynamic/status', {id:followid}, function (ret) {

                return;
            })

        })
        $(".dynamic-info .info-detail .re-data-state-box .follow").on("click",function(e){
            e.preventDefault();
            e.stopPropagation();
            var followid=$(this).data("followid");
            var status=$(this).data("status");
            var amount=core.getNumber($(this).children(".amount").html());
            if(status==0){
                $(this).data("status",1);
                $(this).children(".amount").html(amount+1);
                $(this).children("img").attr("src","../addons/ewei_shopv2//plugin/union/template/mobile/default/static/images/ic_love_selected.png");
            }
            if(status==1){
                $(this).data("status",0);
                $(this).children(".amount").html(amount-1);
                $(this).children("img").attr("src","../addons/ewei_shopv2//plugin/union/template/mobile/default/static/images/ic_love.png");
            }
            core.json('union/dynamic/status', {id:followid}, function (ret) {

                return;
            })
        })

    };
    return modal;
})