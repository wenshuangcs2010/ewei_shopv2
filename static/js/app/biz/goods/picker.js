define(['core', 'tpl', 'biz/member/cart', 'biz/plugin/diyform'], function (core, tpl, cart, diyform) {
    var modal = {
        goodsid: 0,
        goods: [],
        option: false,
        specs: [],
        options: [],
        params: {
            titles: '',
            optionthumb: '',
            split: ';',
            option: false,
            total: 1,
            optionid: 0,
            onSelected: false,
            onConfirm: false,
            autoClose: true
        }
    };
    modal.open = function (params) {

        modal.params = $.extend(modal.params, params || {});
        if (modal.goodsid != params.goodsid) {
            modal.goodsid = params.goodsid;
            core.json('goods/picker', {id: params.goodsid}, function (ret) {
                if (ret.status == 0) {
                    FoxUI.toast.show('未找到商品!');
                    return
                }
                modal.followtip = '';
                modal.followurl = '';
                if (ret.status == 2) {
                    modal.followtip = ret.result.followtip;
                    modal.followurl = ret.result.followurl;
                    modal.show();
                    return
                };
                if (ret.status == 4) {
                    modal.needlogin = 1;
                    modal.show();
                    return
                };
                if (ret.status == 3) {
                    modal.mustbind = 1;
                    modal.show();
                    return
                };
                //预售
                if (ret.status == 5) {
                    FoxUI.toast.show(ret.result.message);
                    modal.goodsid = '';
                    return;
                };
                modal.containerHTML = tpl('option-picker', ret.result);
                modal.goods = ret.result.goods;
                modal.specs = ret.result.specs;
                modal.options = ret.result.options;
                modal.seckillinfo = ret.result.seckillinfo;
                if (modal.goods.unit == '') {
                    modal.goods.unit = '件'
                }
                modal.show()
            }, true, false)
        } else {
            modal.show()
        }
    };
    modal.close = function () {
        modal.container.close()
    };
    modal.init = function () {
       
        $('.closebtn', modal.container.container).unbind('click').click(function () {
            modal.close()
        });
        $('.fui-mask').unbind('click').click(function () {
            modal.close()
        });

        if( modal.seckillinfo == false ){
            
            $('.fui-number', modal.container.container).numbers({
                value: modal.params.total,
                max: modal.goods.maxbuy,
                min: modal.goods.minbuy,
                minToast: "{min}" + modal.goods.unit + "起售",
                maxToast: "最多购买{max}" + modal.goods.unit,
                callback: function (num) {
                    modal.params.total = num;

                }
            });

        }else{
            modal.params.total = 1;
        }
        $(".spec-item", modal.container.container).unbind('click').click(function () {
            modal.chooseSpec(this);

        });
        $('.cartbtn', modal.container.container).unbind('click').click(function () {
             modal.addToCart();
        });

        $('.buybtn', modal.container.container).unbind('click').click(function () {
             
            if ($(this).hasClass('disabled')) {
                return
            }
            if (!modal.check()) {
                return
            }

            if ($('.diyform-container').length > 0) {
                var diyformdata = diyform.getData('.diyform-container');
                if (!diyformdata) {
                    return
                } else {
                    core.json('order/create/diyform', {
                        id: modal.goods.id,
                        diyformdata: diyformdata
                    }, function (ret) {
                        location.href = core.getUrl('order/create', {
                            id: modal.goods.id,
                            optionid: modal.params.optionid,
                            total: modal.params.total,
                            gdid: ret.result.goods_data_id
                        });
                    }, true, true);
                }
            } else {
                location.href = core.getUrl('order/create', {
                    id: modal.goods.id,
                    optionid: modal.params.optionid,
                    total: modal.params.total
                });
            }
            if (modal.params.autoClose) {
                modal.close()
            }
        });
        $('.confirmbtn', modal.container.container).unbind('click').click(function () {
            if ($(this).hasClass('disabled')) {
                return
            }
            if (!modal.check()) {
                return
            }
           
            if (modal.params.onConfirm) {
                modal.params.total = parseInt($('.num', modal.container.container).val());

                modal.params.onConfirm(modal.params.total, modal.params.optionid, modal.params.titles, modal.params.optionthumb)
            }

            if (modal.params.autoClose) {
                modal.close()
            }
        });

        var height = $(document.body).height();

        modal.container.container.find('.option-picker').css('max-height', height - 50);
        modal.container.container.find('.option-picker .option-picker-options').css('max-height', height - 225)
    };
    modal.addToCart = function(){

        if (!modal.goods.canAddCart) {
            FoxUI.toast.show('此商品不可加入购物车<br>请直接点击立刻购买');
            return
        }

        if ($(this).hasClass('disabled')) {
            return
        }
        if (!modal.check()) {
            return
        }
        modal.params.total = parseInt($('.num', modal.container.container).val());
        if ($('.diyform-container').length > 0) {
            FoxUI.loader.show('mini');
            var diyformdata = diyform.getData('.option-picker .diyform-container');
            FoxUI.loader.hide();
            if (!diyformdata) {
                return
            }
            cart.add(modal.goodsid, modal.params.optionid, modal.params.total, diyformdata, function (ret) {
                FoxUI.toast.show('添加成功');
                modal.changeCartcount(ret.cartcount)
            })
        } else {
            cart.add(modal.goodsid, modal.params.optionid, modal.params.total, false, function (ret) {
                
                FoxUI.toast.show('添加成功');
                modal.changeCartcount(ret.cartcount)
            })
        }
        if (modal.params.autoClose) {
            modal.close()
        };

    };
    modal.show = function () {
        if (modal.followtip) {
            FoxUI.confirm(modal.followtip, function () {
                if (modal.followurl != '' && modal.followurl != null) {
                    location.href = modal.followurl
                }
            });
            return
        }
        if (modal.needlogin) {
            var backurl = core.getUrl('goods/detail', {id: modal.goodsid});
            backurl = backurl.replace("./index.php?", "");
            FoxUI.confirm("请先登录", "提示", function () {
                location.href = core.getUrl('account/login', {backurl: btoa(backurl)})
            });
            return
        }
        if (modal.mustbind) {
            FoxUI.alert("请先绑定手机", "提示", function () {
                location.href = core.getUrl('member/bind', {backurl: btoa(location.href)})
            });
            return
        }



        modal.container = new FoxUIModal({content: modal.containerHTML, extraClass: "picker-modal"});
        modal.init();


        if(modal.seckillinfo && modal.seckillinfo.status==0){

            $('.fui-mask').hide(),$('.picker-modal').hide();
            if((typeof( modal.options.length ) ==='undefined' || modal.options.length <=0 )&& $('.diyform-container').length <=0){
                if( modal.params.action=='buy') {
                    location.href = core.getUrl('order/create',{id: modal.goods.id ,total: 1,optionid:0 });
                    return;
                } else{
                    modal.addToCart();
                    return;
                }
            }
        }
        $('.fui-mask').show(),$('.picker-modal').show();
        if (modal.params.showConfirm) {
            $('.confirmbtn', modal.container.container).show();
        } else {
            $('.buybtn', modal.container.container).show();
            if (modal.goods.canAddCart) {
                $('.cartbtn', modal.container.container).show()
            }
        }
        if (modal.params.optionid != '0') {
            modal.initOption()
        }
        modal.container.show();
        //验证规格库存
        if(modal.specs.length==1){
            $.each(modal.options, function () {
                var thisspecs = this.specs;
                if(this.stock==0){
                    $(".spec-item"+thisspecs+"").removeClass("spec-item").removeClass("btn-danger").addClass("disabled").off("click");
                }
            });
        }
    };
    modal.initOption = function () {
        $(".spec-item").removeClass('btn-danger');
        var optionid = modal.params.optionid;
        var specs = false;
        $.each(modal.options, function () {
            if (this.id == optionid) {
                specs = this.specs.split('_');
                return false
            }
        });
        if (specs) {
            var item = false;
            var selectitems = [];
            $(".spec-item").each(function () {
                var item = $(this), itemid = item.data('id');
                $.each(specs, function () {
                    if (this == itemid) {
                        selectitems.push(item);
                        item.addClass('btn-danger')
                    }
                })
            });
            if (selectitems.length > 0) {
                var lastitem = selectitems[selectitems.length - 1];
                modal.chooseSpec(lastitem, false)
            }
        }
    };
    modal.chooseSpec = function (obj, callback) {
        var $this = $(obj);

        $this.closest('.spec').find('.spec-item').removeClass('btn-danger'), $this.addClass('btn-danger');
        var thumb = $this.data('thumb') || '';
        if (thumb) {
            $('.thumb', modal.container.container).attr('src', thumb)
        }
        modal.params.optionthumb = thumb;
        var selected = $(".spec-item.btn-danger", modal.container.container);
        var itemids = [];
        //判断规格库存为0，规格不可选
        if (selected.length <= modal.specs.length){
            $.each(modal.options, function () {
                if((modal.specs.length-selected.length) == 1){
                    var specid = [];
                    var specOpion = this.specs;
                    $.each(selected,function () {
                        if(specOpion.indexOf(this.getAttribute("data-id"))>=0){
                            specid.push(this.getAttribute("data-id"))
                        }
                    });
                    if(specid.length==selected.length){
                        for (var i=0;i<specid.length;i++){
                            specOpion = specOpion.replace(specid[i],"");
                        }
                        specOpion = specOpion.split("_");
                        var option = [];
                        $.each(specOpion,function(i,v){
                            var data = $.trim(v);//$.trim()函数来自jQuery
                            if('' != data){
                                option.push(data);
                            }
                        });
                        if(this.stock<=0 && this.stock != -1){
                            $(".spec-item"+option[0]+"").removeClass("spec-item").removeClass("btn-danger").addClass("disabled").off("click");
                        }else{
                            $(".spec-item"+option[0]+"").removeClass("disabled").addClass("spec-item").off("click").on("click",function () {
                                modal.chooseSpec(this)
                            });
                        }
                    }

                }else if(modal.specs.length==selected.length){
                    var specid = [];
                    var specOpion = this.specs;
                    $.each(selected,function () {
                        if(specOpion.indexOf(this.getAttribute("data-id"))>=0 && specOpion.indexOf($this.data("id"))>=0){
                            specid.push(this.getAttribute("data-id"))
                        }
                    });
                    var option = [];
                    if(specid.length==(modal.specs.length-1)){
                        for (var i=0;i<specid.length;i++){
                            specOpion = specOpion.replace(specid[i],"");
                        }
                        specOpion = specOpion.split("_");
                        $.each(specOpion,function(i,v){
                            var data = $.trim(v);//$.trim()函数来自jQuery
                            if('' != data){
                                option.push(data);
                            }
                        });
                        if(this.stock<=0 && this.stock != -1){
                            $(".spec-item"+option[0]+"").removeClass("spec-item").removeClass("btn-danger").addClass("disabled").off("click");
                        }else{
                            $(".spec-item"+option[0]+"").removeClass("disabled").addClass("spec-item").off("click").on("click",function () {
                                modal.chooseSpec(this)
                            });
                        }
                    }
                }


            });
        }
        if (selected.length == modal.specs.length) {
            selected.each(function () {
                itemids.push($(this).data('id'))
            });
            $.each(modal.options, function () {
                var specs = this.specs.split('_').sort().join('_');
                if (specs == itemids.sort().join('_')) {
                    var stock = this.stock == '-1' ? '无限' : this.stock;
                    $('.total', modal.container.container).html(stock);
                    if (this.stock != '-1' && this.stock <= 0) {
                        $('.confirmbtn', modal.container).show().addClass('disabled').html('库存不足');
                        $('.cartbtn,.buybtn', modal.container).hide()
                    } else {
                        if (modal.params.showConfirm) {
                            $('.confirmbtn', modal.container).removeClass('disabled').html('确定');
                            $('.cartbtn,.buybtn', modal.container).hide()
                        } else {
                            $('.cartbtn,.buybtn', modal.container).show(), $('.confirmbtn').hide()
                        }
                    }
                    var timestamp = Date.parse(new Date()) / 1000;
                    if(modal.goods.ispresell>0 && (modal.goods.preselltimeend==0 || modal.goods.preselltimeend > timestamp)){
                        $('.price', modal.container.container).html(this.presellprice);
                    }else{
                        $('.price', modal.container.container).html(this.marketprice);
                    }

                    modal.option = this;
                    modal.params.optionid = this.id
                }
            })
        }
        var titles = [];
        selected.each(function () {
            titles.push($.trim($(this).html()))
        });
        modal.params.titles = titles.join(modal.params.split);
        $('.info-titles', modal.container.container).html('已选 ' + modal.params.titles);
        if (callback) {
            if (modal.params.onSelected) {
                modal.params.onSelected(modal.params.total, modal.params.optionid, modal.params.titles)
            }
        }
    };
    modal.check = function () {
        var spec = $(".spec", modal.container.container);
        var selected = true;
        spec.each(function () {
            if ($(this).find('.spec-item.btn-danger').length <= 0) {
                FoxUI.toast.show('请选择' + $(this).find('.title').html());
                selected = false;
                return false
            }
        });
        if (selected) {
            if (modal.option.stock != -1 && modal.option.stock <= 0) {
                FoxUI.toast.show('库存不足');
                return false
            }
            var num = parseInt($('.num', modal.container.container).val());
            if (num <= 0) {
                num = 1
            }
            if (num > modal.option.stock) {
                num = modal.option.stock
            }
            $(".num", modal.container.container).val(num);
            if (modal.goods.maxbuy > 0 && num > modal.goods.maxbuy) {
                FoxUI.toast.show('最多购买 ' + modal.goods.maxbuy + ' ' + modal.goods.unit);
                return false
            }
            if (modal.goods.minbuy > 0 && num < modal.goods.minbuy) {
                FoxUI.toast.show(modal.goods.minbuy + modal.goods.unit + '起售');
                return false
            }
            return true
        }
        return false
    };
    modal.changeCartcount = function (count) {
        if ($("#menucart").length > 0) {
            var badge = $("#menucart").find(".badge");
            if (badge.length < 1) {
                $("#menucart").append('<span class="badge">' + count + '</div>')
            } else {
                badge.text(count)
            }
        }
    };
    return modal
});