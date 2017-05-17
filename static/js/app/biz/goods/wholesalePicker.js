define(['core', 'tpl', 'biz/member/cart', 'biz/plugin/diyform', 'swiper'], function (core, tpl, cart, diyform, swiper) {
    var modal = {
        goodsid: 0,
        goods: [],
        option: false,
        spec1items: [],
        spec2items: [],
        options: [],
        hasoption: 0,
        buyoptions: [],
        params: {selectoption: [], onConfirm: false, autoClose: true}
    };
    modal.open = function (params) {
        modal.params = $.extend(modal.params, params || {});
        if (modal.goodsid != params.goodsid) {
            modal.goodsid = params.goodsid;
            core.json('goods/wholesalepicker', {id: params.goodsid}, function (ret) {
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
                }
                if (ret.status == 4) {
                    modal.needlogin = 1;
                    modal.show();
                    return
                }
                if (ret.status == 3) {
                    modal.mustbind = 1;
                    modal.show();
                    return
                }
                modal.containerHTML = tpl('option-wholesalepicker', ret.result);
                modal.goods = ret.result.goods;
                modal.spec1items = ret.result.spec1items;
                modal.spec2items = ret.result.spec2items;
                modal.options = ret.result.options;
                modal.hasoption = ret.result.hasoption;
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
        if (modal.hasoption == 2) {
            $('.btnitem1', modal.container.container).unbind('click').click(function () {
                var item1id = $(this).data('id');
                $('.btnitem1', modal.container.container).removeClass('active');
                $(this).addClass('active');
                $('.body', modal.container.container).hide();
                $('#body_' + item1id, modal.container.container).show()
            });
            var item1id = modal.spec1items[0].id;
            $('.btnitem1', modal.container.container).removeClass('active');
            $('#item1_' + item1id, modal.container.container).addClass('active');
            $('.body', modal.container.container).hide();
            $('#body_' + item1id, modal.container.container).show()
        }
        $('.cartbtn', modal.container.container).unbind('click').click(function () {
            modal.addToCart()
        });
        $('.buybtn', modal.container.container).unbind('click').click(function () {
            if ($(this).hasClass('disabled')) {
                return
            }
            if (!modal.check()) {
                return
            }
            location.href = core.getUrl('order/create', {
                id: modal.goods.id,
                iswholesale: 1,
                buyoptions: window.JSON.stringify(modal.buyoptions)
            });
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
                modal.params.onConfirm(modal.buyoptions)
            }
            if (modal.params.autoClose) {
                modal.close()
            }
        });
        var swiper = new Swiper('.swiper-container', {
            paginationClickable: true,
            slidesPerView: 3,
            nextButton: '.go-right',
            prevButton: '.go-left',
        });
        $('.closebtn', modal.container.container).unbind('click').click(function () {
            modal.close()
        });
        $('.fui-mask').unbind('click').click(function () {
            modal.close()
        });
        $('.fui-number', modal.container.container).each(function () {
            var stock = $(this).prev().find('span').html();
            var $parent = $(this).parents(".optiondata");
            if (stock > 0) {
                $(this).numbers({
                    value: 0,
                    min: 0,
                    max: stock,
                    canzero: 1,
                    maxToast: "不能大于剩余库存",
                    callback: function (total) {
                        $parent.attr('data-total', total);
                        modal.caculate()
                    }
                })
            } else {
                $(this).find(".num").attr("disabled", true)
            }
        });
    };
    modal.caculate = function () {
        var intervalfloor = modal.goods.intervalfloor;
        var total = 0;
        var price = 0;
        var totalprice = 0;
        var buyoption = [];
        $('.optiondata', modal.container.container).each(function () {
            var num = parseInt($(this).attr("data-total"));
            total = total + num;
            var optionid = $(this).attr("data-optionid");
            buyoption[buyoption.length] = {'optionid': optionid, 'total': num}
        });
        modal.buyoptions = buyoption;
        if (modal.hasoption == 2) {
            $('.body', modal.container.container).each(function () {
                var id = $(this).attr("data-id");
                var total2 = 0;
                $(this).find('.optiondata').each(function () {
                    total2 = total2 + parseInt($(this).attr("data-total"))
                });
                if (total2 > 0) {
                    $('#item1_' + id, modal.container.container).find('.dot').show().html(total2)
                } else {
                    $('#item1_' + id, modal.container.container).find('.dot').hide().html("")
                }
            })
        }
        var intervalnum1 = modal.goods.intervalnum1;
        var intervalprice1 = modal.goods.intervalprice1;
        var intervalnum2 = modal.goods.intervalnum2;
        var intervalprice2 = modal.goods.intervalprice2;
        var intervalnum3 = modal.goods.intervalnum3;
        var intervalprice3 = modal.goods.intervalprice3;

        if (total >= intervalnum3 && modal.goods.intervalfloor > 2) {
            $('#UnitPrice', modal.container.container).html("单价: ￥" + intervalprice3 + "/件 共" + total + "件");
            var totalprice = intervalprice3 * total;
            $('#totalprice', modal.container.container).html(totalprice.toFixed(2));
            $('#again', modal.container.container).hide()
        } else if (total >= intervalnum2 && modal.goods.intervalfloor > 1) {
            $('#UnitPrice', modal.container.container).html("单价: ￥" + intervalprice2 + "/件 共" + total + "件");
            var totalprice = intervalprice2 * total;
            $('#totalprice', modal.container.container).html(totalprice.toFixed(2));
            if (modal.goods.intervalfloor > 2) {
                var need = intervalnum3 - total;
                $('#again', modal.container.container).show().html("再购买" + need + "件享受￥" + intervalprice3 + "/件的价格")
            }
            else
            {
                $('#again', modal.container.container).hide();
            }
        } else if (total >= intervalnum1 && modal.goods.intervalfloor > 0) {
            $('#UnitPrice', modal.container.container).html("单价: ￥" + intervalprice1 + "/件 共" + total + "件");
            var totalprice = intervalprice1 * total;
            $('#totalprice', modal.container.container).html(totalprice.toFixed(2));
            if (modal.goods.intervalfloor > 1) {
                var need = intervalnum2 - total;
                $('#again', modal.container.container).show().html("再购买" + need + "件享受￥" + intervalprice2 + "/件的价格")
            }else
            {
                $('#again', modal.container.container).hide();
            }
        } else if (total < intervalnum1) {
            var need = intervalnum1 - total;
            $('#again', modal.container.container).show().html("再购买" + need + "件达到最低起批数量");
            $('#UnitPrice', modal.container.container).html('您已选购' + total + "件商品");
            $('#totalprice', modal.container.container).html(0)
        }
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
        modal.caculate();
        $('.fui-mask').show(), $('.picker-modal').show();
        if (modal.params.showConfirm) {
            $('.confirmbtn', modal.container.container).show()
        } else {
            $('.buybtn', modal.container.container).show();
            if (modal.goods.canAddCart) {
                $('.cartbtn', modal.container.container).show()
            }
        }
        modal.container.show()
    };
    modal.addToCart = function () {
        if ($(this).hasClass('disabled')) {
            return
        }
        if (!modal.check()) {
            return
        }
        modal.params.total = parseInt($('.num', modal.container.container).val());
        cart.addwholesale(modal.goodsid, modal.buyoptions, function (ret) {
            FoxUI.toast.show('添加成功');
            modal.changeCartcount(ret.cartcount)
        });
        if (modal.params.autoClose) {
            modal.close()
        }
    };
    modal.check = function () {
        if (modal.goods.intervalfloor <= 0) {
            FoxUI.toast.show('商品价格信息错误!');
            return false
        }
        var total = 0;
        for (var i = 0; i < modal.buyoptions.length; i++) {
            total = total + parseInt(modal.buyoptions[i].total)
        }
        if (modal.goods.intervalnum1 > total) {
            FoxUI.toast.show('未达到最低起批数量!');
            return false
        }
        return true
    };
    modal.changeCartcount = function (count) {
        if ($("#menucart").length > 0) {
            var badge = $("#menucart").find(".badge");
            if (badge.length < 1) {
                $("#menucart").append('<span class="badge">' + count + "</div>")
            } else {
                badge.text(count)
            }
        }
    };
    return modal
});