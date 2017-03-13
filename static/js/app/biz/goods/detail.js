define(['core', 'tpl', 'biz/goods/picker', 'biz/member/favorite', 'biz/member/cart', 'biz/plugin/diyform'], function (core, tpl, picker, favorite, cart, diyform) {
    var modal = {};
    modal.init = function (params) {
        modal.seckillinfo = params.seckillinfo;
        modal.goodsid = params.goodsid;
        modal.optionid = 0;
        modal.total = 1;
        modal.getComments = params.getComments;
        modal.log_id=params.log_id;
        FoxUI.tab({
            container: $('#tab'), handlers: {
                tab1: function () {
                    $('.basic-block').show();
                    modal.hideDetail();
                    modal.hideParams();
                    modal.hideComment()
                }, tab2: function () {
                    modal.hideParams();
                    modal.hideComment();
                    modal.showDetail()
                }, tab3: function () {
                    modal.showParams();
                    modal.hideComment()
                }, tab4: function () {
                    modal.showComment()
                }
            }
        });
        modal.hideDetail();
        modal.hideParams();
        modal.hideComment();
        $('.option-selector').click(function () {
            modal.optionPicker('')
        });
        //二维码分享
        $('#alert-click').on("click",function(){
         $("#alert-picker").show();
         });
         $('.alert-close').on("click",function(){
         $("#alert-picker").hide();
         });
         $('#alert-mask').on("click",function(){
         $("#alert-picker").hide();
         });
        $('#city-picker').click(function () {
            modal.salePicker = new FoxUIModal({
                content: $('#city-picker-modal').html(),
                extraClass: 'picker-modal',
                maskClick: function () {
                    modal.salePicker.close()
                }
            });
            modal.salePicker.container.find('.btn-danger').click(function () {
                modal.salePicker.close()
            });
            modal.salePicker.show()
        });
        $('#sale-picker').click(function () {
            modal.salePicker = new FoxUIModal({
                content: $('#sale-picker-modal').html(),
                extraClass: 'picker-modal',
                maskClick: function () {
                    modal.salePicker.close()
                }
            });

            FoxUI.according.init();

            modal.salePicker.container.find('.btn-danger').click(function () {
                modal.salePicker.close()
            });
            modal.salePicker.show()
        });
        $(".bottom-buttons .cartbtn").click(function () {
            modal.optionPicker('cart')
        });
        $(".bottom-buttons .buybtn").click(function () {
            modal.optionPicker('buy')

        });
        if( $('#time-container').length>0 || $('#discount-container').length>0){

            $('.fui-labeltext').timer({
                onEnd: function () {
                    location.reload();
                },onStart:function(){
                    location.reload();
                }
            });
        }else if( $('.seckill-container').length>0){
            modal.initSeckill();
        }

        $('.favorite-item').click(function () {
            var self = $(this);
            if (self.attr('submit') == '1') {
                return
            }
            self.attr('submit', 1);
            var isfavorite = self.attr('data-isfavorite') == '1';
            var icon = self.find('.icon');
            icon.removeClass('icon-like').removeClass('icon-likefill');
            isfavorite && icon.addClass('icon-like');
            !isfavorite && icon.addClass('icon-likefill');
            self.toggleClass('active');
            if (!isfavorite) {
                icon.addClass('fav').transitionEnd(function () {
                    icon.removeClass('fav')
                })
            }
            isfavorite = self.attr('data-isfavorite') == '1' ? 0 : 1;
            favorite.toggle(modal.goodsid, isfavorite, function (is) {
                self.removeAttr('submit').attr("data-isfavorite", is ? "1" : 0)
            })
        });

        if(core.isWeixin()){
            $('#btn-share').click(function () {
                $('#cover').fadeIn(200)
            });
            $('#cover').click(function () {
                $('#cover').hide()
            });
        }else{
            $('#btn-share').click(function () {
                if(core.ish5app()){
                    return;
                }
                FoxUI.alert("请复制链接发送给好友");
            });
        }

        if (modal.getComments) {
            core.json('goods/detail/get_comments', {id: modal.goodsid}, function (ret) {
                var result = ret.result;
                $(".fui-icon-col[data-level='all']").find('.count').html(result.count.all);
                $(".fui-icon-col[data-level='good']").find('.count').html(result.count.good);
                $(".fui-icon-col[data-level='normal']").find('.count').html(result.count.normal);
                $(".fui-icon-col[data-level='bad']").find('.count').html(result.count.bad);
                $(".fui-icon-col[data-level='pic']").find('.count').html(result.count.pic);

                if (ret.status == 1 && result.list.length > 0) {
                    modal.initComment();
                    $('#tabcomment').show();
                    core.tpl('#comments-container', 'tpl_goods_detail_comments', ret.result);
                    $('#comments-container .fui-cell:first-child').click(function () {
                        $("#tabcomment").click()
                    });
                    $('#comments-container').lazyload()
                };
                core.showImages('#comments-container .remark.img img');
            });

        }
        $('.basic-block').pullToLoading({
            onLoadingReady: function () {
                $(".look-detail").html("<i class='icon icon-unfold'></i> <span>释放查看图文详情</span>")
            }, onLoading: function () {
                $(".look-detail").html("<i class='icon icon-fold'></i> <span>上拉查看图文详情</span>");
                $('.basic-block').pullToLoading('done');
                modal.showDetail();
                $('#tab .tab.active').removeClass('active');
                $('#tab .tab:eq(1)').addClass('active')
            }
        });

        if (typeof(window.cartcount) !== 'undefined') {
            picker.changeCartcount(window.cartcount)
        };
        core.showImages('.fui-swipe .fui-swipe-item img');

        /*赠品*/
        $(".fui-cell-giftclick").click(function(){

            modal.giftPicker = new FoxUIModal({
                content: $('#gift-picker-modal').html(),
                extraClass: 'picker-modal',
                maskClick: function () {
                    modal.giftPicker.close();
                }
            });
            modal.giftPicker.container.find('.btn-danger').click(function () {
                modal.giftPicker.close()
            });
            modal.giftPicker.show();

            var giftid = $("#giftid").val();
            $(".gift-item").each(function(){
                if($(this).val()==giftid){
                    $(this).prop("checked","true");
                }
            });

            $(".gift-item").on("click",function(){
                $.ajax({
                    url: core.getUrl('goods/detail/querygift', {id: $(this).val()}),
                    cache: true,
                    success: function (data) {
                        data = window.JSON.parse(data) ;
                        if(data.status > 0){
                            $("#giftid").val(data.result.id);
                            $("#gifttitle").text(data.result.title);
                        }
                    }
                })
            });
        });
    };
    /*赠品*/
    modal.giftPicker = function (){
        modal.giftPicker = new FoxUIModal({
            content: $('#option-picker-modal').html(),
            extraClass: 'picker-modal',
            maskClick: function () {
                modal.packagePicker.close()
            }
        });
    };
    modal.getDetail = function () {
        if ($('.detail-block').find('.content-block').html() != '') {
            return
        }
        FoxUI.loader.show('mini');
        $.ajax({
            url: core.getUrl('goods/detail/get_detail', {id: modal.goodsid}),
            cache: true,
            success: function (html) {
                FoxUI.loader.hide();
                $('.detail-block').find('.content-block').html(html);


                $('.detail-block').pullToRefresh({
                    onRefreshReady: function () {
                        $(".look-basic").html("<i class='icon icon-fold'></i> <span>释放返回商品详情</span>")
                    }, onRefresh: function () {
                        $(".look-basic").html("<i class='icon icon-unfold'></i> <span>下拉返回商品详情</span>");
                        $('.basic-block').show();
                        $('.detail-block').removeClass('in').pullToRefresh('done');
                        $('#tab .tab.active').removeClass('active');
                        $('#tab .tab:first-child').addClass('active')
                    }
                });

                setTimeout(function () {
                    $('.detail-block').lazyload();
                    core.showImages('.content-block img');
                }, 10)
            }
        })
    };

    modal.showDetail = function () {
        $('.basic-block').hide();
        modal.getDetail();
        $('.detail-block').transition(300).addClass('in').transitionEnd(function () {
            $('.detail-block').transition('')
        })
    };
    modal.hideDetail = function () {
        $('.basic-block').show();
        $('.detail-block').transition(300).removeClass('in').transitionEnd(function () {
            $('.detail-block').transition('')
        })
    };
    modal.showParams = function () {
        $('.param-block').show().addClass('in')
    };
    modal.hideParams = function () {
        $('.param-block').removeClass('in')
    };
    modal.optionPicker = function (action) {
       // console.log(modal.total);
     
        picker.open({
            goodsid: modal.goodsid,
            total: modal.total,
            log_id:modal.log_id,
            split: ';',
            action:action,
            optionid: modal.optionid,
            showConfirm: true,
            autoClose: false,
            mustbind: modal.mustbind,
            backurl: modal.backurl,
            onConfirm: function (total, optionid, optiontitle) {
                modal.total = total;

                modal.optionid = optionid;
                $('.option-selector').html("已选: 数量x" + total + " " + optiontitle);
                if (action == 'buy') {
                    var giftid =  $("#giftid").val();
                    if($("#giftid") && giftid==''){
                        FoxUI.alert("请选择赠品！");
                        $(".picker-modal").remove();
                    }else{
                        if ($('.diyform-container').length > 0) {
                            var diyformdata = diyform.getData('.diyform-container');
                            if (!diyformdata) {
                                return
                            } else {
                                core.json('order/create/diyform', {
                                    id: modal.goodsid,
                                    diyformdata: diyformdata
                                }, function (ret) {
                                    $.router.load(core.getUrl('order/create', {
                                        id: modal.goodsid,
                                        optionid: modal.optionid,
                                        total: modal.total,
                                        log_id:modal.log_id,
                                        gdid: ret.result.goods_data_id,
                                        giftid:giftid
                                    }), true);
                                }, true, true);
                                picker.close()
                            }
                        } else {
                            picker.close();
                            $.router.load(core.getUrl('order/create', {
                                id: modal.goodsid,
                                optionid: modal.optionid,
                                total: modal.total,
                                log_id:modal.log_id,
                                giftid:giftid
                            }), false);
                        }
                    }
                } else if (action == 'cart') {
                    if ($('.diyform-container').length > 0) {
                        var diyformdata = diyform.getData('.diyform-container');
                        if (!diyformdata) {
                            return
                        } else {
                            core.json('order/create/diyform', {
                                id: modal.goodsid,
                                diyformdata: diyformdata
                            }, function (ret) {
                                cart.add(modal.goodsid, modal.optionid, modal.total, diyformdata, function (ret) {
                                    $('.cart-item').find('.badge').html(ret.cartcount).removeClass('out').addClass('in');
                                    window.cartcount = ret.cartcount
                                });
                            }, true, true);
                            picker.close()
                        }
                    } else {
                        cart.add(modal.goodsid, modal.optionid, modal.total, false, function (ret) {
                            $('.cart-item').find('.badge').html(ret.cartcount).removeClass('out').addClass('in');
                            window.cartcount = ret.cartcount
                        });
                        picker.close()
                    }
                } else {
                    picker.close()
                }
            }
        })
    };
    modal.showComment = function () {
        $('.comment-block').show().addClass('in').transitionEnd(function () {
            if (!$('.comment-block').attr('loaded')) {
                $('.comment-block').attr('loaded', 1);
                modal.getCommentList()
            }
        })
    };
    modal.hideComment = function () {
        $('.comment-block').removeClass('in')
    };
    modal.initComment = function () {
        modal.commentPage = 1;
        modal.commentLevel = '';
        modal.commentCount = 1;
        $('.fui-content').infinite({
            onLoading: function () {
                modal.getCommentList()
            }
        });
        if (modal.commentPage == 1) {
            modal.getCommentList()
        }
    };
    modal.getCommentList = function () {
        $('#comments-list-container .content-empty').hide();
        $('#comments-list-container .infinite-loading').show();
        core.json('goods/detail/get_comment_list', {
            id: modal.goodsid,
            page: modal.commentPage,
            level: modal.commentLevel,
            getcount: modal.commentCount
        }, function (ret) {
            var result = ret.result;
            if (result.total <= 0) {
                $('#comments-list-container .container').html('').hide();
                $('#comments-list-container .content-empty').show();
                $('#comments-list-container').infinite('stop')
            } else {
                $('#comments-list-container .container').show();
                $('#comments-list-container .content-empty').hide();
                $('#comments-list-container').infinite('init');
                if (result.list.length <= 0 || result.list.length < result.pagesize) {
                    $('#comments-list-container').infinite('stop')
                }
            }
            modal.commentCount = 0;
            modal.commentPage++;
            core.tpl('#comments-list-container .container', 'tpl_goods_detail_comments_list', result, modal.commentPage > 1);
            $('#comments-list-container .fui-icon-group .fui-icon-col').unbind('click').click(function () {
                $('#comments-list-container .fui-icon-group .fui-icon-col span.text-danger').removeClass('text-danger');
                $(this).find('span').addClass('text-danger');
                modal.commentPage = 1;
                modal.commentCount = 1;
                modal.commentLevel = $(this).data('level');
                $('#comments-list-container .container').html('');
                modal.getCommentList()
            });
            core.showImages('#comments-all .remark.img img');
        }, false);
    };


    modal.formatSeconds  =function(value){
        var theTime = parseInt(value);// 秒
        var theTime1 = 0;// 分
        var theTime2 = 0;// 小时
        if(theTime > 60) {
            theTime1 = parseInt(theTime/60);
            theTime = parseInt(theTime%60);
            if(theTime1 > 60) {
                theTime2 = parseInt(theTime1/60);
                theTime1 = parseInt(theTime1%60);
            }
        }
        return {
            'hour': theTime2<10? '0' +theTime2: theTime2  ,
            'min':  theTime1<10? '0' +theTime1: theTime1  ,
            'sec':  theTime<10? '0' +theTime: theTime
        }
    };
    
    modal.initSeckill = function(){
        var container = $('.seckill-container'),starttime = container.data('starttime'),endtime = container.data('endtime'),status = container.data('status') || 0;

        $.ajax({url: '../addons/ewei_shopv2/map.json', complete:function(x){
            currenttime = +new Date(x.getResponseHeader("Date"))/1000;

            if(status==0){
                modal.lasttime = endtime - currenttime;
            }else{
                modal.lasttime = starttime - currenttime;
            }
            clearInterval(modal.timer);
            modal.setSeckillTimer();
            modal.timer = modal.setSeckillTimerInterval();
            
        }});
    };

    modal.setSeckillTimer = function(){

        modal.lasttime-=1;
        var times =  modal.formatSeconds(modal.lasttime) ;
        var obj = $('.seckill-container');
        obj.find('.time-hour').html( times.hour);
        obj.find('.time-min').html( times.min);
        obj.find('.time-sec').html( times.sec);

        if( modal.lasttime<=0 ){
             location.reload();
        }
    };

    modal.setSeckillTimerInterval = function(){
        return setInterval(function(){
            modal.setSeckillTimer();
        },1000);
    };
    
    return modal
});