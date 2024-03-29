define(['core','jquery.gcjs'], function (core) {
    var modal = {page: 1, params: {}};
    var defaults = {
    };
    modal.init=function(){
        core.json('union/index/get_welfare_list', {}, function (ret) {
            var result = ret.result;
            if (result.total <= 0) {
                $('.content-empty').show();
                $('.fui-content').infinite('stop')
            } else {
                $('.content-empty').hide();
                $('.fui-content').infinite('init');
                if (result.list.length <= 0 || result.list.length < result.pagesize) {
                    $('.fui-content').infinite('stop')
                }
            }
            core.tpl('.welfarecontainer', 'tpl_welfare_list', result,0)
        }, false, true);

    }
    modal.personnelmien_init=function(params){
        modal.params = $.extend(defaults, params || {});
        var leng = $.trim($('.container').html());
        if (leng == '') {
            modal.page = 1;
            modal.personnelmien();
        }
        $('.fui-content').infinite({
            onLoading: function () {
                modal.personnelmien()
            }
        });
    }
    modal.personnelmien = function () {
        modal.params.page = modal.page;
        core.json('union/personnelmien/get_personnelmien',modal.params, function (ret) {
            var result = ret.result;
            if (result.total <= 0) {
                $('.content-empty').show();
                $('.fui-content').infinite('stop')
            } else {
                $('.content-empty').hide();
                $('.fui-content').infinite('init');
                if (result.list.length <= 0 || result.list.length < result.pagesize) {
                    $('.fui-content').infinite('stop')
                }
            }
            modal.page++;
            core.tpl('#goods-list-container', 'tpl_goods_list', result,0)
        }, false, true)
    };
    modal.document_init=function(params){
        modal.params = $.extend(defaults, params || {});
        $(document).keydown(function (event) {

            if (event.keyCode == 13) {
                event.preventDefault();
                event.stopPropagation();
                modal.params.keywords=$("#keywords").val();
                $('.container').html("");
                modal.page = 1;
                modal.document();
            }
            return;
        });
        var leng = $.trim($('.container').html());
        if (leng == '') {
            modal.page = 1;
            modal.document();
        }
        $('.fui-content').infinite({
            onLoading: function () {
                modal.document()
            }
        });
    };
    modal.document=function(){
        modal.params.page = modal.page;
        core.json('union/document/get_document_list', modal.params, function (ret) {
            var result = ret.result;
            if (result.total <= 0) {
                $('.content-empty').show();
                $('.fui-content').infinite('stop')
            } else {
                $('.content-empty').hide();
                $('.fui-content').infinite('init');
                if (result.list.length <= 0 || result.list.length < result.pagesize) {
                    $('.fui-content').infinite('stop')
                }
            }
            modal.page++;
            core.tpl('#goods-list-container', 'tpl_goods_list', result,modal.page>1)
        }, false, true)
    };

    modal.suggestions_init=function(params) {
        modal.params = $.extend(defaults, params || {});
        var leng = $.trim($('.container').html());
        $(".content-empty").hide();
        if (leng == '') {
            modal.page = 1;
            modal.suggestions();
        }
        $('.fui-content').infinite({
            onLoading: function () {
                modal.suggestions()
            }
        });
    };
    modal.bindsuggchickEvent=function(){
        $(".fui-cell-group").unbind("click").on("click",function (e) {
            e.preventDefault();
            e.stopPropagation();
            var id=$(this).data("id");
            // FoxUI.confirm("是否删除建言","提示", function () {
            //     core.json("union/index/deletesuggestiions",{id:id},function(ret){
            //         if(ret.status==1){
            //             location.refresh();
            //         }
            //     },false,true)
            //
            // },function () {
                location.href=core.getUrl("union/index/get_suggestions_post",{id:id});
            //});
        })
    };
    modal.suggestions_delete=function(params){
        $("#deletepost").click(function () {
            FoxUI.confirm("是否删除建言","提示", function () {
                core.json("union/index/deletesuggestiions",{id:params.id},function(ret){
                            if(ret.status==1){
                                location.href=core.getUrl("union/index/mysuggestions");
                            }},false,true)
            })
        })
    };
    modal.suggestions=function(){
        modal.params.page = modal.page;
        console.log(modal.params);
        core.json('union/index/get_suggestions_list', modal.params, function (ret) {
            var result = ret.result;
            if (result.total <= 0) {
                $('.content-empty').show();
                $('.fui-content').infinite('stop')
            } else {
                $('.content-empty').hide();
                $('.fui-content').infinite('init');
                if (result.list.length <= 0 || result.list.length < result.pagesize) {
                    $('.fui-content').infinite('stop')
                }
            }
            modal.page++;
            if(modal.params.type=="my"){
                core.tpl('#goods-list-my-container', 'tpl_goods_list_mylist', result,modal.page>1);
            }else{
                core.tpl('#goods-list-container', 'tpl_goods_list', result,modal.page>1);
            }

            if(modal.params.type=="my"){
                modal.bindsuggchickEvent();
            }

        }, false, true)
    };
    modal.association_init=function(params){

        FoxUI.tab({
            container: $('#tabjoin'), handlers: {
                level1: function () {
                    modal.changeTabmemberjoin(1)
                }, level2: function () {
                    modal.changeTabmemberjoin(2)
                }, level3: function () {
                    modal.changeTabmemberjoin(3)
                }
            }
        });
        console.log("sss");
        $(".fui-content .noborder").children("a").unbind("click").on("click",function () {
                var categoryid=$(this).data("id");
                modal.params.category_id=categoryid;
                modal.page = 1;
                $('.fui-content').infinite('init');
                $('.content-empty').hide(), $('.infinite-loading').show();
                $('#tpl-list-container').html("");
                modal.association();
        });
        modal.params = $.extend(defaults, params || {});
        var leng = $.trim($('.container').html());
        $('.content-empty').hide();
        if (leng == '') {
            modal.page = 1;
            modal.asspcmy_new_get_list();
        }
        $('.fui-content').infinite({
            onLoading: function () {

                var tabval=$("#tabjoin").find(".active").data("tab");


                if(tabval=='level1'){
                    modal.asspcmy_new_get_list()
                }
                if(tabval=='level2'){
                    modal.asspcmy_myasso_not_join();
                }
                if(tabval=='level3'){
                    modal.association();
                }

            }
        });
    };
    modal.changeTabmemberjoin=function(key){

        $('.fui-content').infinite('init');
        $('.content-empty').hide(), $('.infinite-loading').show(), $('#container').html('');
        $('#tpl-list-container').html("");
        modal.page = 1;
        if(key==1){
            $(".fui-content .noborder").hide();
            modal.asspcmy_new_get_list();
        }
        if(key==2){
            $(".fui-content .noborder").hide();
            modal.asspcmy_myasso_not_join();
        }
        if(key==3){
            $(".fui-content .noborder").show();

            modal.association();
        }
    };
    modal.asspcmy_myasso_not_join=function(){
        modal.params.page = modal.page;
        core.json('union/association/myasso_not_join', modal.params, function (ret) {
            var result = ret.result;
            if (result.total <= 0) {
                $('.content-empty').show();
                $("#empty_show_text").html('未找到其他没有加入的');
                $('.fui-content').infinite('stop')
            } else {
                $('.content-empty').hide();
                $('.fui-content').infinite('init');
                if (result.list.length <= 0 || result.list.length < result.pagesize) {
                    $('.fui-content').infinite('stop')
                }
            }
            modal.page++;
            core.tpl('#tpl-list-container', 'tpl_asso_list', result,modal.page>1);
            modal.bindcreate_add();
        }, false, true)
    };
    modal.asspcmy_new_get_list=function(){
        modal.params.page = modal.page;
        core.json('union/association/myasso_new_list', modal.params, function (ret) {
            var result = ret.result;
            if (result.total <= 0) {
                $('.content-empty').show();
                $('.fui-content').infinite('stop')
            } else {
                $('.content-empty').hide();
                $('.fui-content').infinite('init');
                if (result.list.length <= 0 || result.list.length < result.pagesize) {
                    $('.fui-content').infinite('stop')
                }
            }
            modal.page++;
            core.tpl('#tpl-list-container', 'tpl_asso_list', result,modal.page>1);
            modal.bindcreate_add();
        }, false, true)
    };


    modal.asspcmy_get_list=function(){
        modal.params.page = modal.page;
        core.json('union/association/myasso_list', modal.params, function (ret) {
            var result = ret.result;
            if (result.total <= 0) {
                $('.content-empty').show();
                $('.fui-content').infinite('stop')
            } else {
                $('.content-empty').hide();
                $('.fui-content').infinite('init');
                if (result.list.length <= 0 || result.list.length < result.pagesize) {
                    $('.fui-content').infinite('stop')
                }
            }
            modal.page++;
            core.tpl('#asso-list-container', 'tpl_asso_list', result,modal.page>1);
            modal.bindcreate_add();
        }, false, true)
    };

    modal.association=function(){

        modal.params.page = modal.page;
        core.json('union/association/get_list', modal.params, function (ret) {
            var result = ret.result;
            if (result.total <= 0) {
                $('.content-empty').show();
                $('.fui-content').infinite('stop')
            } else {
                $('.content-empty').hide();
                $('.fui-content').infinite('init');
                if (result.list.length <= 0 || result.list.length < result.pagesize) {
                    $('.fui-content').infinite('stop')
                }
            }
            modal.page++;
            core.tpl('#tpl-list-container', 'tpl_goods_list', result,modal.page>1)
        }, false, true);
    };
    modal.unionlistbindEvents = function() {
        $('.union_info').unbind('click').click(function() {
            var unionid=$(this).data("unionid");
            var _chilkthis=$(this);

            if(_chilkthis.find(".default").hasClass("defaultset")){
                return false;
            }
            $(".default").removeClass("defaultset");
            $(".default").html("设置默认");

            core.json('union/member/joinunion', {unionid:unionid}, function (ret) {
                if(ret.status==0){
                    FoxUI.toast.show(ret.result.message);
                   //var url=core.getUrl("union/member/member_info");
                  // window.location.href=url;
                    _chilkthis.find(".default").addClass("defaultset");
                    _chilkthis.find(".default").html("默认");
                    return;
                }
                if(ret.status==1){
                    var url=core.getUrl("union/member/join_union",{unionid:unionid});
                    window.location.href=url;
                }

            }, false, true)
        })
    };
    modal.unionlist_init=function(params){
        modal.params = $.extend(defaults, params || {});
        var leng = $.trim($('.container').html());
        if (leng == '') {
            modal.page = 1;
            modal.getunionlist();
        }
        $('.fui-content').infinite({
            onLoading: function () {
                modal.getunionlist()
            }
        });

        $(document).keydown(function (event) {

            if (event.keyCode == 13) {
                event.preventDefault();
                event.stopPropagation();
                modal.params.keywords=$("#searchtitle").val();
                $('.container').html("");
                modal.page = 1;
                modal.getunionlist();
            }
            return;
        });

    };
    modal.getunionlist=function(){
        modal.params.page = modal.page;
        core.json('union/member/get_list', modal.params, function (ret) {
            var result = ret.result;
            if (result.total <= 0) {
                $('.content-empty').show();
                $('.fui-content').infinite('stop')
            } else {
                $('.content-empty').hide();
                $('.fui-content').infinite('init');
                if (result.list.length <= 0 || result.list.length < result.pagesize) {
                    $('.fui-content').infinite('stop')
                }
            }
            modal.page++;
            core.tpl('#goods-list-container', 'tpl_goods_list', result,modal.page>1);
            modal.unionlistbindEvents();
        }, false, true)
    };
    modal.member_info_init=function(){

        $("#btn-submit").click(function () {

            var realname=$('#realname').val();
            var mobile=$("#mobile").val();
            var birthday = $('#birthday').val().split('-');
            var mail=$("#mail").val();
            var wechat=$("#wechat").val();
            var data={
                'realname':realname,
                'birthday':birthday,
                'mail':mail,
                'wechat':wechat,
            };
            core.json('union/member/updateinfo', data, function (ret) {
                if(ret.status==0){
                    FoxUI.toast.show(ret.result.message);

                    return;
                }
                if(ret.status==1){
                    var url=core.getUrl("union/member");
                    window.location.href=url;
                }
            }, false, true)
        })
    };
    modal.join_member=function(){
        $("#btn-submit").click(function () {
            if($("#realname").isEmpty()){
                FoxUI.toast.show('请填写姓名');
                return;
            }
            data={realname:$("#realname").val(),unionid:$("#union_id").val()};
            core.json('union/member/join_union_post', data, function (ret) {
                if(ret.status==0){
                    FoxUI.toast.show(ret.result.message);

                    return;
                }
                if(ret.status==1){
                    var url=core.getUrl("union/member");
                    window.location.href=url;
                }
            }, false, true)
        })
    };

    modal.dynamic_init=function(params){
        modal.params = $.extend(defaults, params || {});
        var leng = $.trim($('.container').html());




        if (leng == '') {
            modal.page = 1;
            modal.dynamic_get_list();
        }
        $('.fui-content').infinite({
            onLoading: function () {
                modal.dynamic_get_list()
            }
        });
    };
    modal.dynamic_bindEvents=function(){
        $(".dynamic-info .info-detail .data-state-box .follow").unbind("click").on("click",function () {
            console.log("ssssssss-222222");
             var status=$(this).data("status");
             var followid= $(this).data("followid");
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
        $(".dynamic-info .info-detail .re-data-state-box .follow").unbind("click").on("click",function(e){
           console.log("ssssssss");
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
    }
    modal.dynamic_get_list=function () {
        modal.params.page = modal.page;
        core.json('union/dynamic/get_list', modal.params, function (ret) {
            var result = ret.result;
            if (result.total <= 0) {
                $('.content-empty').show();
                $('.fui-content').infinite('stop')
            } else {
                $('.content-empty').hide();
                $('.fui-content').infinite('init');
                if (result.list.length <= 0 || result.list.length < result.pagesize) {
                    $('.fui-content').infinite('stop')
                }


            }
            modal.page++;
            core.tpl('#goods-list-container', 'tpl_goods_list', result,modal.page>1);
            modal.dynamic_bindEvents();
        }, false, true)
    };
    modal.welist_init=function (params){
        modal.params = $.extend(defaults, params || {});
        var leng = $.trim($('.container').html());
        if (leng == '') {
            modal.page = 1;
            modal.we_getlist();
        }
        $('.fui-content').infinite({
            onLoading: function () {
                modal.we_getlist()
            }
        });
    };
    modal.we_getlist=function(){
        modal.params.page = modal.page;
        core.json('union/welfare/get_list', modal.params, function (ret) {
            var result = ret.result;
            if (result.total <= 0) {
                $('.content-empty').show();
                $('.fui-content').infinite('stop')
            } else {
                $('.content-empty').hide();
                $('.fui-content').infinite('init');
                if (result.list.length <= 0 || result.list.length < result.pagesize) {
                    $('.fui-content').infinite('stop')
                }
            }
            modal.page++;
            core.tpl('#list-container', 'tpl_list', result,modal.page>1);
        }, false, true)
    };
    modal.friendship_init=function(params){
        modal.params = $.extend(defaults, params || {});

        $(".d_simp .commission_head .setbtn li .togele").click(function () {
            $(".d_simp .commission_head .setbtn li .menu_list").toggle();
        });
        var leng = $.trim($('.container').html());
        $('.content-empty').hide();

        if (leng == '') {
            modal.page = 1;
            modal.friend_list();
        }
        $('.fui-content').infinite({
            onLoading: function () {
                modal.friend_list()
            }
        });
    };
    modal.friend_list=function(){
        modal.params.page = modal.page;
        core.json('union/friendship/get_list', modal.params, function (ret) {
            var result = ret.result;
            if (result.total <= 0) {
                $('.content-empty').show();
                $('.fui-content').infinite('stop')
            } else {
                $('.content-empty').hide();
                $('.fui-content').infinite('init');
                if (result.list.length <= 0 || result.list.length < result.pagesize) {
                    $('.fui-content').infinite('stop')
                }
            }
            modal.page++;
            core.tpl('#list-container', 'tpl_list', result,modal.page>1);
            modal.bindpageclickEvents();

        }, false, true)
    };
    modal.bindpageclickEvents=function(){
        $('.fabulous_click').unbind('click').click(function(e) {
            e.preventDefault();
            e.stopPropagation();
            var fabulous=$(this).data("fabulous");
            var friend_id=$(this).data("friend_id");
            if(fabulous==0){
                $(this).children("img").attr('src',"../addons/ewei_shopv2//plugin/union/template/mobile/default/static/images/ic_like_selected.png");
                $(this).data("fabulous",1);
                core.json("union/friendship/addfabuls",{"fabulous":1,'friend_id':friend_id},function (ret) {
                    var result = ret.result;
                },false,true)
            }
            if(fabulous==1){
                $(this).children("img").attr('src',"../addons/ewei_shopv2//plugin/union/template/mobile/default/static/images/ic_like_unselected.png");

                $(this).data("fabulous",0);
                core.json("union/friendship/addfabuls",{"fabulous":0,'friend_id':friend_id},function (ret) {
                    var result = ret.result;
                },false,true)
            }



        });
        $('.follow_click').unbind('click').click(function(e) {
            e.preventDefault();
            e.stopPropagation();
            var follow=$(this).data("follow");
            var allfollow= parseInt($(this).next(".right").find(".allfollow").html());
            var friend_id=$(this).data("friend_id");
            if(follow==0){
                $(this).children("img").attr('src',"../addons/ewei_shopv2//plugin/union/template/mobile/default/static/images/ic_attetion_selected.png");
                $(this).data("follow",1);
                $(this).next(".right").find(".allfollow").html(allfollow+1);

                core.json("union/friendship/addfollow",{"follow":1,'friend_id':friend_id},function (ret) {
                    var result = ret.result;
                },false,true)
            }
            if(follow==1){
                $(this).children("img").attr('src',"../addons/ewei_shopv2//plugin/union/template/mobile/default/static/images/ic_attetion_unselected.png");
                $(this).data("follow",0);
                $(this).next(".right").find(".allfollow").html(allfollow-1);
                core.json("union/friendship/addfollow",{"follow":0,'friend_id':friend_id},function (ret) {
                    var result = ret.result;
                },false,true)
            }


        })
    };
    modal.friendship_type_init=function(params){
        modal.params = $.extend(defaults, params || {});
        $(".d_simp .commission_head .setbtn li .togele").click(function () {
            $(".d_simp .commission_head .setbtn li .menu_list").toggle();
        });
        var leng = $.trim($('.container').html());
        if (leng == '') {
            modal.page = 1;
            modal.friend_type_list();
        }
        $('.fui-content').infinite({
            onLoading: function () {
                modal.friend_type_list()
            }
        });
    };
    modal.friend_type_list=function(){
        modal.params.page = modal.page;
        core.json('union/friendship/get_list', modal.params, function (ret) {
            var result = ret.result;
            if (result.total <= 0) {
                $('.content-empty').show();
                $('.fui-content').infinite('stop')
            } else {
                $('.content-empty').hide();
                $('.fui-content').infinite('init');
                if (result.list.length <= 0 || result.list.length < result.pagesize) {
                    $('.fui-content').infinite('stop')
                }
            }
            modal.page++;
            core.tpl('#list-container', 'tpl_type_list', result,modal.page>1);
            modal.bindpageclickEvents();
            modal.bindeditclickEvents();
        }, false, true)
    };
    modal.friendship_type_f_init=function(params){
        modal.params = $.extend(defaults, params || {});
        $(".d_simp .commission_head .setbtn li .togele").click(function () {
            $(".d_simp .commission_head .setbtn li .menu_list").toggle();
        });
        var leng = $.trim($('.container').html());
        if (leng == '') {
            modal.page = 1;
            modal.friend_type_f_list();
        }
        $('.fui-content').infinite({
            onLoading: function () {
                modal.friend_type_f_list()
            }
        });
    };
    modal.friend_type_f_list=function(){
        modal.params.page = modal.page;
        core.json('union/friendship/get_list', modal.params, function (ret) {
            var result = ret.result;
            if (result.total <= 0) {
                $('.content-empty').show();
                $('.fui-content').infinite('stop')
            } else {
                $('.content-empty').hide();
                $('.fui-content').infinite('init');
                if (result.list.length <= 0 || result.list.length < result.pagesize) {
                    $('.fui-content').infinite('stop')
                }
            }
            modal.page++;
            core.tpl('#list-container', 'tpl_type_f_list', result,modal.page>1);
            modal.bindpageclickEvents();

        }, false, true)
    };
    modal.bindeditclickEvents=function(){
        $('.btn').unbind('click').click(function() {
            var datatype=$(this).data("type");
            var id=$(this).data("id");
            var btn=$(this);
            if(datatype=="edit"){
                window.location.href=core.getUrl("union/friendship/addfriendship",{id:id});
            }
            if(datatype=="del"){
                FoxUI.confirm('确认删除么?', '提示', function () {
                    core.json('union/friendship/del',{id:id}, function (ret) {


                    }, false, true)

                   var del=btn.parent().parent().parent(".fui-list-group");
                    del.remove();
                })
            }
        })
    };
    modal.friendship_view=function(){
        $(".tabs-left .nav-tabs li").unbind('click').click(function() {
            $(this).parent().children("li").removeClass("active");
            var tab=$(this).children("span").data("href");
            $(this).addClass("active");
            $(".tab-content").children("div").removeClass("active");
            $("#"+tab).addClass("active");
        });
    };

    modal.venue_init=function(params){
        modal.params = $.extend(defaults, params || {});
        $(".venue_page .commission_head .setbtn li .togele").click(function () {
            $(".venue_page .commission_head .setbtn li .menu_list").toggle();
        });
        $('.content-empty').hide();
        var leng = $.trim($('.container').html());
        if (leng == '') {
            modal.page = 1;
            modal.venue_get_list();
        }
        $('.fui-content').infinite({
            onLoading: function () {
                modal.venue_get_list()
            }
        });
    };
    modal.venue_get_list=function(){
        modal.params.page = modal.page;
        core.json('union/venue/get_list', modal.params, function (ret) {
            var result = ret.result;
            if (result.total <= 0) {
                $('.content-empty').show();
                $('.fui-content').infinite('stop')
            } else {
                $('.content-empty').hide();
                $('.fui-content').infinite('init');
                if (result.list.length <= 0 || result.list.length < result.pagesize) {
                    $('.fui-content').infinite('stop')
                }
            }
            modal.page++;
            core.tpl('#venue-list-container', 'tpl_venue_list', result,modal.page>1);
        }, false, true)
    };
    modal.venue_mylist_init=function (params) {
        modal.params = $.extend(defaults, params || {});
        var leng = $.trim($('.container').html());


        if (leng == '') {
            modal.page = 1;
            modal.venue_mylist();
        }
        $('.fui-content').infinite({
            onLoading: function () {
                modal.venue_mylist()
            }
        });
    };
    modal.venue_mylist=function () {
        modal.params.page = modal.page;
        core.json('union/venue/mylist_list', modal.params, function (ret) {
            var result = ret.result;

            if (result.total <= 0) {
                $('.content-empty').show();
                $('.fui-content').infinite('stop')
            } else {
                $('.content-empty').hide();
                $('.fui-content').infinite('init');
                if (result.list.length <= 0 || result.list.length < result.pagesize) {
                    $('.fui-content').infinite('stop')
                }
            }
            modal.page++;
            core.tpl('#venue-mylist-container', 'tpl_mylist_list', result,modal.page>1);
            modal.bindcreate_cancel();
        }, false, true)
    };
    modal.bindcreate_cancel=function () {
        $(".btn-cancel").unbind('click').click(function () {
            var btn=$(this);
            var id=$(this).data("id");
            FoxUI.confirm('确认取消么?', '提示', function () {
                core.json('union/venue/cancel',{id:id}, function (ret) {
                    if(ret.status==1){
                        var del=btn.parent().parent().parent().parent(".fui-list-group");
                        del.remove();
                        FoxUI.toast.show("成功取消");
                        return;
                    }
                    FoxUI.toast.show("取消失败请重试");
                    return;

                }, false, true)
            })
        })

    };
    modal.assocmy_init=function(params){
        modal.params = $.extend(defaults, params || {});
        var leng = $.trim($('.container').html());
        if (leng == '') {
            modal.page = 1;
            modal.asspcmy_get_list();
        }
        $('.fui-content').infinite({
            onLoading: function () {
                modal.asspcmy_get_list()
            }
        });
    };

    modal.bindcreate_add=function(){
        /*
        $(".assocjoin").unbind("click").click(function(e){
            e.preventDefault();
            e.stopPropagation();
            var rid=$(this).data("id");
            var status=$(this).data("status");
            if(status==0){
                FoxUI.toast.show("等待管理员审核");
                return;
            }
            if(status==1){
                FoxUI.toast.show("已经加入当前协会");
                return ;
            }
            var message=$(this);
            if(status==-1){
                core.json('union/association/join_add', {id:rid,status:status}, function (ret) {

                    if(ret.status==0){
                        FoxUI.toast.show(ret.result.message);
                        return ;
                    }
                    message.data("status",0);
                    message.html("审核中");
                    message.addClass("active_sh");
                },false,true);
            }
            return;
        })*/
    }
    modal.assview_init=function(params){
        modal.params = $.extend(defaults, params || {});
        var leng = $.trim($('.container').html());

        $("#btn-submit").unbind("click").on("click",function () {
            var type=$(this).data("type");
            var id=$(this).data("id");
            var memberid=$(this).data("memberid");
            if(typeof (type)=="undefined"){
                return;
            }
            if(type=="join"){
                window.location.href=core.getUrl("union/association/addassociation",{id:id});
            }
            if(type=="signout"){
                FoxUI.confirm("确认退出当前小组么？",'提示',function () {
                    core.json('union/association/addtions',{"assomemberid":memberid,'assoid':id,'type':type}, function (ret) {
                        if(ret.status!=1){
                            FoxUI.toast.show(ret.result.message);
                            return ;
                        }else{
                            FoxUI.toast.show(ret.result.msg);
                            setTimeout(function(){
                                window.location.href=core.getUrl("union/association/assview",{id:id});
                            },1000);
                        }
                    })
                })
            }
        })

        if (leng == '') {
            modal.page = 1;
            modal.asspcview_list();
        }
        $('.asssview-index-page .fui-content').infinite({
            onLoading: function () {
                modal.asspcview_list()
            }
        });
    };
    modal.asspcview_list=function(){
        modal.params.page = modal.page;

        core.json('union/association/viewlist', modal.params, function (ret) {
            var result = ret.result;
            if (result.total <= 0) {
                $('.asssview .fui-content-inner .content-empty').show();
                $('.asssview-index-page .fui-content').infinite('stop')
            } else {
                $('.asssview .fui-content-inner .content-empty').hide();
                $('.asssview-index-page .fui-content').infinite('init');
                if (result.list.length <= 0 || result.list.length < result.pagesize) {
                    $('.asssview-index-page .fui-content').infinite('stop')
                }
            }
            modal.page++;
            core.tpl('#view-list-container', 'tpl_view_list', result,modal.page>1);

        }, false, true)
    };

    modal.memberinit=function(){
        $(".baosgin").unbind("click").click(function(e){
            e.preventDefault();
            e.stopPropagation();
            var btn=$(this);
            var id=btn.data("id");
            core.json('union/memberactivity/status', {id:id}, function (ret) {
                if(ret.status==0){
                    FoxUI.toast.show(ret.result.message);
                    return
                }
                btn.removeClass("baosgin");
                btn.addClass("jiezhisign");
                btn.html("已报名");
            })


        })
    };
    modal.memeractivity_init=function(params){
        modal.params = $.extend(defaults, params || {});
        var leng = $.trim($('.container').html());
        if (leng == '') {
            modal.page = 1;
            modal.memeractivity_getlist();
        }
        $('.asssview-index-page .fui-content').infinite({
            onLoading: function () {
                modal.memeractivity_getlist()
            }
        });
    };
    modal.memeractivity_getlist=function(){
        modal.params.page = modal.page;

        core.json('union/memberactivity/memberlist', modal.params, function (ret) {
            var result = ret.result;
            if (result.total <= 0) {
                $('.memberlist  .fui-content-inner .content-empty').show();
                $('.page-member-list .fui-content').infinite('stop')
            } else {
                $('.memberlist .fui-content-inner .content-empty').hide();
                $('.page-member-list .fui-content').infinite('init');
                if (result.list.length <= 0 || result.list.length < result.pagesize) {
                    $('.page-member-list .fui-content').infinite('stop')
                }
            }
            modal.page++;
            core.tpl('#member-list-container', 'tpl_member_list', result,modal.page>1);

        }, false, true)
    };

    modal.train_init=function(params){
        modal.params = $.extend(defaults, params || {});
        var leng = $.trim($('.container').html());
        if (leng == '') {
            modal.page = 1;
            modal.train_getlist();
        }
        $('.asssview-index-page .fui-content').infinite({
            onLoading: function () {
                modal.train_getlist()
            }
        });
    };
    modal.train_getlist=function(){
        modal.params.page = modal.page;

        core.json('union/train/memberlist', modal.params, function (ret) {
            var result = ret.result;
            if (result.total <= 0) {
                $('.memberlist  .fui-content-inner .content-empty').show();
                $('.page-member-list .fui-content').infinite('stop')
            } else {
                $('.memberlist .fui-content-inner .content-empty').hide();
                $('.page-member-list .fui-content').infinite('init');
                if (result.list.length <= 0 || result.list.length < result.pagesize) {
                    $('.page-member-list .fui-content').infinite('stop')
                }
            }
            modal.page++;
            core.tpl('#member-list-container', 'tpl_member_list', result,modal.page>1);

        }, false, true)
    };
    modal.readmember_init=function(params){
        modal.params = $.extend(defaults, params || {});
        var leng = $.trim($('.container').html());

        if (leng == '') {
            modal.page = 1;
            modal.readmember_getlist();
        }
        $('.fui-content').infinite({
            onLoading: function () {
                modal.readmember_getlist()
            }
        });
        FoxUI.tab({
            container: $('#tab'), handlers: {
                level1: function () {
                    modal.changeTab(1)
                }, level2: function () {
                    modal.changeTab(2)
                }
            }
        })
    };
    modal.changeTab = function (level) {
        $('.fui-content').infinite('init');
        $('.content-empty').hide(), $('.infinite-loading').show(), $('#container').html('');
        modal.page = 1, modal.params.level = level, modal.readmember_getlist()
    };
    modal.readmember_getlist=function(){
        modal.params.page = modal.page;
        core.json('union/readmember', modal.params, function (ret) {
            var result = ret.result;
            if (result.list.length ==0) {
                $('.fui-content .content-empty').show();
                $('.fui-content').infinite('stop')
            } else {

                $('.fui-content .content-empty').hide();
                $('.fui-content').infinite('init');
                if (result.list.length <= 0 || result.list.length < result.pagesize) {
                    $('.page-member-list .fui-content').infinite('stop')
                }
            }
            core.tpl('#container', 'tpl_member_list', result,modal.page>1);
        }, false, true)
    };
    modal.sign_init=function(params){
        modal.params = $.extend(defaults, params || {});
        var leng = $.trim($('#container').html());

        if (leng == '') {
            modal.page = 1;
            modal.sign_getlist();
        }
        $('.fui-content').infinite({
            onLoading: function () {
                modal.sign_getlist()
            }
        });
    };

    modal.sign_getlist=function(){
        modal.params.page = modal.page;

        core.json('union/report/getmemberlist', modal.params, function (ret) {
            var result = ret.result;
            if (result.list.length ==0) {
                $('.fui-content .content-empty').show();
                $('.fui-content').infinite('stop')
            } else {

                $('.fui-content .content-empty').hide();
                $('.fui-content').infinite('init');
                if (result.list.length <= 0 || result.list.length < result.pagesize) {
                    $('.fui-content').infinite('stop')
                }
            }
            modal.page++;
            core.tpl('#container', 'tpl_list', result,modal.page>1);
        }, false, true)
    };
    modal.leavmessage_init=function(params){
        modal.params = $.extend(defaults, params || {});
        $('#container').html("");

        var leng = $.trim($('#container').html());

        if (leng == '') {
            modal.page = 1;
            modal.leavmessage_getlist();
        }
        $('.fui-content').infinite({
            onLoading: function () {
                modal.leavmessage_getlist()
            }
        });
    };
    modal.leavmessage_getlist=function(){
        modal.params.page = modal.page;
        core.json('union/leavmessage/getlist', modal.params, function (ret) {
            var result = ret.result;
            if (result.list.length ==0) {
                $('.fui-content .content-empty').show();
                $('.fui-content').infinite('stop')
            } else {

                $('.fui-content .content-empty').hide();
                $('.fui-content').infinite('init');

                if (result.list.length <= 0 || result.list.length < result.pagesize) {
                    $('.fui-content').infinite('stop')
                }
                modal.page++;
            }
            core.tpl('#container', 'tpl_list', result,modal.page>1);
        }, false, true)
    };
    modal.leavreply_init=function (params) {
        modal.params = $.extend(defaults, params || {});
        $('#container').html("");
        console.log( $('#container').html());
        var leng = $.trim( $('#container').html());

        if (leng == '') {
            modal.page = 1;
            modal.leavreply_getreplaylist();
        }
        $('.fui-content').infinite({
            onLoading: function () {
                modal.leavreply_getreplaylist()
            }
        });
    };

    modal.leavreply_getreplaylist=function () {
        modal.params.page = modal.page;
        core.json('union/leavmessage/getreplaylist', modal.params, function (ret) {
            var result = ret.result;
            if (result.list.length ==0) {
                $('.fui-content .content-empty').show();
                $('.fui-content').infinite('stop')
            } else {
                $('.fui-content .content-empty').hide();
                $('.fui-content').infinite('init');
                if (result.list.length <= 0 || result.list.length < result.pagesize) {
                    $('.fui-content').infinite('stop')
                }
                modal.page++;
            }
            core.tpl('#container', 'tpl_getreplay_list', result,modal.page>1);
        }, false, true)
    }
    return modal;
});